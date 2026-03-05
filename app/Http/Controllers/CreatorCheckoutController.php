<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Video;
use App\Services\PayPalService;
use Illuminate\Http\Request;

class CreatorCheckoutController extends Controller
{
    public function form(User $creator, Video $video)
    {
        if ($video->creator_id !== $creator->id || !$creator->is_creator || (!$creator->is_admin && $creator->creator_subscription_status !== 'active')) {
            abort(404);
        }

        if ($creator->is_admin) {
            return redirect()->route('payment.form', $video);
        }

        if ($video->isServiceProduct() && $video->availableServiceLines()->count() < 1) {
            return back()->with('error', 'Sin stock: no hay lineas disponibles para este producto.');
        }

        $methods = $creator->creator_payment_methods ?? [];

        $paypal = new PayPalService();
        $paypalConfigured = $paypal->isConfigured() && !empty($creator->paypal_email);
        $paypalClientId   = $paypalConfigured ? $paypal->getClientId() : null;

        return view('creator.checkout', compact('creator', 'video', 'methods', 'paypalConfigured', 'paypalClientId'));
    }

    public function submit(Request $request, User $creator, Video $video)
    {
        if ($video->creator_id !== $creator->id || !$creator->is_creator || (!$creator->is_admin && $creator->creator_subscription_status !== 'active')) {
            abort(404);
        }

        if ($creator->is_admin) {
            return redirect()->route('payment.form', $video);
        }

        if ($video->isServiceProduct() && $video->availableServiceLines()->count() < 1) {
            return back()->with('error', 'Sin stock: no hay lineas disponibles para este producto.');
        }

        $validated = $request->validate([
            'telegram_username' => 'required|string|max:255',
            'payment_method'    => 'nullable|string|max:80',
            'payment_reference' => 'nullable|string|max:500',
            'discount_code'     => 'nullable|string|max:50',
        ]);

        $cleanUsername = ltrim(trim($validated['telegram_username']), '@');

        $existingPurchase = Purchase::where('video_id', $video->id)
            ->where('telegram_username', $cleanUsername)
            ->where('verification_status', 'verified')
            ->first();

        if ($existingPurchase && !$video->isServiceProduct()) {
            return back()->with('error', 'Ya tienes este video aprobado para tu usuario de Telegram.');
        }

        $baseAmount     = (float) $video->price;
        $discountAmount = 0.0;
        $appliedCode    = null;

        if (!empty($validated['discount_code'])) {
            $discountCode = DiscountCode::where('code', strtoupper($validated['discount_code']))->first();
            if ($discountCode && $discountCode->isValid($baseAmount)) {
                $discountAmount = $discountCode->apply($baseAmount);
                $appliedCode    = strtoupper($validated['discount_code']);
                $discountCode->increment('used_count');
            }
        }

        $finalAmount = max(0, round($baseAmount - $discountAmount, 2));

        $purchase = Purchase::create([
            'video_id'            => $video->id,
            'creator_id'          => $creator->id,
            'amount'              => $finalAmount,
            'currency'            => 'usd',
            'telegram_username'   => $cleanUsername,
            'purchase_status'     => 'completed',
            'verification_status' => 'pending',
            'delivery_status'     => 'pending',
            'delivery_attempts'   => 0,
            'payment_method'      => $validated['payment_method'] ?? 'manual',
            'payment_reference'   => $validated['payment_reference'] ?? null,
            'payment_instructions'=> $this->resolveInstructions($creator, $validated['payment_method'] ?? 'otro'),
            'customer_email'      => null,
            'stripe_session_id'   => 'manual_' . now()->timestamp . '_' . bin2hex(random_bytes(4)),
            'discount_code'       => $appliedCode,
            'discount_amount'     => $discountAmount,
        ]);

        return redirect()->route('purchase.view', $purchase->purchase_uuid)
            ->with('success', 'Solicitud registrada. El creador validara tu pago manualmente.');
    }

    public function validateDiscount(Request $request)
    {
        $request->validate([
            'code'   => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $code = DiscountCode::where('code', strtoupper($request->code))->first();

        if (!$code || !$code->isValid((float) $request->amount)) {
            return response()->json(['valid' => false, 'message' => 'Código inválido, expirado o sin usos disponibles.']);
        }

        $discount = $code->apply((float) $request->amount);

        return response()->json([
            'valid'        => true,
            'discount'     => $discount,
            'formatted'    => '€' . number_format($discount, 2),
            'final_amount' => round((float) $request->amount - $discount, 2),
            'description'  => $code->description,
        ]);
    }

    private function resolveInstructions(User $creator, string $method): ?string
    {
        $methods = $creator->creator_payment_methods ?? [];

        return match ($method) {
            'paypal'             => $methods['paypal_url'] ?? null,
            'boton_personalizado'=> $methods['payment_button_html'] ?? null,
            default              => $methods['other_payment_notes'] ?? null,
        };
    }
}
