<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\StripeClient;

class CreatorSubscriptionController extends Controller
{
    private function getCreatorPriceId(): ?string
    {
        return Setting::get('creator_monthly_price_id') ?: env('CREATOR_MONTHLY_PRICE_ID');
    }

    private function getCreatorMonthlyPriceUsd(): float
    {
        return (float) (Setting::get('creator_monthly_price_usd', 5.00) ?: 5.00);
    }

    private function ensureCreatorStripePrice(): ?string
    {
        $stripeSecret = Setting::get('stripe_secret') ?: config('cashier.secret');
        if (!$stripeSecret) {
            return $this->getCreatorPriceId();
        }

        $usd = $this->getCreatorMonthlyPriceUsd();
        $cents = (int) round($usd * 100);
        $storedCents = (int) (Setting::get('creator_monthly_price_amount_cents', 0) ?: 0);
        $storedPriceId = Setting::get('creator_monthly_price_id');

        if ($storedPriceId && $storedCents === $cents) {
            return $storedPriceId;
        }

        try {
            $stripe = new StripeClient($stripeSecret);

            $productId = Setting::get('creator_monthly_product_id');
            if (!$productId) {
                $product = $stripe->products->create([
                    'name' => 'Creator Membership',
                    'description' => 'Monthly creator subscription for selling on the platform',
                ]);
                $productId = $product->id;
                Setting::set('creator_monthly_product_id', $productId);
            }

            $price = $stripe->prices->create([
                'currency' => 'usd',
                'unit_amount' => $cents,
                'recurring' => ['interval' => 'month'],
                'product' => $productId,
            ]);

            Setting::set('creator_monthly_price_id', $price->id);
            Setting::set('creator_monthly_price_amount_cents', $cents, 'integer');

            return $price->id;
        } catch (\Throwable $e) {
            Log::warning('Failed to auto-create creator Stripe price', [
                'error' => $e->getMessage(),
                'usd' => $usd,
            ]);

            return $this->getCreatorPriceId();
        }
    }

    public function show()
    {
        $user = Auth::user();

        return view('creator.subscription', [
            'user' => $user,
            'isActive' => $user?->is_creator && $user?->creator_subscription_status === 'active',
            'monthlyPriceUsd' => $this->getCreatorMonthlyPriceUsd(),
        ]);
    }

    public function checkout(Request $request)
    {
        $user = $request->user();
        $priceId = $this->ensureCreatorStripePrice();

        if (!$priceId) {
            return back()->with('error', 'No se ha configurado el precio mensual de creadores en Stripe.');
        }

        if ($user->is_creator && $user->creator_subscription_status === 'active') {
            return redirect()->route('creator.dashboard')->with('success', 'Tu membresia ya esta activa.');
        }

        return $user->newSubscription('creator', $priceId)->checkout([
            'success_url' => route('creator.subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('creator.subscription.show'),
            'metadata' => [
                'purchase_type' => 'creator_subscription',
                'user_id' => (string) $user->id,
            ],
        ]);
    }

    public function success(Request $request)
    {
        $user = $request->user();
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('creator.subscription.show')
                ->with('error', 'Sesion de pago invalida.');
        }

        $stripeSecret = Setting::get('stripe_secret') ?: config('cashier.secret');
        if (!$stripeSecret) {
            return redirect()->route('creator.subscription.show')
                ->with('error', 'Stripe no esta configurado.');
        }

        try {
            Stripe::setApiKey($stripeSecret);
            $session = Session::retrieve($sessionId);

            if (($session->mode ?? null) !== 'subscription') {
                throw new \RuntimeException('La sesion no es de suscripcion.');
            }

            $sessionUserId = data_get($session, 'metadata.user_id');
            if ((string) $sessionUserId !== (string) $user->id) {
                throw new \RuntimeException('La sesion no corresponde al usuario autenticado.');
            }

            if (($session->payment_status ?? null) !== 'paid') {
                throw new \RuntimeException('El pago aun no esta completado.');
            }
        } catch (\Throwable $e) {
            Log::warning('Creator subscription verification failed', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('creator.subscription.show')
                ->with('error', 'No se pudo validar el pago de membresia.');
        }

        // Pre-sync the Cashier subscription row so the middleware doesn't
        // find an empty subscriptions table and immediately revert the status.
        // (Stripe webhooks arrive asynchronously — the row may not exist yet.)
        if ($session->subscription) {
            try {
                $stripeSub = \Stripe\Subscription::retrieve($session->subscription);
                $user->subscriptions()->updateOrCreate(
                    ['stripe_id' => $session->subscription],
                    [
                        'name'         => 'creator',
                        'stripe_status'=> $stripeSub->status,
                        'stripe_price' => $stripeSub->items->data[0]->price->id ?? null,
                        'quantity'     => 1,
                        'trial_ends_at'=> null,
                        'ends_at'      => null,
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('CreatorSubscription: could not pre-sync Cashier row', [
                    'user_id'         => $user->id,
                    'subscription_id' => $session->subscription,
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        $updates = [
            'is_creator' => true,
            'creator_subscription_status' => 'active',
        ];

        if (!$user->creator_slug) {
            $updates['creator_slug'] = Str::slug($user->name) . '-' . Str::lower(Str::random(5));
        }

        if (!$user->creator_store_name) {
            $updates['creator_store_name'] = $user->name;
        }

        $user->update($updates);

        return redirect()->route('creator.dashboard')->with('success', 'Membresia activada. Ya puedes vender en tu tienda.');
    }

    public function billingPortal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(route('creator.subscription.show'));
    }
}
