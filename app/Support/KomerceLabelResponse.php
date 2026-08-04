<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalizes Komerce print-label API payloads into something the admin UI
 * can act on (absolute PDF URL or raw PDF bytes).
 *
 * Real Collaborator responses often return a relative `data.path`
 * (e.g. `/storage/label-….pdf`) and/or `data.base_64`.
 */
final class KomerceLabelResponse
{
    /**
     * @param  array<string, mixed>  $response
     */
    public static function absoluteUrl(array $response, ?string $deliveryBaseUrl = null): ?string
    {
        foreach (['data.path', 'data.url', 'data.label_url', 'path', 'url'] as $key) {
            $value = data_get($response, $key);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $value = trim($value);

            if (preg_match('#^https?://#i', $value) === 1) {
                return $value;
            }

            if (str_starts_with($value, '/')) {
                $base = rtrim((string) ($deliveryBaseUrl ?? config('komerce.rajaongkir.delivery_base_url', '')), '/');

                return $base !== '' ? $base.$value : null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public static function pdfBinary(array $response): ?string
    {
        foreach (['data.base_64', 'data.base64', 'base_64', 'base64'] as $key) {
            $value = data_get($response, $key);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $decoded = base64_decode(trim($value), true);

            if ($decoded !== false && $decoded !== '') {
                return $decoded;
            }
        }

        return null;
    }
}
