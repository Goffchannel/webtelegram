<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseServiceAccess;
use App\Models\Setting;
use App\Services\M3uParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IptvAdminController extends Controller
{
    // =========================================================================
    // Index page
    // =========================================================================

    /** Setting::get() may return an already-decoded array (type='json') or a raw string. */
    private function jsonSetting(string $key, array $default = []): array
    {
        $value = Setting::get($key, null);
        if ($value === null)  return $default;
        if (is_array($value)) return $value;
        return json_decode($value, true) ?: $default;
    }

    public function index()
    {
        $channels  = $this->jsonSetting('iptv_channels_json');
        $bannedIps = $this->jsonSetting('iptv_banned_ips');
        $accessLog = $this->jsonSetting('iptv_access_log');
        $settings  = [
            'list_name'       => Setting::get('iptv_list_name',       'Plooplayer VIP'),
            'group_name'      => Setting::get('iptv_group_name',       'Plooplayer VIP'),
            'list_pl'         => Setting::get('iptv_list_pl',          '7PxQeW7s7VBU+8vW8rN0jG7+spPJZyYEYhzB4VivSv0='),
            'max_ips_per_day' => Setting::get('iptv_max_ips_per_day',  '10'),
            'current_token'   => Setting::get('iptv_current_token',    ''),
        ];

        // Load CDN slots (additional slots only; slot 1 shown separately via current_token)
        $cdnSlots = $this->jsonSetting('iptv_cdn_slots');

        // Count active subscribers per slot
        $slotCounts = PurchaseServiceAccess::where('status', 'active')
            ->selectRaw('cdn_slot, count(*) as cnt')
            ->groupBy('cdn_slot')
            ->pluck('cnt', 'cdn_slot')
            ->toArray();

        return view('admin.iptv.index', compact('channels', 'bannedIps', 'accessLog', 'settings', 'cdnSlots', 'slotCounts'));
    }

    // =========================================================================
    // Save general settings
    // =========================================================================

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'list_name'       => 'required|string|max:120',
            'group_name'      => 'required|string|max:120',
            'list_pl'         => 'required|string|max:200',
            'max_ips_per_day' => 'required|integer|min:1|max:1000',
        ]);

        Setting::set('iptv_list_name',       $data['list_name']);
        Setting::set('iptv_group_name',      $data['group_name']);
        Setting::set('iptv_list_pl',         $data['list_pl']);
        Setting::set('iptv_max_ips_per_day', (string) $data['max_ips_per_day']);

        return back()->with('success', 'Configuración guardada.');
    }

    // =========================================================================
    // M3U parse preview (returns JSON — called via JS fetch)
    // =========================================================================

    public function parseM3u(Request $request)
    {
        $request->validate(['m3u' => 'required|string']);

        try {
            $parsed = M3uParser::parse($request->input('m3u'));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'count'    => count($parsed),
            'channels' => $parsed,
        ]);
    }

    // =========================================================================
    // Save encrypted channel list to DB (token-agnostic — token injected at runtime)
    // =========================================================================

    public function saveChannels(Request $request)
    {
        $request->validate(['m3u' => 'required|string']);

        $parsed = M3uParser::parse($request->input('m3u'));

        if (empty($parsed)) {
            return back()->with('error', 'No se encontraron canales MPD en el M3U.');
        }

        // Store channels WITHOUT baking in the CDN token.
        // The token is injected dynamically per-slot when the endpoint is served.
        $needsCdnToken = $request->boolean('cdn_token', true);
        $newStations   = [];
        foreach ($parsed as $channel) {
            $newStations[] = M3uParser::toStation($channel, '', $needsCdnToken);
        }

        if ($request->boolean('merge')) {
            // Merge: keep existing channels, append new ones (dedup by encrypted URL key)
            $existing = $this->jsonSetting('iptv_channels_json');
            $existingUrls = collect($existing)->pluck('url')->filter()->flip()->toArray();
            $added = 0;
            foreach ($newStations as $s) {
                if (!isset($existingUrls[$s['url'] ?? ''])) {
                    $existing[] = $s;
                    $added++;
                }
            }
            $stations = array_values($existing);
            $message  = "$added canales añadidos (" . count($stations) . " en total).";
        } else {
            $stations = $newStations;
            $message  = count($stations) . ' canales guardados (lista reemplazada).';
        }

        Setting::set('iptv_channels_json', json_encode($stations), 'string');
        Setting::set('iptv_channels_updated_at', now()->toDateTimeString());

        Log::info('IPTV channel list updated', ['count' => count($stations), 'merge' => $request->boolean('merge')]);

        return back()->with('success', $message);
    }

    // =========================================================================
    // Refresh x-tcdn-token for slot 1 from its external URL
    // =========================================================================

    public function refreshToken()
    {
        $tokenUrl = $this->getSlot1Url();

        try {
            $response = Http::timeout(10)->get($tokenUrl);

            if (!$response->successful()) {
                return back()->with('error', 'Error al obtener el token: HTTP ' . $response->status());
            }

            $newToken = trim($response->body());

            if (empty($newToken)) {
                return back()->with('error', 'El servidor devolvió un token vacío.');
            }

            Setting::set('iptv_current_token', $newToken);

            return back()->with('success', 'Token slot 1 actualizado: ' . $newToken);
        } catch (\Throwable $e) {
            Log::error('IPTV token refresh failed (slot 1)', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al contactar el servidor de tokens: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // CDN Slots management (slots 2+)
    // =========================================================================

    /**
     * Look up a subscriber's CDN slot by Telegram username or purchase UUID.
     * Returns JSON so the frontend can display it without a page reload.
     */
    public function lookupSubscriber(Request $request)
    {
        $query = trim($request->input('query', ''));

        if (empty($query)) {
            return response()->json(['error' => 'Introduce un @username o UUID.'], 422);
        }

        // Search by UUID first, then by telegram_username
        $purchase = Purchase::where('purchase_uuid', $query)->first()
            ?? Purchase::where('telegram_username', ltrim($query, '@'))
                ->whereHas('serviceAccess')
                ->latest()
                ->first();

        if (!$purchase) {
            return response()->json(['error' => 'No se encontró ninguna compra IPTV para ese usuario/UUID.'], 404);
        }

        $access = $purchase->serviceAccess;

        if (!$access) {
            return response()->json(['error' => 'Esta compra no tiene acceso de servicio asociado.'], 404);
        }

        return response()->json([
            'purchase_uuid'    => $purchase->purchase_uuid,
            'telegram_username'=> '@' . ltrim($purchase->telegram_username, '@'),
            'cdn_slot'         => $access->cdn_slot ?? 1,
            'status'           => $access->status,
            'expires_at'       => $access->expires_at?->format('d/m/Y H:i'),
            'access_token'     => substr($access->access_token, 0, 12) . '…',
            'last_viewed_at'   => $access->last_viewed_at?->format('d/m/Y H:i') ?? 'nunca',
        ]);
    }

    /**
     * Move a subscriber to a different CDN slot.
     */
    public function moveSubscriber(Request $request)
    {
        $data = $request->validate([
            'purchase_uuid' => 'required|string',
            'cdn_slot'      => 'required|integer|min:1|max:20',
        ]);

        $purchase = Purchase::where('purchase_uuid', $data['purchase_uuid'])->first();

        if (!$purchase) {
            return response()->json(['error' => 'Compra no encontrada.'], 404);
        }

        $access = $purchase->serviceAccess;

        if (!$access) {
            return response()->json(['error' => 'Esta compra no tiene acceso de servicio asociado.'], 404);
        }

        $oldSlot = $access->cdn_slot ?? 1;
        $newSlot = (int) $data['cdn_slot'];

        $access->update(['cdn_slot' => $newSlot]);

        Log::info('IPTV subscriber moved', [
            'purchase_uuid' => $data['purchase_uuid'],
            'from_slot'     => $oldSlot,
            'to_slot'       => $newSlot,
        ]);

        return response()->json([
            'ok'      => true,
            'message' => "Suscriptor movido del slot $oldSlot al slot $newSlot.",
            'cdn_slot' => $newSlot,
        ]);
    }

    /**
     * Call the external token generator (generates tokens for all extra slots).
     * Returns JSON with the raw output so the frontend can display it.
     */
    public function generateTokens()
    {
        $url = 'http://212.227.178.212/movistar.php';

        try {
            $response = Http::timeout(30)->get($url);
            $body     = trim($response->body());

            $lines   = array_filter(array_map('trim', explode("\n", $body)));
            $success = collect($lines)->every(fn($l) => str_starts_with($l, 'EXITO:'));

            return response()->json([
                'ok'     => $success,
                'output' => $body,
                'lines'  => array_values($lines),
            ]);
        } catch (\Throwable $e) {
            Log::error('IPTV generate tokens failed', ['error' => $e->getMessage()]);
            return response()->json([
                'ok'     => false,
                'output' => 'Error al contactar el servidor: ' . $e->getMessage(),
                'lines'  => [],
            ], 500);
        }
    }

    /** Add or update a CDN slot (slot number 2 or higher). */
    public function saveSlot(Request $request)
    {
        $data = $request->validate([
            'slot'      => 'required|integer|min:1|max:20',
            'token_url' => 'required|url|max:500',
            'max_users' => 'required|integer|min:1|max:1000',
        ]);

        $slots = $this->jsonSetting('iptv_cdn_slots');

        // Upsert: update existing slot or add new one
        $found = false;
        foreach ($slots as &$s) {
            if ((int) ($s['slot'] ?? 0) === (int) $data['slot']) {
                $s['token_url']  = $data['token_url'];
                $s['max_users']  = (int) $data['max_users'];
                $found = true;
                break;
            }
        }
        unset($s);

        if (!$found) {
            $slots[] = [
                'slot'          => (int) $data['slot'],
                'token_url'     => $data['token_url'],
                'current_token' => '',
                'max_users'     => (int) $data['max_users'],
            ];
        }

        // Sort by slot number
        usort($slots, fn($a, $b) => ($a['slot'] ?? 0) <=> ($b['slot'] ?? 0));

        Setting::set('iptv_cdn_slots', json_encode($slots), 'string');

        return back()->with('success', 'Slot ' . $data['slot'] . ' guardado.');
    }

    /** Block or unblock a CDN slot. Blocked slots reject new purchases and active access. */
    public function toggleSlotBlock(Request $request)
    {
        $data = $request->validate(['slot' => 'required|integer|min:1|max:20']);
        $slot = (int) $data['slot'];

        $slots = $this->jsonSetting('iptv_cdn_slots');

        $found      = false;
        $nowBlocked = false;

        foreach ($slots as &$s) {
            if ((int) ($s['slot'] ?? 0) === $slot) {
                $s['blocked'] = !($s['blocked'] ?? false);
                $nowBlocked   = $s['blocked'];
                $found        = true;
                break;
            }
        }
        unset($s);

        if (!$found) {
            // Slot not yet in config — create minimal entry just to store the blocked flag
            $nowBlocked = true;
            $slots[]    = [
                'slot'          => $slot,
                'token_url'     => '',
                'current_token' => '',
                'max_users'     => 10,
                'blocked'       => true,
            ];
        }

        usort($slots, fn($a, $b) => ($a['slot'] ?? 0) <=> ($b['slot'] ?? 0));
        Setting::set('iptv_cdn_slots', json_encode($slots), 'string');

        $label = $nowBlocked ? 'bloqueado' : 'desbloqueado';
        return back()->with('success', "Slot $slot $label.");
    }

    /** Remove a CDN slot (only slots 2+). */
    public function removeSlot(Request $request)
    {
        $data = $request->validate(['slot' => 'required|integer|min:2']);

        $slots = $this->jsonSetting('iptv_cdn_slots');
        $slots = array_values(array_filter($slots, fn($s) => (int) ($s['slot'] ?? 0) !== (int) $data['slot']));

        Setting::set('iptv_cdn_slots', json_encode($slots), 'string');

        return back()->with('success', 'Slot ' . $data['slot'] . ' eliminado.');
    }

    /** Refresh the CDN token for a specific slot from its configured URL. */
    public function refreshSlotToken(Request $request)
    {
        $data = $request->validate(['slot' => 'required|integer|min:1|max:20']);
        $slot = (int) $data['slot'];

        if ($slot === 1) {
            return $this->refreshToken();
        }

        $slots = $this->jsonSetting('iptv_cdn_slots');
        $tokenUrl = null;

        foreach ($slots as $s) {
            if ((int) ($s['slot'] ?? 0) === $slot) {
                $tokenUrl = $s['token_url'] ?? null;
                break;
            }
        }

        if (!$tokenUrl) {
            return back()->with('error', "Slot $slot no encontrado o sin URL configurada.");
        }

        try {
            $response = Http::timeout(10)->get($tokenUrl);

            if (!$response->successful()) {
                return back()->with('error', "Slot $slot: error HTTP " . $response->status());
            }

            $newToken = trim($response->body());

            if (empty($newToken)) {
                return back()->with('error', "Slot $slot: el servidor devolvió un token vacío.");
            }

            // Update the token in the slots array
            foreach ($slots as &$s) {
                if ((int) ($s['slot'] ?? 0) === $slot) {
                    $s['current_token'] = $newToken;
                    break;
                }
            }
            unset($s);

            Setting::set('iptv_cdn_slots', json_encode($slots), 'string');

            return back()->with('success', "Slot $slot token actualizado: $newToken");
        } catch (\Throwable $e) {
            Log::error("IPTV token refresh failed (slot $slot)", ['error' => $e->getMessage()]);
            return back()->with('error', "Slot $slot: error al contactar el servidor: " . $e->getMessage());
        }
    }

    // =========================================================================
    // IP ban management
    // =========================================================================

    public function banIp(Request $request)
    {
        $request->validate(['ip' => 'required|ip']);
        $ip = $request->input('ip');

        $banned = $this->jsonSetting('iptv_banned_ips');
        if (!in_array($ip, $banned, true)) {
            $banned[] = $ip;
            Setting::set('iptv_banned_ips', json_encode($banned), 'string');
        }

        return back()->with('success', "IP $ip baneada.");
    }

    public function unbanIp(Request $request)
    {
        $request->validate(['ip' => 'required|ip']);
        $ip = $request->input('ip');

        $banned = $this->jsonSetting('iptv_banned_ips');
        $banned = array_values(array_filter($banned, fn($b) => $b !== $ip));
        Setting::set('iptv_banned_ips', json_encode($banned), 'string');

        return back()->with('success', "IP $ip desbaneada.");
    }

    public function clearLog()
    {
        Setting::set('iptv_access_log', '[]', 'string');
        return back()->with('success', 'Log de accesos limpiado.');
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    private function getSlot1Url(): string
    {
        // Allow slot 1 URL to be configured via cdn_slots, fallback to hardcoded default
        $slots = $this->jsonSetting('iptv_cdn_slots');
        foreach ($slots as $s) {
            if ((int) ($s['slot'] ?? 0) === 1) {
                return $s['token_url'] ?? 'https://elchuno.serv00.net/gof/token.json';
            }
        }
        return 'https://elchuno.serv00.net/gof/token.json';
    }
}
