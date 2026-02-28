<?php

namespace App\Services;

class PlooplayerEncryption
{
    private const AES_KEY    = "Mich@elJ@ckson12";   // 16 bytes
    private const CHACHA_KEY = "12MichaelJackson";   // 16 bytes
    private const AES_IV     = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

    // ChaCha20 initial constants for 128-bit key: "expand 16-byte k" as LE uint32
    private const CONSTANTS = [0x61707865, 0x3120646e, 0x79622d36, 0x6b206574];

    /**
     * Encrypt a plaintext string using AES-128-CBC + ChaCha20 (original DJB, 8-byte nonce).
     * Each call produces a different ciphertext because the nonce is random.
     */
    public static function encrypt(string $plaintext): string
    {
        // 1-2: AES-128-CBC encrypt (PKCS7 padding) → raw bytes → Base64 string
        $aesBytes     = openssl_encrypt($plaintext, 'AES-128-CBC', self::AES_KEY, OPENSSL_RAW_DATA, self::AES_IV);
        $aesB64String = base64_encode($aesBytes);

        // 3-4: Generate random 8-byte nonce and ChaCha20-XOR the Base64 bytes
        $nonce          = random_bytes(8);
        $chaCipherBytes = self::chacha20Xor($aesB64String, self::CHACHA_KEY, $nonce);

        // 5: nonce || ciphertext → Base64
        return base64_encode($nonce . $chaCipherBytes);
    }

    /**
     * Decrypt a ciphertext produced by encrypt().
     */
    public static function decrypt(string $ciphertext): string
    {
        $bytes          = base64_decode($ciphertext);
        $nonce          = substr($bytes, 0, 8);
        $chaCipherBytes = substr($bytes, 8);

        $aesB64String = self::chacha20Xor($chaCipherBytes, self::CHACHA_KEY, $nonce);
        $aesBytes     = base64_decode($aesB64String);

        return openssl_decrypt($aesBytes, 'AES-128-CBC', self::AES_KEY, OPENSSL_RAW_DATA, self::AES_IV);
    }

    // -------------------------------------------------------------------------
    // ChaCha20 (original DJB, 64-bit nonce, 64-bit counter, "expand 16-byte k")
    // -------------------------------------------------------------------------

    private static function rotl32(int $v, int $c): int
    {
        $v &= 0xFFFFFFFF;
        return (($v << $c) | ($v >> (32 - $c))) & 0xFFFFFFFF;
    }

    private static function quarterRound(array &$s, int $a, int $b, int $c, int $d): void
    {
        $s[$a] = ($s[$a] + $s[$b]) & 0xFFFFFFFF;
        $s[$d] = self::rotl32($s[$d] ^ $s[$a], 16);

        $s[$c] = ($s[$c] + $s[$d]) & 0xFFFFFFFF;
        $s[$b] = self::rotl32($s[$b] ^ $s[$c], 12);

        $s[$a] = ($s[$a] + $s[$b]) & 0xFFFFFFFF;
        $s[$d] = self::rotl32($s[$d] ^ $s[$a], 8);

        $s[$c] = ($s[$c] + $s[$d]) & 0xFFFFFFFF;
        $s[$b] = self::rotl32($s[$b] ^ $s[$c], 7);
    }

    private static function chacha20Block(array $state): string
    {
        $w = $state;

        for ($i = 0; $i < 10; $i++) {
            // Column rounds
            self::quarterRound($w, 0, 4, 8, 12);
            self::quarterRound($w, 1, 5, 9, 13);
            self::quarterRound($w, 2, 6, 10, 14);
            self::quarterRound($w, 3, 7, 11, 15);
            // Diagonal rounds
            self::quarterRound($w, 0, 5, 10, 15);
            self::quarterRound($w, 1, 6, 11, 12);
            self::quarterRound($w, 2, 7, 8, 13);
            self::quarterRound($w, 3, 4, 9, 14);
        }

        $out = '';
        for ($i = 0; $i < 16; $i++) {
            $out .= pack('V', ($w[$i] + $state[$i]) & 0xFFFFFFFF);
        }

        return $out;
    }

    /**
     * ChaCha20 stream XOR (encrypt = decrypt since it's a stream cipher).
     * key: 16-byte string (will be repeated to fill the 32-byte key slots in the state).
     * nonce: 8-byte string.
     * counter: starts at 0.
     */
    private static function chacha20Xor(string $data, string $key, string $nonce): string
    {
        // Unpack key (16 bytes repeated → 8 LE uint32 words) and nonce (8 bytes → 2 words)
        $keyWords   = array_values(unpack('V8', $key . $key));
        $nonceWords = array_values(unpack('V2', $nonce));

        $length    = strlen($data);
        $keystream = '';
        $blockNum  = 0;

        while (strlen($keystream) < $length) {
            $state = array_merge(
                self::CONSTANTS,
                $keyWords,             // words 4-11: 16-byte key repeated
                [$blockNum, 0],        // words 12-13: 64-bit counter (lo, hi)
                $nonceWords            // words 14-15: 64-bit nonce
            );
            $keystream .= self::chacha20Block($state);
            $blockNum++;
        }

        // XOR data with keystream (PHP string XOR is byte-by-byte)
        return $data ^ substr($keystream, 0, $length);
    }
}
