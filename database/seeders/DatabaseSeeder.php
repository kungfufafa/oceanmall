<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Shopper\Core\Database\Seeders\ShopperSeeder;
use Shopper\Database\Seeders\AuthTableSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AuthTableSeeder::class,
            ShopperSeeder::class,
            StoreConfigSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            CollectionSeeder::class,
            AttributeSeeder::class,
            ProductSeeder::class,
            PaymentMethodSeeder::class,
            CarrierSeeder::class,
            ZoneSeeder::class,
            TaxSeeder::class,
            CustomerSeeder::class,
            ReviewSeeder::class,
            DiscountSeeder::class,
            OrderSeeder::class,
            // BlogSeeder skipped: OceanMall belum punya model/migration Blog*.
            // Legacy optional: StorefrontDemoSeeder::class,
        ]);
    }
}
