<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseServiceAccess;
use App\Models\ServiceAccessLine;
use App\Models\Setting;
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

            // For shared (IPTV) lines, assign the CDN slot with the most available space.
            if ($sharedLine) {
                $cdnSlot = $this->assignCdnSlot();
                if ($cdnSlot === null) {
                    $purchase->markAsDeliveryFailed('Sin plazas disponibles: todos los slots CDN están al máximo de suscriptores activos. Inténtalo más tarde.');
                    return null;
                }
            } else {
                $cdnSlot = 1;
            }

            $access = PurchaseServiceAccess::create([
                'purchase_id'             => $purchase->id,
                'video_id'                => $purchase->video_id,
                'service_access_line_id'  => $line->id,
                'access_token'            => Str::random(64),
                'expires_at'              => now()->addDays($durationDays),
                'status'                  => 'active',
                'max_ips'                 => 2, // allow browser + Plooplayer app
                'cdn_slot'                => $cdnSlot,
            ]);

            // Only mark inventory (non-shared) lines as assigned.
            if (!$line->is_shared) {
                $line->update([
                    'is_assigned'          => true,
                    'assigned_purchase_id' => $purchase->id,
                    'assigned_at'          => now(),
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

    /**
     * Pick the CDN slot with the fewest active subscribers still under its max_users limit.
     * Returns null if ALL slots are at capacity — purchase should be blocked in that case.
     */
    private function assignCdnSlot(): ?int
    {
        // Count active subscribers per slot
        $counts = PurchaseServiceAccess::where('status', 'active')
            ->selectRaw('cdn_slot, count(*) as cnt')
            ->groupBy('cdn_slot')
            ->pluck('cnt', 'cdn_slot')
            ->toArray();

        // Load extra slots config (slots 2+)
        $raw        = Setting::get('iptv_cdn_slots', null);
        $extraSlots = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: []);

        // Build slot list starting with slot 1
        $defaultMax = 10;
        $slotList   = [['slot' => 1, 'max_users' => $defaultMax]];

        foreach ($extraSlots as $s) {
            $num = (int) ($s['slot'] ?? 0);
            if ($num < 2) continue;
            $slotList[] = ['slot' => $num, 'max_users' => (int) ($s['max_users'] ?? $defaultMax)];
        }

        usort($slotList, fn($a, $b) => $a['slot'] <=> $b['slot']);

        $bestSlot  = null;
        $bestCount = PHP_INT_MAX;

        foreach ($slotList as $s) {
            $used = (int) ($counts[$s['slot']] ?? 0);
            if ($used < $s['max_users'] && $used < $bestCount) {
                $bestCount = $used;
                $bestSlot  = $s['slot'];
            }
        }

        return $bestSlot; // null = todos los slots llenos
    }

    /**
     * Check if there is at least one CDN slot with space available.
     * Use this at checkout time to prevent payment when no slots are free.
     */
    public function hasAvailableIptvSlot(): bool
    {
        return $this->assignCdnSlot() !== null;
    }
}
