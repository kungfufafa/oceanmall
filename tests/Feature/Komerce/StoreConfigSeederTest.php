<?php

declare(strict_types=1);

namespace Tests\Feature\Komerce;

use Database\Seeders\StoreConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Currency;
use Shopper\Core\Models\Inventory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class StoreConfigSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_oceanmall_cirebon_seed_persists_repo_warehouse_pin(): void
    {
        Country::factory()->create(['cca2' => 'ID']);
        Currency::factory()->create([
            'name' => 'Indonesian Rupiah',
            'code' => 'IDR',
            'symbol' => 'Rp',
            'format' => 'Rp1.234',
        ]);

        Role::query()->firstOrCreate([
            'name' => config('shopper.admin.roles.admin'),
            'guard_name' => 'web',
        ]);
        Role::query()->firstOrCreate([
            'name' => config('shopper.admin.roles.user'),
            'guard_name' => 'web',
        ]);

        $this->seed(StoreConfigSeeder::class);

        $inventory = Inventory::query()->where('code', 'oceanmall-cirebon')->first();

        $this->assertNotNull($inventory);
        $this->assertSame('OceanMall Cirebon', $inventory->name);
        $this->assertSame('17248', (string) $inventory->getAttribute('rajaongkir_origin_id'));
        $this->assertEqualsWithDelta(-6.7366, (float) $inventory->getAttribute('latitude'), 0.0001);
        $this->assertEqualsWithDelta(108.5414, (float) $inventory->getAttribute('longitude'), 0.0001);
    }
}
