<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Video;
use App\Services\PayPalService;
use App\Services\ServiceAccessManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaypalController extends Controller
{
    public function createOrder(Request $request)
    {
        $request->validate([
            'video_id'          => 'required|exists:videos,id',
            'telegram_username' => 'required|string|max:255',
        ]);

        try {
            $video           = Video::findOrFail($request->video_id);
            $telegramUsername = trim($request->telegram_username, '@');

            // Stock check for non-shared service products
            if ($video->isServiceProduct()
                && !$video->serviceLines()->where('is_shared', true)->exists()
                && $video->availableServiceLines()->count() < 1
            ) {
                return response()->json(['error' => 'Sin stock disponible.'], 400);
            }

            // Duplicate purchase check
            $existing = Purchase::where('telegram_username', $telegramUsername)
                ->where('video_id', $video->id)
                ->where('purchase_status', 'completed')
                ->first();

            if ($existing && !$video->isServiceProduct()) {
                return response()->json([
                    'error'             => 'Ya compraste este video.',
                    'existing_purchase' => ['purchase_uuid' => $existing->purchase_uuid],
                ], 400);
            }

            $paypal = new PayPalService();
            if (!$paypal->isConfigured()) {
                return response()->json(['error' => 'PayPal no está configurado.'], 500);
            }

            // Creator's PayPal email as payee (so payment goes directly to them)
            $payeeEmail = $video->creator?->paypal_email ?: null;

            $purchaseUuid = Str::uuid()->toString();
            $order        = $paypal->createOrder($video->price, 'USD', $purchaseUuid, $payeeEmail);
            $orderId      = $order['id'];

            // Store pending purchase so captureOrder can find it
            $user = $this->getOrCreateUser($telegramUsername);
            Purchase::create([
                'purchase_uuid'            => $purchaseUuid,
                'stripe_session_id'        => 'paypal_' . $orderId,
                'video_id'                 => $video->id,
                'creator_id'               => $video->creator_id,
                'user_id'                  => $user?->id,
                'telegram_username'        => $telegramUsername,
                'amount'                   => $video->price,
                'currency'                 => 'usd',
                'purchase_status'          => 'pending',
                'delivery_status'          => 'pending',
                'delivery_attempts'        => 0,
                'stripe_metadata'          => ['paypal_order_id' => $orderId],
            ]);

            return response()->json(['order_id' => $orderId]);

        } catch (\Exception $e) {
            Log::error('PayPal createOrder failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al crear la orden PayPal.'], 500);
        }
    }

    public function captureOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
        ]);

        try {
            $orderId  = $request->order_id;
            $purchase = Purchase::where('stripe_session_id', 'paypal_' . $orderId)->first();

            if (!$purchase) {
                return response()->json(['error' => 'Orden no encontrada.'], 404);
            }

            if ($purchase->purchase_status === 'completed') {
                // Already captured (e.g. double-click) — just redirect
                return response()->json([
                    'success'      => true,
                    'redirect_url' => route('purchase.view', $purchase->purchase_uuid),
                ]);
            }

            $paypal  = new PayPalService();
            $capture = $paypal->captureOrder($orderId);

            if (($capture['status'] ?? '') !== 'COMPLETED') {
                return response()->json(['error' => 'El pago no fue completado por PayPal.'], 400);
            }

            $purchase->update([
                'purchase_status'          => 'completed',
                'stripe_payment_intent_id' => 'paypal_capture_' . $orderId,
            ]);

            $video = $purchase->video;
            if ($video?->isServiceProduct()) {
                app(ServiceAccessManager::class)->provisionForPurchase($purchase);
                $purchase->refresh();
                if ($purchase->delivery_status !== 'delivered') {
                    $purchase->update(['verification_status' => 'verified']);
                    $purchase->markAsDelivered();
                }
            }

            return response()->json([
                'success'      => true,
                'redirect_url' => route('purchase.view', $purchase->purchase_uuid),
            ]);

        } catch (\Exception $e) {
            Log::error('PayPal captureOrder failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al capturar el pago PayPal.'], 500);
        }
    }

    private function getOrCreateUser(string $telegramUsername): ?User
    {
        $username = ltrim($telegramUsername, '@');
        $email    = $username . '@telegram.local';

        return User::firstOrCreate(
            ['email' => $email],
            [
                'name'             => $username,
                'telegram_username'=> $username,
                'password'         => bcrypt(Str::random(32)),
            ]
        );
    }
}
