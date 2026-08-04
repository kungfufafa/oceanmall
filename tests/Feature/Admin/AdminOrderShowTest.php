<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminOrderShowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        Role::query()->firstOrCreate([
            'name' => config('shopper.admin.roles.admin'),
            'guard_name' => 'web',
        ]);
        $admin->assignRole(config('shopper.admin.roles.admin'));

        return $admin;
    }

    public function test_admin_can_view_order_ops_page(): void
    {
        $this->withoutVite();

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
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('admin/order-show')
                    ->where('order.number', (string) $order->number)
                    ->has('shipments', 1)
                    ->has('inventories'),
            );
    }

    public function test_non_admin_cannot_view_order_ops_page(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.orders.show', $order))
            ->assertForbidden();
    }
}
