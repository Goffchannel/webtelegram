<?php

namespace App\Http\Controllers;

use App\Models\PurchaseServiceAccess;
use App\Models\Setting;
use App\Services\PlooplayerEncryption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IptvController extends Controller
{
    // =========================================================================
    // PUBLIC: subscriber playlist entry-point
    // GET /iptv/{token}
    // =========================================================================

    /**
     * Return the outer Plooplayer JSON (groups format) for a subscriber.
     *
     * Validates the subscription, then returns a JSON whose `groups[0].ploorl`
     * is the encrypted URL pointing to the correct inner channel list slot.
     */
    public function playlist(Request $request, string $token)
    {
        $access = PurchaseServiceAccess::where('access_token', $token)->first();

        if (!$access) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($access->status === 'revoked') {
            return response()->json(['error' => 'Access revoked'], 403);
        }

        if ($access->isExpired()) {
            if ($access->status !== 'expired') {
                $access->update(['status' => 'expired']);
            }
            return response()->json(['error' => 'Subscription expired'], 403);
        }

        // IP binding: block if too many different IPs use this token
        $ip = $request->ip();
        if (!$access->checkAndBindIp($ip)) {
            return response()->json(['error' => 'Access restricted: too many devices. Contact support.'], 403);
        }

        // Slot block check
        $slot = (int) ($access->cdn_slot ?? 1);
        if ($this->isSlotBlocked($slot)) {
            return response()->json(['error' => 'Service temporarily unavailable. Contact support.'], 503);
        }

        $access->update(['last_viewed_at' => now()]);

        $listName  = Setting::get('iptv_list_name', 'Plooplayer VIP');
        $listPl    = Setting::get('iptv_list_pl',   '7PxQeW7s7VBU+8vW8rN0jG7+spPJZyYEYhzB4VivSv0=');
        $groupName = Setting::get('iptv_group_name', $listName);

        // Build the channels URL for this subscriber's assigned slot
        // $slot already set above
        $channelsUrl = $slot > 1
            ? route('iptv.channels.slot', ['slot' => $slot])
            : route('iptv.channels');

        $payload = [
            'name'   => $listName,
            'pl'     => $listPl,
            'groups' => [
                [
                    'name'   => $groupName,
                    'ploorl' => PlooplayerEncryption::encrypt($channelsUrl),
                    'import' => true,
                ],
            ],
        ];

        return response()->json($payload);
    }

    // =========================================================================
    // PUBLIC: inner channel list (IP-rate-limited)
    // GET /iptv/channels          → slot 1
    // GET /iptv/channels/{slot}   → slot N (N >= 2)
    // =========================================================================

    /**
     * Return the inner Plooplayer JSON (stations format) with the full channel list.
     *
     * The CDN token (x-tcdn-token) is injected dynamically from the slot config,
     * so a single base channel list serves all slots with different tokens.
     */
    public function channels(Request $request, int $slot = 1)
    {
        $ip = $request->ip();

        // --- Slot block check ---
        if ($this->isSlotBlocked($slot)) {
            return response('', 503);
        }

        // --- IP ban check ---
        $raw       = Setting::get('iptv_banned_ips', null);
        $bannedIps = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: []);
        if (in_array($ip, $bannedIps, true)) {
            return response('', 403);
        }

        // --- IP rate limit per slot (unique IPs per day) ---
        $maxIps  = (int) Setting::get('iptv_max_ips_per_day', '10');
        $dayKey  = 'iptv_daily_ips_slot' . $slot . '_' . now()->format('Y-m-d');
        $dailyIps = Cache::get($dayKey, []);

        if (!in_array($ip, $dailyIps, true)) {
            if (count($dailyIps) >= $maxIps) {
                return response()->json(['error' => 'Daily IP limit exceeded'], 429);
            }
            $dailyIps[] = $ip;
            Cache::put($dayKey, $dailyIps, now()->endOfDay());
        }

        // --- Log access ---
        $this->logAccess($ip, $request->userAgent());

        // --- Build and return inner JSON with slot's token injected ---
        $listName = Setting::get('iptv_list_name', 'Plooplayer VIP');
        $listPl   = Setting::get('iptv_list_pl',   '7PxQeW7s7VBU+8vW8rN0jG7+spPJZyYEYhzB4VivSv0=');
        $rawCh    = Setting::get('iptv_channels_json', null);
        $stations = is_array($rawCh) ? $rawCh : (json_decode((string) $rawCh, true) ?: []);

        // Inject the correct CDN token for this slot
        // Only for channels that need it (needs_cdn_token defaults to true for backwards compat)
        $token = $this->getSlotToken($slot);
        foreach ($stations as &$station) {
            if ($station['needs_cdn_token'] ?? true) {
                $station['headers']['x-tcdn-token'] = $token;
            } else {
                unset($station['headers']); // remove empty header for clearkey channels
            }
            unset($station['needs_cdn_token']);
        }
        unset($station);

        return response()->json([
            'name'     => $listName,
            'pl'       => $listPl,
            'stations' => $stations,
        ]);
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    private function isSlotBlocked(int $slot): bool
    {
        $raw   = Setting::get('iptv_cdn_slots', null);
        $slots = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: []);
        foreach ($slots as $s) {
            if ((int) ($s['slot'] ?? 0) === $slot) {
                return (bool) ($s['blocked'] ?? false);
            }
        }
        return false;
    }

    /**
     * Get the current CDN token for a given slot number.
     * Slot 1 reads from iptv_current_token (backwards compat).
     * Slots 2+ read from iptv_cdn_slots JSON array.
     */
    private function getSlotToken(int $slot): string
    {
        if ($slot <= 1) {
            return (string) Setting::get('iptv_current_token', '');
        }

        $raw   = Setting::get('iptv_cdn_slots', null);
        $slots = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: []);

        foreach ($slots as $s) {
            if ((int) ($s['slot'] ?? 0) === $slot) {
                return (string) ($s['current_token'] ?? '');
            }
        }

        return '';
    }

    private function logAccess(string $ip, ?string $ua): void
    {
        try {
            $logKey  = 'iptv_access_log';
            $maxLogs = 500;
            $rawLog = Setting::get($logKey, null);
            $logs   = is_array($rawLog) ? $rawLog : (json_decode((string) $rawLog, true) ?: []);

            array_unshift($logs, [
                'ip' => $ip,
                'ua' => $ua,
                'ts' => now()->toDateTimeString(),
            ]);

            if (count($logs) > $maxLogs) {
                $logs = array_slice($logs, 0, $maxLogs);
            }

            Setting::set($logKey, json_encode($logs), 'string');
        } catch (\Throwable $e) {
            // Non-critical — never fail the response because of logging
        }
    }
}
