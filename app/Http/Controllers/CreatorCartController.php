<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;

class CreatorCartController extends Controller
{
    public function show(User $creator)
    {
        if (!$creator->is_creator || (!$creator->is_admin && $creator->creator_subscription_status !== 'active')) {
            abort(404);
        }
        if ($creator->is_admin) {
            return redirect()->route('creator.storefront', $creator->creator_slug);
        }
        $methods = $creator->creator_payment_methods ?? [];
        return view('creator.cart', compact('creator', 'methods'));
    }

    public function checkout(Request $request, User $creator)
    {
        if (!$creator->is_creator || (!$creator->is_admin && $creator->creator_subscription_status !== 'active')) {
            abort(404);
        }
        if ($creator->is_admin) {
            return redirect()->route('creator.storefront', $creator->creator_slug);
        }

        $validated = $request->validate([
            'video_ids'         => 'required|array|min:1|max:20',
            'video_ids.*'       => 'required|integer|exists:videos,id',
            'telegram_username' => 'required|string|max:255',
            'payment_method'    => 'nullable|string|max:80',
            'payment_reference' => 'nullable|string|max:500',
            'discount_code'     => 'nullable|string|max:50',
        ]);

        $videos = Video::whereIn('id', $validated['video_ids'])
            ->where('creator_id', $creator->id)
            ->get();

        if ($videos->isEmpty()) {
            return back()->with('error', 'No se encontraron productos válidos en el carrito.');
        }

        foreach ($videos as $video) {
            if ($video->isServiceProduct() && $video->availableServiceLines()->count() < 1) {
                return back()->with('error', "Sin stock: \"{$video->title}\" no tiene líneas disponibles.");
            }
        }

        $cleanUsername = ltrim(trim($validated['telegram_username']), '@');
        $totalAmount   = (float) $videos->sum('price');

        $totalDiscount = 0.0;
        $appliedCode   = null;

        if (!empty($validated['discount_code'])) {
            $discountCode = DiscountCode::where('code', strtoupper($validated['discount_code']))->first();
            if ($discountCode && $discountCode->isValid($totalAmount)) {
                $totalDiscount = $discountCode->apply($totalAmount);
                $appliedCode   = strtoupper($validated['discount_code']);
                $discountCode->increment('used_count');
            }
        }

        $instructions = $this->resolveInstructions($creator, $validated['payment_method'] ?? 'otro');
        $purchases    = [];

        foreach ($videos as $video) {
            if (!$video->isServiceProduct()) {
                $already = Purchase::where('video_id', $video->id)
                    ->where('telegram_username', $cleanUsername)
                    ->where('verification_status', 'verified')
                    ->exists();
                if ($already) continue;
            }

            $itemDiscount = $totalAmount > 0
                ? round($totalDiscount * ((float) $video->price / $totalAmount), 2)
                : 0.0;

            $purchase = Purchase::create([
                'video_id'             => $video->id,
                'creator_id'           => $creator->id,
                'amount'               => max(0.0, round((float) $video->price - $itemDiscount, 2)),
                'currency'             => 'usd',
                'telegram_username'    => $cleanUsername,
                'purchase_status'      => 'completed',
                'verification_status'  => 'pending',
                'delivery_status'      => 'pending',
                'delivery_attempts'    => 0,
                'payment_method'       => $validated['payment_method'] ?? 'manual',
                'payment_reference'    => $validated['payment_reference'] ?? null,
                'payment_instructions' => $instructions,
                'customer_email'       => null,
                'stripe_session_id'    => 'cart_' . now()->timestamp . '_' . bin2hex(random_bytes(4)),
                'discount_code'        => $appliedCode,
                'discount_amount'      => $itemDiscount,
            ]);

            $purchases[] = $purchase;
        }

        if (empty($purchases)) {
            return back()->with('error', 'Ya tienes todos estos productos aprobados para tu usuario de Telegram.');
        }

        session(['cart_purchases' => collect($purchases)->pluck('purchase_uuid')->toArray()]);

        // Clear cart from session signal so the JS can clear localStorage
        session()->flash('clear_cart', true);

        return redirect()->route('creator.cart.success', $creator->creator_slug);
    }

    public function success(User $creator)
    {
        $uuids = session('cart_purchases', []);
        if (empty($uuids)) {
            return redirect()->route('creator.storefront', $creator->creator_slug);
        }
        $purchases = Purchase::with('video')->whereIn('purchase_uuid', $uuids)->get();
        return view('creator.cart-success', compact('creator', 'purchases'));
    }

    private function resolveInstructions(User $creator, string $method): ?string
    {
        $methods = $creator->creator_payment_methods ?? [];
        return match ($method) {
            'paypal'              => $methods['paypal_url'] ?? null,
            'boton_personalizado' => $methods['payment_button_html'] ?? null,
            default               => $methods['other_payment_notes'] ?? null,
        };
    }
}
