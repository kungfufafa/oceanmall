<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Core\Models\Zone;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->warn(PHP_EOL.'Creating payment methods...');

        // COD kept for ops flexibility but off by default — prepaid Komerce is the production path.
        PaymentMethod::query()->updateOrCreate(
            ['slug' => 'cod'],
            [
                'title' => 'Cash on delivery',
                'description' => 'Bayar di tempat saat barang diterima.',
                'is_enabled' => false,
                'driver' => 'manual',
            ],
        );

        PaymentMethod::query()->updateOrCreate(
            ['slug' => 'komerce-va-bca'],
            [
                'title' => 'BCA Virtual Account',
                'description' => 'Bayar via BCA Virtual Account (Komerce).',
                'is_enabled' => true,
                'driver' => 'komerce',
                'metadata' => json_encode([
                    'payment_type' => 'bank_transfer',
                    'channel_code' => 'BCA',
                ], JSON_THROW_ON_ERROR),
            ],
        );

        PaymentMethod::query()->updateOrCreate(
            ['slug' => 'komerce-va-bri'],
            [
                'title' => 'BRI Virtual Account',
                'description' => 'Bayar via BRI Virtual Account (Komerce).',
                'is_enabled' => true,
                'driver' => 'komerce',
                'metadata' => json_encode([
                    'payment_type' => 'bank_transfer',
                    'channel_code' => 'BRIVA',
                ], JSON_THROW_ON_ERROR),
            ],
        );

        PaymentMethod::query()->updateOrCreate(
            ['slug' => 'komerce-va-mandiri'],
            [
                'title' => 'Mandiri Virtual Account',
                'description' => 'Bayar via Mandiri Virtual Account (Komerce).',
                'is_enabled' => true,
                'driver' => 'komerce',
                'metadata' => json_encode([
                    'payment_type' => 'bank_transfer',
                    'channel_code' => 'MANDIRI',
                ], JSON_THROW_ON_ERROR),
            ],
        );

        PaymentMethod::query()->updateOrCreate(
            ['slug' => 'komerce-va-bni'],
            [
                'title' => 'BNI Virtual Account',
                'description' => 'Bayar via BNI Virtual Account (Komerce).',
                'is_enabled' => true,
                'driver' => 'komerce',
                'metadata' => json_encode([
                    'payment_type' => 'bank_transfer',
                    'channel_code' => 'BNI',
                ], JSON_THROW_ON_ERROR),
            ],
        );

        PaymentMethod::query()->updateOrCreate(
            ['slug' => 'komerce-va-permata'],
            [
                'title' => 'Permata Virtual Account',
                'description' => 'Bayar via Permata Virtual Account (Komerce).',
                'is_enabled' => true,
                'driver' => 'komerce',
                'metadata' => json_encode([
                    'payment_type' => 'bank_transfer',
                    'channel_code' => 'PERMATA',
                ], JSON_THROW_ON_ERROR),
            ],
        );

        PaymentMethod::query()->updateOrCreate(
            ['slug' => 'komerce-qris'],
            [
                'title' => 'QRIS',
                'description' => 'Bayar via QRIS (GoPay, OVO, Dana, ShopeePay, m-banking).',
                'is_enabled' => true,
                'driver' => 'komerce',
                'metadata' => json_encode([
                    'payment_type' => 'qris',
                ], JSON_THROW_ON_ERROR),
            ],
        );

        // Legacy combined slug: keep row but disabled so ZoneSeeder upgrades cleanly.
        PaymentMethod::query()->updateOrCreate(
            ['slug' => 'komerce-va-qris'],
            [
                'title' => 'Virtual Account / QRIS (Komerce)',
                'description' => 'Legacy combined method — use komerce-va-* or komerce-qris.',
                'is_enabled' => false,
                'driver' => 'komerce',
                'metadata' => json_encode([
                    'payment_type' => 'bank_transfer',
                    'channel_code' => 'BCA',
                ], JSON_THROW_ON_ERROR),
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

        // Attach all enabled payment methods to existing zones
        $enabledIds = PaymentMethod::query()->where('is_enabled', true)->pluck('id');
        foreach (Zone::all() as $zone) {
            $zone->paymentMethods()->syncWithoutDetaching($enabledIds);
        }

        $this->command->info('Payment methods created successfully.');
    }
}
