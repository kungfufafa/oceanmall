<?php

declare(strict_types=1);

namespace App\Shipping;

/**
 * Maps checkout / Cost courier codes onto Shipping Delivery store-order names.
 *
 * Store Order expects `shipping` like "JNE" and `shipping_type` like "REG",
 * not the Cost API display name ("Jalur Nugraha Ekakurir") or a Shopper
 * service label stored in `name`.
 */
final class RajaOngkirCourier
{
    public static function deliveryName(?string $carrierCode, ?string $fallbackName = null): string
    {
        $code = strtolower(trim((string) $carrierCode));

        if (str_contains($code, ':')) {
            $code = trim(explode(':', $code, 2)[0] ?? $code);
        }

        $mapped = match ($code) {
            'jne' => 'JNE',
            'jnt', 'j&t' => 'JNT',
            'sicepat' => 'SICEPAT',
            'ide', 'idexpress' => 'IDE',
            'anteraja' => 'ANTERAJA',
            'pos' => 'POS',
            'tiki' => 'TIKI',
            'lion' => 'LION',
            'ninja' => 'NINJA',
            'wahana' => 'WAHANA',
            'rpx' => 'RPX',
            'ncs' => 'NCS',
            default => '',
        };

        if ($mapped !== '') {
            return $mapped;
        }

        $fallback = strtoupper(trim((string) $fallbackName));

        if ($fallback !== '' && ! in_array($fallback, ['STD', 'EZ', 'REG', 'YES', 'OKE', 'CTC', 'CTCYES', 'SIUNT', 'GOKIL', 'EXPRESS'], true)) {
            return $fallback;
        }

        return strtoupper($code);
    }

    public static function deliveryService(?string $service): string
    {
        $service = trim((string) $service);

        if (str_contains($service, ':')) {
            $service = trim(explode(':', $service, 2)[1] ?? $service);
        }

        return strtoupper($service);
    }
}
