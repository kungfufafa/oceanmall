<?php

declare(strict_types=1);

namespace App\Support;

final class KomerceCourierAssets
{
    /**
     * Official Komerce / Komship CDN logos for all supported expeditions.
     *
     * @var array<string, string>
     */
    private const LOGOS = [
        'jne' => 'https://storage.googleapis.com/komship-bucket/shipment/jne-logo.png',
        'jnt' => 'https://storage.googleapis.com/komerce/assets/illustration/JNT.png',
        'j&t' => 'https://storage.googleapis.com/komerce/assets/illustration/JNT.png',
        'sicepat' => 'https://storage.googleapis.com/komship-bucket/shipment/sicepat-logo.png',
        'idexpress' => 'https://storage.googleapis.com/komship-bucket/shipment/idexpress-logo.png',
        'ide' => 'https://storage.googleapis.com/komship-bucket/shipment/idexpress-logo.png',
        'sap' => 'https://storage.googleapis.com/komship-bucket/shipment/sap-express-logo.png',
        'ninja' => 'https://storage.googleapis.com/komerce/assets/illustration/NINJA.png',
        'anteraja' => 'https://storage.googleapis.com/komship-bucket/shipment/anteraja-logo.png',
        'pos' => 'https://storage.googleapis.com/komship-bucket/shipment/pos-logo.png',
        'tiki' => 'https://storage.googleapis.com/komship-bucket/shipment/tiki-logo.png',
        'lion' => 'https://storage.googleapis.com/komerce/assets/logo/lion-parcel.svg',
        'wahana' => 'https://storage.googleapis.com/komship-bucket/shipment/wahana-logo.png',
        'gosend' => 'https://storage.googleapis.com/komerce/assets/illustration/gosend.svg',
    ];

    public static function logoUrl(?string $carrierCode): ?string
    {
        if ($carrierCode === null || trim($carrierCode) === '') {
            return null;
        }

        $normalized = strtolower(trim($carrierCode));

        return self::LOGOS[$normalized] ?? null;
    }
}
