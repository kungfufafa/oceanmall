<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Shipping\NormalizeShipmentStatus;
use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Models\OrderShipment;
use App\Models\Product;
use App\Models\User;
use App\Services\Komerce\ShippingDeliveryClient;
use App\Support\KomerceCallbackSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Enum\ShippingStatus;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\OrderAddress;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class WarehouseOpsE2ETest extends TestCase
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

    private function fakeKomerce(): void
    {
        config()->set('komerce.enabled', true);
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.webhook_secret', 'webhook-secret');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
    }

    /**
     * @return array{0: Order, 1: OrderShipment}
     */
    private function paidOrderPendingLabel(): array
    {
        $address = OrderAddress::query()->create([
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Merdeka 1',
            'postal_code' => '10110',
            'city' => 'Jakarta',
            'phone' => '081234567890',
        ]);

        $order = Order::factory()->create([
            'number' => 'ORD-WH-E2E-1',
            'price_amount' => 100000,
            'currency_code' => 'IDR',
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
            'shipping_status' => ShippingStatus::Unfulfilled,
            'shipping_address_id' => $address->id,
            'metadata' => json_encode([
                'shipping_address' => [
                    'country_id' => 1,
                    'rajaongkir_destination_id' => '152',
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $inventory = Inventory::factory()->create([
            'name' => 'Gudang Cirebon',
            'rajaongkir_origin_id' => '501',
        ]);

        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'carrier_name' => 'JNE',
            'service_code' => 'REG',
            'service_name' => 'Reguler',
            'cost' => 18000,
            'currency_code' => 'IDR',
            'status' => 'pending',
        ]);

        $product = Product::factory()->standard()->create([
            'name' => 'Kopi Cirebon',
            'weight_value' => 500,
            'weight_unit' => 'g',
        ]);

        $shipment->lines()->create([
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'qty' => 1,
        ]);

        return [$order, $shipment];
    }

    public function test_warehouse_flow_awb_print_webhook_completes_order(): void
    {
        $this->fakeKomerce();
        $admin = $this->admin();
        [$order, $shipment] = $this->paidOrderPendingLabel();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/store' => Http::response([
                'success' => true,
                'data' => [
                    'order_no' => 'RO-WH-1',
                    'awb' => 'JNE-WH-1',
                    'tracking_number' => 'JNE-WH-1',
                ],
            ]),
            'https://delivery.example.test/order/api/v1/pickup/request' => Http::response([
                'success' => true,
                'data' => ['pickup_code' => 'PICKUP-WH'],
            ]),
            'https://delivery.example.test/order/api/v1/orders/print-label*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => ['path' => 'https://delivery.example.test/storage/label/RO-WH-1.pdf'],
            ]),
        ]);

        resolve(CreateRajaOngkirDeliveryForShipment::class, [
            'orderShipmentId' => $shipment->id,
        ])->handle(resolve(ShippingDeliveryClient::class));

        $shipment->refresh();
        $order->refresh();
        $this->assertSame('RO-WH-1', data_get($shipment->metadata, 'komerce.order_no'));
        $this->assertSame('JNE-WH-1', $shipment->awb);
        $this->assertSame(ShippingStatus::Shipped, $order->shipping_status);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/order-show')
                ->where('shipments.0.can_print_label', true)
                ->where('shipments.0.can_override', false)
                ->where('shipments.0.delivery_order_no', 'RO-WH-1')
            );

        $this->actingAs($admin)
            ->get(route('admin.orders.print-label', $order))
            ->assertRedirect('https://delivery.example.test/storage/label/RO-WH-1.pdf');

        $payload = [
            'order_no' => 'RO-WH-1',
            'cnote' => 'JNE-WH-1',
            'status' => 'DELIVERED',
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $sig = KomerceCallbackSignature::sign($body, 'webhook-secret');

        $this->call(
            'POST',
            '/webhooks/komerce/delivery',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_CALLBACK_API_KEY' => $sig,
            ],
            $body,
        )->assertOk()->assertJson(['status' => 'handled']);

        $shipment->refresh();
        $order->refresh();
        $this->assertSame(NormalizeShipmentStatus::DELIVERED, $shipment->status);
        $this->assertSame(ShippingStatus::Delivered, $order->shipping_status);
        $this->assertSame(OrderStatus::Completed, $order->status);
    }
}
