<?php

declare(strict_types=1);

namespace Tests\Feature\Cpanel;

use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class CpanelOrderDetailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->configureShopperCpanel();

        $admin = User::factory()->create();
        Role::query()->firstOrCreate([
            'name' => config('shopper.admin.roles.admin'),
            'guard_name' => 'web',
        ]);
        $admin->assignRole(config('shopper.admin.roles.admin'));

        return $admin;
    }

    public function test_admin_path_is_not_a_backoffice_route(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create(['currency_code' => 'IDR']);

        $this->actingAs($admin)
            ->get('/admin/orders/'.$order->id)
            ->assertNotFound();
    }

    public function test_cpanel_order_detail_includes_komerce_shipping_panel_for_admin(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create(['currency_code' => 'IDR']);
        $inventory = Inventory::factory()->create(['name' => 'Gudang Jakarta']);
        OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'service_code' => 'REG',
            'cost' => 12000,
            'currency_code' => 'IDR',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('shopper.orders.detail', $order))
            ->assertOk()
            ->assertSee('RajaOngkir / Komerce shipping', false);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Shopper\KomerceOrderShipping::class, ['order' => $order])
            ->assertSee('Gudang Jakarta');
    }

    public function test_non_admin_cannot_print_fulfillment_label(): void
    {
        $this->configureShopperCpanel();
        $user = User::factory()->create();
        $order = Order::factory()->create();

        // Under /cpanel, Shopper redirects AuthorizationException to its forbidden page.
        $this->actingAs($user)
            ->getJson(route('shopper.orders.fulfillment.print-label', $order))
            ->assertRedirect(route('shopper.forbidden'));
    }
}
