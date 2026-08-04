<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Komerce Payment callback signature (HMAC-SHA256) as documented at
 * https://rajaongkir.com/docs/payment-api/getting-started/callback-handling
 *
 * The X-Callback-Api-Key header carries HMAC(raw JSON body, callback_API_KEY),
 * not the plain secret itself.
 */
final class KomerceCallbackSignature
{
    public static function sign(string $rawBody, string $secret): string
    {
        return hash_hmac('sha256', $rawBody, $secret);
    }

    public static function isValid(string $rawBody, string $secret, string $provided): bool
    {
        if ($secret === '' || $provided === '') {
            return false;
        }

        return hash_equals(self::sign($rawBody, $secret), $provided);
    }
}
