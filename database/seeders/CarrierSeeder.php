<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Shopper\Core\Models\Carrier;
use Shopper\Core\Models\Zone;

final class CarrierSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->warn(PHP_EOL.'Creating carriers...');

        $carriers = [
            [
                'slug' => 'jnt',
                'name' => 'J&T Express',
                'description' => 'Pengiriman ekspres via J&T (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'jne',
                'name' => 'JNE Express',
                'description' => 'Pengiriman ekspres via JNE (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'sicepat',
                'name' => 'SiCepat Ekspres',
                'description' => 'Pengiriman ekspres via SiCepat (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'ide',
                'name' => 'IDexpress',
                'description' => 'Pengiriman ekspres via IDexpress (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'anteraja',
                'name' => 'Anteraja',
                'description' => 'Pengiriman ekspres via Anteraja (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'pos',
                'name' => 'POS Indonesia',
                'description' => 'Pengiriman pos via POS Indonesia (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'tiki',
                'name' => 'TIKI',
                'description' => 'Pengiriman ekspres via TIKI (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'lion',
                'name' => 'Lion Parcel',
                'description' => 'Pengiriman via Lion Parcel (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'ninja',
                'name' => 'Ninja Xpress',
                'description' => 'Pengiriman via Ninja Xpress (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'wahana',
                'name' => 'Wahana Express',
                'description' => 'Pengiriman via Wahana Express (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'rpx',
                'name' => 'RPX Holding',
                'description' => 'Pengiriman via RPX Holding (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'ncs',
                'name' => 'Nusantara Card Semesta',
                'description' => 'Pengiriman via NCS (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'sap',
                'name' => 'SAP Express',
                'description' => 'Pengiriman via SAP Express (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'gosend',
                'name' => 'GoSend',
                'description' => 'Pengiriman via GoSend (Komerce / RajaOngkir)',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
            [
                'slug' => 'manual',
                'name' => 'Kurir Toko / Pengiriman Lokal',
                'description' => 'Pengiriman manual oleh kurir toko',
                'is_enabled' => true,
                'driver' => 'manual',
            ],
        ];

        foreach ($carriers as $data) {
            Carrier::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }

        // Cleanup legacy generic carrier if present
        Carrier::query()->where('slug', 'rajaongkir')->delete();

        // Populate carrier logos and metadata
        \Illuminate\Support\Facades\Artisan::call('komerce:sync-carriers');

        // Attach enabled carriers to existing zones
        $enabledIds = Carrier::query()->where('is_enabled', true)->pluck('id');
        foreach (Zone::all() as $zone) {
            $zone->carriers()->syncWithoutDetaching($enabledIds);
        }

        $this->command->info('Carriers created successfully.');
    }
}
