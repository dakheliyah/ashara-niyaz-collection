<?php

namespace App\Services;

use RuntimeException;

class ItsTokenCipher
{
    public static function encrypt(string $plaintext): string
    {
        $key = config('its_onelogin.encryption_key');
        if (empty($key)) {
            throw new RuntimeException('ITS_ENCRYPTION_KEY is not set in the environment.');
        }

        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('ITS token encryption failed.');
        }

        return base64_encode($iv.$encrypted);
    }

    public static function decrypt(string $encrypted): ?string
    {
        $key = config('its_onelogin.encryption_key');
        if (empty($key)) {
            return null;
        }

        $decoded = base64_decode($encrypted, true);
        if ($decoded === false) {
            return null;
        }

        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        if (strlen($decoded) <= $ivLength) {
            return null;
        }

        $iv = substr($decoded, 0, $ivLength);
        $cipherText = substr($decoded, $ivLength);
        $decrypted = openssl_decrypt($cipherText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $decrypted === false ? null : $decrypted;
    }
}
