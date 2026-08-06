<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\KomerceCourierAssets;
use Illuminate\Console\Command;
use Shopper\Core\Models\Carrier;

final class SyncKomerceCarriers extends Command
{
    protected $signature = 'komerce:sync-carriers';

    protected $description = 'Sync Komerce/RajaOngkir carrier logos and metadata into the database carriers table';

    public function handle(): int
    {
        $defaultExpeditions = [
            'jne' => 'JNE Express',
            'jnt' => 'J&T Express',
            'sicepat' => 'SiCepat Ekspres',
            'ide' => 'IDexpress',
            'sap' => 'SAP Express',
            'ninja' => 'Ninja Xpress',
            'gosend' => 'GoSend',
            'lion' => 'Lion Parcel',
        ];

        foreach ($defaultExpeditions as $slug => $name) {
            Carrier::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'driver' => 'rajaongkir',
                    'is_enabled' => true,
                ],
            );
        }

        $activatedSlugs = ['jne', 'jnt', 'sicepat', 'ide', 'sap', 'ninja', 'gosend', 'lion', 'manual'];

        $carriers = Carrier::query()->get();
        $updated = 0;

        foreach ($carriers as $carrier) {
            $logoUrl = KomerceCourierAssets::logoUrl($carrier->slug);
            if ($logoUrl === null && $carrier->slug === 'ide') {
                $logoUrl = KomerceCourierAssets::logoUrl('idexpress');
            }

            $isActive = in_array(strtolower((string) $carrier->slug), $activatedSlugs, true);

            $metadata = is_array($carrier->metadata) ? $carrier->metadata : [];
            if ($logoUrl !== null) {
                $metadata['logo_url'] = $logoUrl;
            }

            $carrier->forceFill([
                'metadata' => $metadata,
                'is_enabled' => $isActive,
            ])->save();

            $updated++;
        }

        $this->info("Successfully synced all activated Komerce expeditions ({$updated} logos updated) in database.");

        return self::SUCCESS;
    }
}
