<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseServiceAccess;
use App\Models\ServiceAccessLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceAccessManager
{
    public function provisionForPurchase(Purchase $purchase): ?PurchaseServiceAccess
    {
        $purchase->loadMissing('video');

        if (!$purchase->video || !$purchase->video->isServiceProduct()) {
            return null;
        }

        $existing = PurchaseServiceAccess::where('purchase_id', $purchase->id)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($purchase) {
            // 1. Try to find a shared (IPTV) line for this product — no inventory lock needed.
            $sharedLine = ServiceAccessLine::query()
                ->where('video_id', $purchase->video_id)
                ->where('is_shared', true)
                ->orderBy('id')
                ->first();

            // 2. Fall back to the classic inventory model (one unique line per purchase).
            $line = $sharedLine ?? ServiceAccessLine::query()
                ->where('video_id', $purchase->video_id)
                ->where('is_assigned', false)
                ->whereNull('assigned_purchase_id')
                ->where(function ($q) { $q->where('is_shared', false)->orWhereNull('is_shared'); })
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$line) {
                $purchase->markAsDeliveryFailed('Sin stock: no hay lineas disponibles para este producto.');
                return null;
            }

            $durationDays = max(1, (int) ($purchase->video->duration_days ?? 30));

            $access = PurchaseServiceAccess::create([
                'purchase_id'             => $purchase->id,
                'video_id'                => $purchase->video_id,
                'service_access_line_id'  => $line->id,
                'access_token'            => Str::random(64),
                'expires_at'              => now()->addDays($durationDays),
                'status'                  => 'active',
                'max_ips'                 => 2, // allow browser + Plooplayer app
            ]);

            // Only mark inventory (non-shared) lines as assigned.
            if (!$line->is_shared) {
                $line->update([
                    'is_assigned'         => true,
                    'assigned_purchase_id' => $purchase->id,
                    'assigned_at'         => now(),
                ]);
            }

            // Auto-verify: Stripe payment is already confirmed, no manual review needed.
            if ($purchase->verification_status === 'pending') {
                $purchase->update(['verification_status' => 'verified']);
            }

            $purchase->markAsDelivered([
                'service_access' => true,
                'access_token'   => $access->access_token,
                'expires_at'     => $access->expires_at?->toIso8601String(),
            ]);

            return $access;
        });
    }
}
