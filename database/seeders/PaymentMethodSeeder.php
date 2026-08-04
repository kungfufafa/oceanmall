<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Shopper\Core\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->warn(PHP_EOL.'Creating payment methods...');

        PaymentMethod::query()->updateOrCreate(
            ['slug' => 'cod'],
            [
                'title' => 'Cash on delivery',
                'description' => 'Bayar di tempat saat barang diterima.',
                'is_enabled' => true,
                'driver' => 'manual',
            ],
        );

        PaymentMethod::query()->updateOrCreate(
            ['slug' => 'komerce-va-qris'],
            [
                'title' => 'Virtual Account / QRIS (Komerce)',
                'description' => 'Bayar via Virtual Account bank atau QRIS melalui Komerce.',
                'is_enabled' => true,
                'driver' => 'komerce',
            ],
        );

        // Keep Stripe available but off by default (OceanMall uses Komerce).
        PaymentMethod::query()->updateOrCreate(
            ['slug' => 'stripe'],
            [
                'title' => 'Stripe',
                'description' => 'Kartu kredit/debit via Stripe (opsional).',
                'is_enabled' => false,
                'driver' => 'stripe',
            ],
        );

        $this->command->info('Payment methods created successfully.');
    }
}
