<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Currency;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Setting;

class StoreConfigSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->warn(PHP_EOL.'Creating admin user...');

        /** @var User $admin */
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@oceanmall.test'],
            [
                'first_name' => 'Admin',
                'last_name' => 'OceanMall',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        );

        /** @var string $role */
        $role = config('shopper.admin.roles.admin');
        $admin->assignRole($role);

        $this->command->info('Admin user created (admin@oceanmall.test / password123).');

        $this->command->warn(PHP_EOL.'Creating customer user...');

        /** @var User $customer */
        $customer = User::query()->updateOrCreate(
            ['email' => 'customer@oceanmall.test'],
            [
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        );

        /** @var string $userRole */
        $userRole = config('shopper.admin.roles.user');
        if (! $customer->hasRole($userRole)) {
            $customer->assignRole($userRole);
        }

        $this->command->info('Customer user created (customer@oceanmall.test / password123).');

        $this->command->warn(PHP_EOL.'Configuring store settings...');

        $indonesiaId = Country::query()->where('cca2', 'ID')->value('id');
        $idrId = Currency::query()->where('code', 'IDR')->value('id');

        $settings = [
            'name' => 'OceanMall',
            'email' => 'info@oceanmall.test',
            'about' => 'OceanMall — Belanja gadget & lifestyle terpercaya. Produk original, pengiriman ke seluruh Indonesia, pembayaran aman.',
            'phone_number' => '0216296612',
            'street_address' => 'Jl. Tuparev No. 109F, Kertawinangun, Kedawung',
            'postal_code' => '45153',
            'state' => 'Jawa Barat',
            'city' => 'Cirebon',
            'country_id' => $indonesiaId,
            'default_currency_id' => $idrId,
            'currencies' => [$idrId],
            'facebook_link' => null,
            'instagram_link' => 'https://instagram.com/completeselular',
            'twitter_link' => null,
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], [
                'value' => $value,
                'locked' => true,
                'display_name' => Setting::lockedAttributesDisplayName($key),
            ]);
        }

        Inventory::query()->updateOrCreate(
            ['code' => 'oceanmall-cirebon'],
            [
                'name' => 'OceanMall Cirebon',
                'email' => 'info@oceanmall.test',
                'street_address' => 'Jl. Tuparev No. 109F, Kertawinangun, Kedawung',
                'postal_code' => '45153',
                'city' => 'Cirebon',
                'state' => 'Jawa Barat',
                'is_default' => true,
                'country_id' => $indonesiaId,
                // KERTAWINANGUN, KEDAWUNG, CIREBON — RajaOngkir domestic destination id
                'rajaongkir_origin_id' => '17248',
            ],
        );

        $this->command->info('Store settings configured.');
    }
}
