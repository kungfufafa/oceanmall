<?php

declare(strict_types=1);

namespace Tests\Feature\Cpanel;

use App\Livewire\Shopper\InventoryForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Inventory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class InventoryRajaOngkirOriginTest extends TestCase
{
    use RefreshDatabase;

    public function test_shopper_location_form_exposes_and_saves_rajaongkir_origin_id(): void
    {
        $this->configureShopperCpanel();

        $admin = User::factory()->create();
        Role::query()->firstOrCreate([
            'name' => config('shopper.admin.roles.admin'),
            'guard_name' => 'web',
        ]);
        $admin->assignRole(config('shopper.admin.roles.admin'));

        $country = Country::factory()->create(['cca2' => 'ID']);
        $inventory = Inventory::factory()->create([
            'country_id' => $country->id,
            'name' => 'Gudang Jakarta',
            'rajaongkir_origin_id' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(InventoryForm::class, ['inventory' => $inventory])
            ->set('data.priority', 0)
            ->set('data.rajaongkir_origin_id', '17248')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertSame('17248', (string) $inventory->fresh()->getAttribute('rajaongkir_origin_id'));
    }
}
