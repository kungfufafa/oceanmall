<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Shopper\Core\Models\Carrier;

final class CarrierSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->warn(PHP_EOL.'Creating carriers...');

        Carrier::query()->firstOrCreate(
            ['slug' => 'rajaongkir'],
            [
                'name' => 'Ekspedisi',
                'is_enabled' => true,
                'driver' => 'rajaongkir',
            ],
        );

        Carrier::query()->firstOrCreate(
            ['slug' => 'manual'],
            [
                'name' => 'Manual',
                'is_enabled' => true,
                'driver' => null,
            ],
        );

        $this->command->info('Carriers created successfully.');
    }
}
