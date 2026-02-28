<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\M3uParser;
use App\Services\PlooplayerEncryption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IptvAdminController extends Controller
{
    private const TOKEN_ENDPOINT = 'https://elchuno.serv00.net/gof/token.json';

    // =========================================================================
    // Index page
    // =========================================================================

    /** Setting::get() may return an already-decoded array (type='json') or a raw string. */
    private function jsonSetting(string $key, array $default = []): array
    {
        $value = Setting::get($key, null);
        if ($value === null)       return $default;
        if (is_array($value))      return $value;
        return json_decode($value, true) ?: $default;
    }

    public function index()
    {
        $channels   = $this->jsonSetting('iptv_channels_json');
        $bannedIps  = $this->jsonSetting('iptv_banned_ips');
        $accessLog  = $this->jsonSetting('iptv_access_log');
        $settings   = [
            'list_name'        => Setting::get('iptv_list_name',        'Plooplayer VIP'),
            'group_name'       => Setting::get('iptv_group_name',       'Plooplayer VIP'),
            'list_pl'          => Setting::get('iptv_list_pl',          '7PxQeW7s7VBU+8vW8rN0jG7+spPJZyYEYhzB4VivSv0='),
            'user_agent'       => Setting::get('iptv_user_agent',       ''),
            'max_ips_per_day'  => Setting::get('iptv_max_ips_per_day',  '10'),
            'current_token'    => Setting::get('iptv_current_token',    ''),
        ];

        return view('admin.iptv.index', compact('channels', 'bannedIps', 'accessLog', 'settings'));
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
            'user_agent'      => 'nullable|string|max:500',
            'max_ips_per_day' => 'required|integer|min:1|max:1000',
        ]);

        Setting::set('iptv_list_name',       $data['list_name']);
        Setting::set('iptv_group_name',      $data['group_name']);
        Setting::set('iptv_list_pl',         $data['list_pl']);
        Setting::set('iptv_user_agent',      $data['user_agent'] ?? '');
        Setting::set('iptv_max_ips_per_day', (string) $data['max_ips_per_day']);

        return back()->with('success', 'Configuración guardada.');
    }

    // =========================================================================
    // M3U parse preview (returns JSON — called via JS fetch)
    // =========================================================================

    public function parseM3u(Request $request)
    {
        $request->validate(['m3u' => 'required|string']);

        $parsed = M3uParser::parse($request->input('m3u'));

        return response()->json([
            'count'    => count($parsed),
            'channels' => $parsed,
        ]);
    }

    // =========================================================================
    // Save encrypted channel list to DB
    // =========================================================================

    public function saveChannels(Request $request)
    {
        $request->validate(['m3u' => 'required|string']);

        $parsed = M3uParser::parse($request->input('m3u'));

        if (empty($parsed)) {
            return back()->with('error', 'No se encontraron canales MPD en el M3U.');
        }

        $token  = Setting::get('iptv_current_token', '');
        $ua     = Setting::get('iptv_user_agent', '');

        // Pre-encrypt user-agent once (nonce is random so it changes every save — that's fine)
        $encryptedUa = $ua !== '' ? PlooplayerEncryption::encrypt($ua) : null;

        $stations = [];
        foreach ($parsed as $channel) {
            $stations[] = M3uParser::toStation($channel, $token, $encryptedUa);
        }

        Setting::set('iptv_channels_json', json_encode($stations), 'string');
        Setting::set('iptv_channels_updated_at', now()->toDateTimeString());

        Log::info('IPTV channel list updated', ['count' => count($stations)]);

        return back()->with('success', count($stations) . ' canales guardados correctamente.');
    }

    // =========================================================================
    // Refresh x-tcdn-token from external server
    // =========================================================================

    public function refreshToken()
    {
        try {
            $response = Http::timeout(10)->get(self::TOKEN_ENDPOINT);

            if (!$response->successful()) {
                return back()->with('error', 'Error al obtener el token: HTTP ' . $response->status());
            }

            $newToken = trim($response->body());

            if (empty($newToken)) {
                return back()->with('error', 'El servidor devolvió un token vacío.');
            }

            Setting::set('iptv_current_token', $newToken);

            // Re-encrypt all channels with the new token
            $this->reencryptWithToken($newToken);

            return back()->with('success', 'Token actualizado: ' . $newToken);
        } catch (\Throwable $e) {
            Log::error('IPTV token refresh failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al contactar el servidor de tokens: ' . $e->getMessage());
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

    /**
     * Update only the `headers.x-tcdn-token` field in every station
     * without re-encrypting any other fields (faster than full re-encrypt).
     */
    private function reencryptWithToken(string $newToken): void
    {
        $stations = $this->jsonSetting('iptv_channels_json');

        foreach ($stations as &$station) {
            $station['headers']['x-tcdn-token'] = $newToken;
        }
        unset($station);

        Setting::set('iptv_channels_json', json_encode($stations), 'string');
    }
}
