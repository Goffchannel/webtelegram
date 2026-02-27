<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;

class CreatorCheckoutController extends Controller
{
    public function form(User $creator, Video $video)
    {
        if ($video->creator_id !== $creator->id || !$creator->is_creator || (!$creator->is_admin && !$creator->subscribed('creator'))) {
            abort(404);
        }

        if ($creator->is_admin) {
            return redirect()->route('payment.form', $video);
        }

        if ($video->isServiceProduct() && $video->availableServiceLines()->count() < 1) {
            return back()->with('error', 'Sin stock: no hay lineas disponibles para este producto.');
        }

        $methods = $creator->creator_payment_methods ?? [];

        return view('creator.checkout', compact('creator', 'video', 'methods'));
    }

    public function submit(Request $request, User $creator, Video $video)
    {
        if ($video->creator_id !== $creator->id || !$creator->is_creator || (!$creator->is_admin && !$creator->subscribed('creator'))) {
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
            'payment_method' => 'required|string|max:80',
            'payment_reference' => 'nullable|string|max:255',
            'proof_url' => 'nullable|url|max:500',
        ]);

        $cleanUsername = ltrim(trim($validated['telegram_username']), '@');

        $existingPurchase = Purchase::where('video_id', $video->id)
            ->where('telegram_username', $cleanUsername)
            ->where('verification_status', 'verified')
            ->first();

        if ($existingPurchase && !$video->isServiceProduct()) {
            return back()->with('error', 'Ya tienes este video aprobado para tu usuario de Telegram.');
        }

        $purchase = Purchase::create([
            'video_id' => $video->id,
            'creator_id' => $creator->id,
            'amount' => $video->price,
            'currency' => 'usd',
            'telegram_username' => $cleanUsername,
            'purchase_status' => 'completed',
            'verification_status' => 'pending',
            'delivery_status' => 'pending',
            'delivery_attempts' => 0,
            'payment_method' => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'] ?? null,
            'proof_url' => $validated['proof_url'] ?? null,
            'payment_instructions' => $this->resolveInstructions($creator, $validated['payment_method']),
            'customer_email' => null,
            'stripe_session_id' => 'manual_' . now()->timestamp . '_' . bin2hex(random_bytes(4)),
        ]);

        return redirect()->route('purchase.view', $purchase->purchase_uuid)
            ->with('success', 'Solicitud registrada. El creador validara tu pago manualmente.');
    }

    private function resolveInstructions(User $creator, string $method): ?string
    {
        $methods = $creator->creator_payment_methods ?? [];

        return match ($method) {
            'paypal' => $methods['paypal_url'] ?? null,
            'boton_personalizado' => $methods['payment_button_html'] ?? null,
            default => $methods['other_payment_notes'] ?? null,
        };
    }
}
