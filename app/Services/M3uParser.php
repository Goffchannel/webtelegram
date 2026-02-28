<?php

namespace App\Services;

class M3uParser
{
    /**
     * Parse an M3U playlist and return only .mpd channel entries.
     *
     * Each returned channel array has:
     *   name    => string
     *   image   => string (may be empty)
     *   url     => string (.mpd URL)
     *   keys    => array of "keyid:keyvalue" strings (one per license_key line)
     *   referer => string|null
     *   origin  => string|null
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
                // Start accumulating a new channel entry
                $current = [
                    'name'    => '',
                    'image'   => '',
                    'url'     => '',
                    'keys'    => [],
                    'referer' => null,
                    'origin'  => null,
                ];

                // Extract tvg-logo
                if (preg_match('/tvg-logo="([^"]*)"/', $line, $m)) {
                    $current['image'] = $m[1];
                }

                // Extract display name (text after last comma)
                $lastComma = strrpos($line, ',');
                if ($lastComma !== false) {
                    $current['name'] = trim(substr($line, $lastComma + 1));
                }
                continue;
            }

            if ($current === null) {
                continue;
            }

            if (str_starts_with($line, '#KODIPROP:inputstream.adaptive.license_key=')) {
                $raw = substr($line, strlen('#KODIPROP:inputstream.adaptive.license_key='));
                // The value can be either a JSON object {"keyid":"keyvalue"}
                // or a plain "keyid:keyvalue" string — handle both.
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    // JSON object: convert to keyid:keyvalue
                    foreach ($decoded as $kid => $kval) {
                        $current['keys'][] = $kid . ':' . $kval;
                    }
                } else {
                    // Already plain text
                    $plain = trim($raw, '"');
                    if ($plain !== '') {
                        $current['keys'][] = $plain;
                    }
                }
                continue;
            }

            if (str_starts_with($line, '#KODIPROP:inputstream.adaptive.stream_headers=')) {
                $headerStr = substr($line, strlen('#KODIPROP:inputstream.adaptive.stream_headers='));
                parse_str($headerStr, $headers);
                $current['referer'] = $headers['referer'] ?? null;
                $current['origin']  = $headers['origin'] ?? null;
                continue;
            }

            // Any other # line: skip but keep current accumulator
            if (str_starts_with($line, '#')) {
                continue;
            }

            // URL line — only accept .mpd streams
            if (str_ends_with(strtolower($line), '.mpd')) {
                $current['url'] = $line;
                $channels[]     = $current;
            }
            // Discard .m3u8 and other formats

            $current = null;
        }

        return $channels;
    }

    /**
     * Convert a parsed channel array into the encrypted Plooplayer station object.
     * The $token passed in headers is the current x-tcdn-token (unencrypted).
     * The $encryptedUserAgent is pre-encrypted (or pass null to omit the field).
     */
    public static function toStation(array $channel, string $cdnToken, ?string $encryptedUserAgent = null): array
    {
        $station = [
            'name'   => $channel['name'],
            'image'  => $channel['image'],
            'ploorl' => PlooplayerEncryption::encrypt($channel['url']),
        ];

        // License keys
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

        // CDN token goes in plain inside headers
        $station['headers'] = ['x-tcdn-token' => $cdnToken];

        // Pre-encrypted user-agent
        if ($encryptedUserAgent !== null) {
            $station['ploousag'] = $encryptedUserAgent;
        }

        return $station;
    }
}
