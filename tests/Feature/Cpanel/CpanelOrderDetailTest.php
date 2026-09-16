<?php

declare(strict_types=1);

namespace Tests\Feature\Cpanel;

use App\Livewire\Shopper\KomerceOrderShipping;
use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Payment\Facades\Payment;
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
        $paymentMethod = PaymentMethod::factory()->create([
            'title' => 'QRIS Komerce',
            'slug' => 'komerce-qris',
            'driver' => 'komerce',
            'is_enabled' => true,
        ]);
        $order = Order::factory()->create([
            'currency_code' => 'IDR',
            'payment_method_id' => $paymentMethod->id,
        ]);
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

        $this->assertSame('komerce', Payment::driver('komerce')->code());

        $this->actingAs($admin)
            ->get(route('shopper.orders.detail', $order))
            ->assertOk()
            ->assertSee('RajaOngkir / Komerce shipping', false)
            ->assertSee('QRIS Komerce', false);

        Livewire::actingAs($admin)
            ->test(KomerceOrderShipping::class, ['order' => $order])
            ->assertSee('Gudang Jakarta');
    }

    public function test_komerce_panel_shows_cancelled_reason_aligned_with_storefront(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create([
            'currency_code' => 'IDR',
            'status' => OrderStatus::Cancelled,
            'payment_status' => PaymentStatus::Voided,
            'metadata' => json_encode([
                'komerce' => [
                    'cancelled_reason' => 'Payment expired',
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        Livewire::actingAs($admin)
            ->test(KomerceOrderShipping::class, ['order' => $order])
            ->assertSee('Pesanan dibatalkan otomatis karena pembayaran kedaluwarsa.');
    }

    public function test_komerce_panel_warns_when_warehouse_or_destination_pin_is_missing(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create([
            'currency_code' => 'IDR',
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
            'metadata' => json_encode([
                'shipping_address' => [
                    'city' => 'Jakarta',
                ],
            ], JSON_THROW_ON_ERROR),
        ]);
        $inventory = Inventory::factory()->create([
            'name' => 'Gudang Cirebon',
            'rajaongkir_origin_id' => '17248',
            'latitude' => null,
            'longitude' => null,
        ]);
        OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'service_code' => 'REG',
            'cost' => 15000,
            'currency_code' => 'IDR',
            'status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->test(KomerceOrderShipping::class, ['order' => $order])
            ->assertSee('Gudang Cirebon')
            ->assertSee('Pinpoint gudang belum diisi')
            ->assertSee('Pinpoint tujuan belum diisi');
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
