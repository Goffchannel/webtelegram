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
     * The Plooplayer app enters this URL. We validate the subscription,
     * then return a JSON whose `groups[0].ploorl` is the encrypted URL
     * pointing to our inner channel list endpoint (/iptv/channels).
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

        $access->update(['last_viewed_at' => now()]);

        $listName   = Setting::get('iptv_list_name', 'Plooplayer VIP');
        $listPl     = Setting::get('iptv_list_pl',   '7PxQeW7s7VBU+8vW8rN0jG7+spPJZyYEYhzB4VivSv0=');
        $groupName  = Setting::get('iptv_group_name', $listName);

        // The inner channel list URL (absolute)
        $channelsUrl = route('iptv.channels');

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
    // GET /iptv/channels
    // =========================================================================

    /**
     * Return the inner Plooplayer JSON (stations format) with the full channel list.
     *
     * This endpoint is what Plooplayer fetches after decrypting the ploorl above.
     * It is protected by:
     *   - IP ban list
     *   - Max 10 unique IPs per day (configurable via iptv_max_ips_per_day setting)
     */
    public function channels(Request $request)
    {
        $ip = $request->ip();

        // --- IP ban check ---
        $raw       = Setting::get('iptv_banned_ips', null);
        $bannedIps = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: []);
        if (in_array($ip, $bannedIps, true)) {
            return response('', 403);
        }

        // --- IP rate limit (unique IPs per day) ---
        $maxIps  = (int) Setting::get('iptv_max_ips_per_day', '10');
        $dayKey  = 'iptv_daily_ips_' . now()->format('Y-m-d');
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

        // --- Build and return inner JSON ---
        $listName  = Setting::get('iptv_list_name', 'Plooplayer VIP');
        $listPl    = Setting::get('iptv_list_pl',   '7PxQeW7s7VBU+8vW8rN0jG7+spPJZyYEYhzB4VivSv0=');
        $rawCh    = Setting::get('iptv_channels_json', null);
        $stations = is_array($rawCh) ? $rawCh : (json_decode((string) $rawCh, true) ?: []);

        return response()->json([
            'name'     => $listName,
            'pl'       => $listPl,
            'stations' => $stations,
        ]);
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

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
