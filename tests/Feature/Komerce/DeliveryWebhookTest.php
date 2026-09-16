<?php

declare(strict_types=1);

namespace Tests\Feature\Komerce;

use App\Models\OrderShipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Tests\Support\SignsKomercePaymentCallbacks;
use Tests\TestCase;

final class DeliveryWebhookTest extends TestCase
{
    use RefreshDatabase;
    use SignsKomercePaymentCallbacks;

    public function test_delivery_webhook_updates_shipment_status_from_komerce_payload(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');

        $order = Order::factory()->create([
            'currency_code' => 'IDR',
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
        ]);
        $inventory = Inventory::factory()->create();
        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'carrier_name' => 'JNE',
            'service_code' => 'REG',
            'status' => 'labeled',
            'metadata' => [
                'komerce' => [
                    'order_no' => 'RO-ORDER-WH-1',
                    'awb' => 'JNE-OLD',
                ],
            ],
        ]);

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'airway_bill' => 'JNE-NEW-AWB',
                    'last_status' => 'Selesai',
                    'history' => [[
                        'desc' => 'Paket telah diterima',
                        'date' => '2026-08-12 10:00:00',
                        'code' => '200',
                        'status' => 'Selesai',
                    ]],
                ],
            ]),
        ]);

        $this->postJson(route('webhooks.komerce.delivery'), [
            'order_no' => 'RO-ORDER-WH-1',
            'cnote' => 'JNE-NEW-AWB',
            'status' => 'Selesai',
        ])
            ->assertOk()
            ->assertJson(['status' => 'handled']);

        $shipment->refresh();
        $this->assertSame('delivered', $shipment->status);
        $this->assertSame('JNE-NEW-AWB', $shipment->awb);
        $this->assertSame('Selesai', data_get($shipment->metadata, 'komerce.webhook_reported_status'));
        $this->assertSame('Selesai', data_get($shipment->metadata, 'komerce.tracking_status'));
    }

    public function test_delivery_webhook_returns_404_when_shipment_missing(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'test-komerce-key');

        $this->postJson(route('webhooks.komerce.delivery'), [
            'order_no' => 'RO-MISSING',
            'cnote' => 'AWB-MISSING',
            'status' => 'Dikirim',
        ])->assertOk()->assertJson(['status' => 'handled']);
    }

    public function test_delivery_webhook_accepts_official_put_method(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'test-komerce-key');

        $this->putJson(route('webhooks.komerce.delivery'), [
            'order_no' => 'RO-MISSING',
            'cnote' => 'AWB-MISSING',
            'status' => 'Dikirim',
        ])->assertOk()->assertJson(['status' => 'handled']);
    }

    public function test_delivery_webhook_rejects_incomplete_official_payload(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'test-komerce-key');

        $this->postJson(route('webhooks.komerce.delivery'), [
            'cnote' => 'JNE-FORGED',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['order_no', 'status']);
    }

    public function test_delivery_webhook_tracks_via_delivery_api_when_cost_key_is_also_enabled(): void
    {
        config()->set('komerce.shipping_cost_api_key', 'test-cost-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');
        config()->set('komerce.shipping_delivery_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');

        $order = Order::factory()->create([
            'currency_code' => 'IDR',
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
        ]);
        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => Inventory::factory()->create()->id,
            'carrier_code' => 'jne',
            'carrier_name' => 'JNE',
            'status' => 'labeled',
            'awb' => 'JNE-DELIVERY-AWB',
            'metadata' => [
                'komerce' => [
                    'order_no' => 'RO-COST-AND-DELIVERY',
                ],
            ],
        ]);

        Http::fake([
            'https://shipping.example.test/api/v1/track/waybill*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'summary' => ['waybill_number' => 'JNE-DELIVERY-AWB', 'status' => 'DELIVERED'],
                    'manifest' => [],
                ],
            ]),
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'airway_bill' => 'JNE-DELIVERY-AWB',
                    'last_status' => 'ON_PROCESS',
                    'history' => [[
                        'desc' => 'Kurir menuju alamat',
                        'date' => '2026-08-16 09:15:00',
                        'code' => '100',
                        'status' => 'ON_PROCESS',
                    ]],
                ],
            ]),
        ]);

        $this->postJson(route('webhooks.komerce.delivery'), [
            'order_no' => 'RO-COST-AND-DELIVERY',
            'cnote' => 'JNE-DELIVERY-AWB',
            'status' => 'ON_PROCESS',
        ])->assertOk();

        $shipment->refresh();
        $this->assertSame('in_transit', $shipment->status);
        $this->assertSame('Kurir menuju alamat', data_get($shipment->metadata, 'komerce.tracking_history.0.description'));

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/order/api/v1/orders/history-airway-bill'));
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/track/waybill'));
    }

    public function test_delivery_webhook_acks_when_tracking_refresh_fails(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => Http::response([
                'meta' => ['code' => 500, 'status' => 'error', 'message' => 'upstream'],
            ], 500),
        ]);

        $order = Order::factory()->create(['currency_code' => 'IDR']);
        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => Inventory::factory()->create()->id,
            'carrier_code' => 'jne',
            'status' => 'labeled',
            'metadata' => ['komerce' => ['order_no' => 'RO-TRACK-DOWN']],
        ]);

        $this->postJson(route('webhooks.komerce.delivery'), [
            'order_no' => 'RO-TRACK-DOWN',
            'cnote' => 'JNE-RETRY-AWB',
            'status' => 'ON_PROCESS',
        ])
            ->assertOk()
            ->assertJson(['status' => 'handled']);

        $shipment->refresh();
        $this->assertSame('JNE-RETRY-AWB', $shipment->awb);
        $this->assertSame('ON_PROCESS', data_get($shipment->metadata, 'komerce.webhook_reported_status'));
    }

    public function test_delivery_webhook_does_not_apply_unverified_reported_status(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'airway_bill' => 'JNE-REAL-AWB',
                    'last_status' => 'ON_PROCESS',
                    'history' => [],
                ],
            ]),
        ]);

        $order = Order::factory()->create(['currency_code' => 'IDR']);
        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => Inventory::factory()->create()->id,
            'carrier_code' => 'jne',
            'status' => 'labeled',
            'awb' => 'JNE-REAL-AWB',
            'metadata' => ['komerce' => ['order_no' => 'RO-VERIFY']],
        ]);

        $this->postJson(route('webhooks.komerce.delivery'), [
            'order_no' => 'RO-VERIFY',
            'cnote' => 'JNE-REAL-AWB',
            'status' => 'Selesai',
        ])->assertOk();

        $this->assertSame('in_transit', $shipment->refresh()->status);
        $this->assertSame('Selesai', data_get($shipment->metadata, 'komerce.webhook_reported_status'));
        $this->assertSame('ON_PROCESS', data_get($shipment->metadata, 'komerce.tracking_status'));
    }
}
