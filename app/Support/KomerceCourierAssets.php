<?php

declare(strict_types=1);

namespace App\Support;

final class KomerceCourierAssets
{
    private const CDN_BASE = 'https://storage.googleapis.com/komship-bucket/shipment';

    /**
     * Official Komerce / Komship CDN logos for all supported expeditions.
     *
     * @var array<string, string>
     */
    private const LOGOS = [
        'jne' => self::CDN_BASE.'/jne-logo.png',
        'jnt' => self::CDN_BASE.'/jnt-logo.png',
        'j&t' => self::CDN_BASE.'/jnt-logo.png',
        'sicepat' => self::CDN_BASE.'/sicepat-logo.png',
        'idexpress' => self::CDN_BASE.'/idexpress-logo.png',
        'ide' => self::CDN_BASE.'/idexpress-logo.png',
        'sap' => self::CDN_BASE.'/sap-express-logo.png',
        'ninja' => self::CDN_BASE.'/ninja-express-logo.png',
        'anteraja' => self::CDN_BASE.'/anteraja-logo.png',
        'pos' => self::CDN_BASE.'/pos-logo.png',
        'tiki' => self::CDN_BASE.'/tiki-logo.png',
        'lion' => self::CDN_BASE.'/lion-logo.png',
        'wahana' => self::CDN_BASE.'/wahana-logo.png',
        'gosend' => self::CDN_BASE.'/gosend-logo.png',
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
