<?php

namespace App\Services;

class M3uParser
{
    /**
     * Parse an M3U playlist and return only .mpd channel entries.
     *
     * Each returned channel array has:
     *   name       => string
     *   image      => string (may be empty)
     *   url        => string (.mpd URL)
     *   keys       => array of "keyid:keyvalue" strings
     *   referer    => string|null
     *   origin     => string|null
     *   user_agent => string|null  ← extracted from stream_headers per channel
     */
    public static function parse(string $m3u): array
    {
        $channels = [];
        $lines    = explode("\n", str_replace("\r\n", "\n", $m3u));

        $current = null;

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);

            if ($line === '' || $line === '#EXTM3U') {
                continue;
            }

            if (str_starts_with($line, '#EXTINF:')) {
                $current = [
                    'name'       => '',
                    'image'      => '',
                    'url'        => '',
                    'keys'       => [],
                    'referer'    => null,
                    'origin'     => null,
                    'user_agent' => null,
                ];

                // tvg-logo
                if (preg_match('/tvg-logo="([^"]*)"/', $line, $m)) {
                    $current['image'] = $m[1];
                }

                // Display name: text after last comma
                $lastComma = strrpos($line, ',');
                if ($lastComma !== false) {
                    $current['name'] = trim(substr($line, $lastComma + 1));
                }
                continue;
            }

            if ($current === null) {
                continue;
            }

            // -----------------------------------------------------------------
            // License keys
            // Formats handled:
            //   JSON:             {"keyid":"keyvalue"}
            //   Single plain:     keyid:keyvalue
            //   Multi-comma:      kid1:val1,kid2:val2,kid3:val3
            // -----------------------------------------------------------------
            if (str_starts_with($line, '#KODIPROP:inputstream.adaptive.license_key=')) {
                $raw = trim(substr($line, strlen('#KODIPROP:inputstream.adaptive.license_key=')));

                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    // JSON object {"kid":"val"}
                    foreach ($decoded as $kid => $kval) {
                        $current['keys'][] = $kid . ':' . $kval;
                    }
                } else {
                    // Plain text — may be comma-separated: kid1:val1,kid2:val2
                    $raw = trim($raw, '"');
                    foreach (explode(',', $raw) as $pair) {
                        $pair = trim($pair);
                        if ($pair !== '') {
                            $current['keys'][] = $pair;
                        }
                    }
                }
                continue;
            }

            // -----------------------------------------------------------------
            // Stream headers — extract referer, origin AND user-agent
            // Format: referer=https://...&origin=https://...&user-agent=Mozilla/...
            // -----------------------------------------------------------------
            if (str_starts_with($line, '#KODIPROP:inputstream.adaptive.stream_headers=')) {
                $headerStr = substr($line, strlen('#KODIPROP:inputstream.adaptive.stream_headers='));

                // parse_str uses PHP var naming rules; use manual split to be safe
                $parts = explode('&', $headerStr);
                foreach ($parts as $part) {
                    $eqPos = strpos($part, '=');
                    if ($eqPos === false) continue;

                    $k = strtolower(trim(substr($part, 0, $eqPos)));
                    $v = trim(substr($part, $eqPos + 1));

                    match ($k) {
                        'referer'    => $current['referer']    = urldecode($v),
                        'origin'     => $current['origin']     = urldecode($v),
                        'user-agent',
                        'useragent',
                        'user_agent' => $current['user_agent'] = urldecode($v),
                        default      => null,
                    };
                }
                continue;
            }

            // Any other # line: skip, keep accumulator
            if (str_starts_with($line, '#')) {
                continue;
            }

            // URL line — only .mpd
            if (str_ends_with(strtolower($line), '.mpd')) {
                $current['url'] = $line;
                $channels[]     = $current;
            }

            $current = null;
        }

        return $channels;
    }

    /**
     * Convert a parsed channel array into an encrypted Plooplayer station object.
     *
     * @param array  $channel        Output from parse()
     * @param string $cdnToken       Current x-tcdn-token (unencrypted, injected as-is)
     */
    public static function toStation(array $channel, string $cdnToken): array
    {
        $station = [
            'name'   => $channel['name'],
            'image'  => $channel['image'],
            'ploorl' => PlooplayerEncryption::encrypt($channel['url']),
        ];

        // License keys — each pair encrypted separately
        if (!empty($channel['keys'])) {
            $station['qqs'] = [
                'qs'       => array_map(
                    fn(string $k) => PlooplayerEncryption::encrypt($k),
                    $channel['keys']
                ),
                'isbase64' => false,
            ];
        }

        // Referer / Origin
        if (!empty($channel['referer'])) {
            $station['ploore'] = PlooplayerEncryption::encrypt($channel['referer']);
        }
        if (!empty($channel['origin'])) {
            $station['ploori'] = PlooplayerEncryption::encrypt($channel['origin']);
        }

        // CDN token — plain text in headers
        $station['headers'] = ['x-tcdn-token' => $cdnToken];

        // Per-channel user-agent — encrypted
        if (!empty($channel['user_agent'])) {
            $station['ploousag'] = PlooplayerEncryption::encrypt($channel['user_agent']);
        }

        return $station;
    }
}
