<?php

declare(strict_types=1);

namespace Tests\Feature\Cpanel;

use App\Livewire\Shopper\KomerceOrderShipping;
use App\Models\OrderShipment;
use App\Models\User;
use App\Support\OrderShipmentOpsPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class KomerceOrderShippingOpsTest extends TestCase
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

    private function fakeDelivery(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'test-delivery-key');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
        config()->set('komerce.pickup_time', '10:00:00');
        config()->set('komerce.pickup_vehicle', 'Motor');
    }

    /**
     * @return array{0: Order, 1: OrderShipment}
     */
    private function orderWithShipment(array $orderAttrs = [], array $shipmentAttrs = []): array
    {
        $order = Order::factory()->create(array_merge([
            'currency_code' => 'IDR',
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
        ], $orderAttrs));

        $inventory = Inventory::factory()->create(['name' => 'Gudang Cirebon']);
        $shipment = OrderShipment::query()->create(array_merge([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'service_code' => 'REG',
            'cost' => 12000,
            'currency_code' => 'IDR',
            'status' => 'pending',
        ], $shipmentAttrs));

        return [$order, $shipment];
    }

    public function test_unpaid_order_cannot_register_komerce_delivery(): void
    {
        $this->fakeDelivery();
        $admin = $this->admin();
        [$order] = $this->orderWithShipment([
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ]);

        Http::fake();

        Livewire::actingAs($admin)
            ->test(KomerceOrderShipping::class, ['order' => $order])
            ->call('processDeliveryOrder')
            ->assertSet('overrideError', 'Lunasi pesanan dulu sebelum mendaftarkan pickup dan resi Komerce.');

        Http::assertNothingSent();
    }

    public function test_panel_shows_pickup_action_after_komerce_registration_without_awb(): void
    {
        $this->fakeDelivery();
        $admin = $this->admin();
        [$order] = $this->orderWithShipment([], [
            'metadata' => ['komerce' => ['order_no' => 'RO-WAIT-1', 'order_id' => '11']],
        ]);

        Livewire::actingAs($admin)
            ->test(KomerceOrderShipping::class, ['order' => $order])
            ->assertSee('Request Pickup', false)
            ->assertDontSee('Cetak Stiker Resi', false)
            ->assertSee('Gudang pengirim terkunci', false);
    }

    public function test_print_label_requires_pickup_not_just_delivery_order_number(): void
    {
        $this->fakeDelivery();
        $admin = $this->admin();
        [$order, $shipment] = $this->orderWithShipment([], [
            'metadata' => ['komerce' => ['order_no' => 'RO-NOPICKUP-1', 'order_id' => '12']],
        ]);

        $this->assertFalse(resolve(OrderShipmentOpsPresenter::class)->canPrintLabel($shipment));

        $this->from(route('shopper.orders.detail', $order))
            ->actingAs($admin)
            ->get(route('shopper.orders.fulfillment.print-label', $order))
            ->assertSessionHasErrors('label');
    }

    public function test_print_label_allowed_after_awb(): void
    {
        $this->fakeDelivery();
        $admin = $this->admin();
        [$order, $shipment] = $this->orderWithShipment([], [
            'awb' => 'JNE-READY-1',
            'tracking_number' => 'JNE-READY-1',
            'status' => 'labeled',
            'metadata' => [
                'komerce' => [
                    'order_no' => 'RO-READY-1',
                    'order_id' => '13',
                    'pickup_response' => ['meta' => ['status' => 'success']],
                ],
            ],
        ]);

        $this->assertTrue(resolve(OrderShipmentOpsPresenter::class)->canPrintLabel($shipment));

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/print-label*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => ['path' => '/storage/label/RO-READY-1.pdf'],
            ]),
            'https://delivery.example.test/order/storage/label/*' => Http::response('%PDF-fake', 200),
        ]);

        $this->actingAs($admin)
            ->get(route('shopper.orders.fulfillment.print-label', $order))
            ->assertRedirect('https://delivery.example.test/order/storage/label/RO-READY-1.pdf');
    }
}
