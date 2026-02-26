<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class CreatorSubscriptionController extends Controller
{
    private function getCreatorPriceId(): ?string
    {
        return Setting::get('creator_monthly_price_id') ?: env('CREATOR_MONTHLY_PRICE_ID');
    }

    public function show()
    {
        $user = Auth::user();

        return view('creator.subscription', [
            'user' => $user,
            'isActive' => $user?->is_creator && $user?->subscribed('creator'),
        ]);
    }

    public function checkout(Request $request)
    {
        $user = $request->user();
        $priceId = $this->getCreatorPriceId();

        if (!$priceId) {
            return back()->with('error', 'No se ha configurado el precio mensual de creadores en Stripe.');
        }

        if ($user->subscribed('creator')) {
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
