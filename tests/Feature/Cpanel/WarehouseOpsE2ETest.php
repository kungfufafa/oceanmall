<?php

declare(strict_types=1);

namespace Tests\Feature\Cpanel;

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
use Shopper\Core\Models\OrderItem;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class WarehouseOpsE2ETest extends TestCase
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

    private function fakeKomerce(): void
    {
        config()->set('komerce.enabled', true);
        config()->set('komerce.shipping_delivery_api_key', 'test-komerce-key');
        config()->set('komerce.webhook_secret', 'webhook-secret');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
        config()->set('komerce.pickup_time', '10:00:00');
        config()->set('komerce.pickup_vehicle', 'Motor');
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
                'komerce' => [
                    'payment_type' => 'bank_transfer',
                ],
                'shipping_address' => [
                    'country_id' => 1,
                    'rajaongkir_destination_id' => '152',
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $inventory = Inventory::factory()->create([
            'name' => 'Gudang Cirebon',
            'email' => 'gudang@oceanmall.test',
            'phone_number' => '02311234567',
            'street_address' => 'Jl. Gudang 10',
            'street_address_plus' => null,
            'city' => 'Cirebon',
            'postal_code' => '45111',
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
            'metadata' => [
                'rate' => [
                    'provider' => 'shipping_delivery',
                    'shipping_name' => 'JNE',
                    'service_name' => 'REG23',
                    'shipping_cost' => 18000,
                    'shipping_cashback' => 4500,
                    'service_fee' => 0,
                    'additional_cost' => 0,
                    'grandtotal' => 118000,
                    'cod_value' => 0,
                    'insurance_value' => 0,
                ],
            ],
        ]);

        $product = Product::factory()->standard()->create([
            'name' => 'Kopi Cirebon',
            'sku' => 'KOPI-CIREBON-500',
            'weight_value' => 500,
            'weight_unit' => 'g',
            'width_value' => 12,
            'width_unit' => 'cm',
            'height_value' => 8,
            'height_unit' => 'cm',
            'depth_value' => 20,
            'depth_unit' => 'cm',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'product_type' => $product->getMorphClass(),
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_amount' => 100000,
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
                'meta' => [
                    'message' => 'Success Create New Order',
                    'code' => 201,
                    'status' => 'success',
                ],
                'data' => ['order_id' => 20001, 'order_no' => 'RO-WH-1'],
            ]),
            'https://delivery.example.test/order/api/v1/pickup/request' => Http::response([
                'meta' => [
                    'message' => 'Success Request Pickup',
                    'code' => 201,
                    'status' => 'success',
                ],
                'data' => [[
                    'status' => 'success',
                    'order_no' => 'RO-WH-1',
                    'awb' => 'JNE-WH-1',
                ]],
            ]),
            'https://delivery.example.test/order/api/v1/orders/print-label*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => ['path' => '/storage/label/RO-WH-1.pdf'],
            ]),
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success', 'message' => 'Success Fetch Airway Bill History'],
                'data' => [
                    'airway_bill' => 'JNE-WH-1',
                    'last_status' => 'DELIVERED',
                    'history' => [
                        [
                            'desc' => 'Package delivered to recipient',
                            'date' => '2026-08-12 10:00:00',
                            'code' => '200',
                            'status' => 'DELIVERED',
                        ],
                    ],
                ],
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
            ->get(route('shopper.orders.detail', $order))
            ->assertOk()
            ->assertSee('RajaOngkir / Komerce shipping', false)
            ->assertSee('RO-WH-1', false)
            ->assertSee('Cetak Stiker Resi', false);

        $this->actingAs($admin)
            ->get(route('shopper.orders.fulfillment.print-label', $order))
            ->assertRedirect('https://delivery.example.test/order/storage/label/RO-WH-1.pdf');
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
