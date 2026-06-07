<?php

namespace AgenDAV\Session;

use RuntimeException;
use SodiumException;

/**
 * Authenticated symmetric encryption for short secrets (the CalDAV password)
 * stored in the session blob. Uses libsodium's secretbox (XSalsa20+Poly1305).
 *
 * The session backend (PdoSessionHandler) keeps an authenticated user's
 * password serialised in the database. Encrypting it at rest with a key that
 * lives outside the database means a read-only DB compromise does not
 * directly leak CalDAV credentials.
 */
class PasswordCipher
{
    public function __construct(private string $key)
    {
        if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException(sprintf(
                'PasswordCipher key must be %d bytes, got %d',
                SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
                strlen($key)
            ));
        }
    }

    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plaintext, $nonce, $this->key);
        return base64_encode($nonce . $cipher);
    }

    /**
    * Returns null if the payload is malformed or fails authentication.
    */
    public function decrypt(string $payload): ?string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }
        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        try {
            $plain = sodium_crypto_secretbox_open($cipher, $nonce, $this->key);
        } catch (SodiumException) {
            return null;
        }
        return $plain === false ? null : $plain;
    }
}
