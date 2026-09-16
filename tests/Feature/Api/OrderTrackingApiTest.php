<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Tests\TestCase;

final class OrderTrackingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_show_exposes_the_same_shipment_tracking_contract_as_vue(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'currency_code' => 'IDR',
        ]);
        $inventory = Inventory::factory()->create(['name' => 'Gudang Jakarta']);

        OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'carrier_name' => 'JNE',
            'service_code' => 'REG',
            'service_name' => 'Regular',
            'cost' => 18000,
            'currency_code' => 'IDR',
            'status' => 'in_transit',
            'awb' => 'JNE123456789',
            'tracking_number' => 'JNE123456789',
            'metadata' => [
                'komerce' => [
                    'tracking_history' => [
                        [
                            'description' => 'Paket dijemput kurir',
                            'datetime' => '2026-08-01 09:00',
                            'location' => 'Jakarta',
                        ],
                    ],
                ],
            ],
        ]);

        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/orders/{$order->number}")
            ->assertOk()
            ->assertJsonPath('data.shipments.0.inventory_name', 'Gudang Jakarta')
            ->assertJsonPath('data.shipments.0.status', 'in_transit')
            ->assertJsonPath('data.shipments.0.status_label', 'Dalam pengiriman')
            ->assertJsonPath('data.shipments.0.currency', 'IDR')
            ->assertJsonPath('data.shipments.0.awb', 'JNE123456789')
            ->assertJsonPath('data.shipments.0.tracking_history.0.description', 'Paket dijemput kurir')
            ->assertJsonPath('data.shipments.0.tracking_history.0.datetime', '2026-08-01 09:00')
            ->assertJsonPath('data.shipments.0.tracking_history.0.location', 'Jakarta')
            ->assertJsonPath('data.shipments.0.origin_pin_ready', false)
            ->assertJsonPath('data.shipments.0.destination_pin_ready', false)
            ->assertJsonPath(
                'data.shipments.0.pin_ready_message',
                'Resi Komerce membutuhkan pinpoint gudang dan tujuan. Pinpoint gudang belum diisi. Pinpoint tujuan belum diisi.',
            );
    }

    public function test_track_refreshes_history_and_returns_datetime_not_raw_date(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'airway_bill' => 'JNE123456789',
                    'last_status' => 'ON_PROCESS',
                    'history' => [
                        [
                            'desc' => 'Shipment received by courier',
                            'date' => '2026-08-01 09:00',
                            'code' => '100',
                            'status' => 'ON_PROCESS',
                        ],
                    ],
                ],
            ]),
        ]);

        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'currency_code' => 'IDR',
        ]);
        $inventory = Inventory::factory()->create(['name' => 'Gudang Jakarta']);
        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'carrier_name' => 'JNE',
            'service_code' => 'REG',
            'service_name' => 'Regular',
            'cost' => 18000,
            'currency_code' => 'IDR',
            'status' => 'labeled',
            'awb' => 'JNE123456789',
            'tracking_number' => 'JNE123456789',
        ]);

        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/orders/{$order->number}/shipments/{$shipment->id}/track")
            ->assertOk()
            ->assertJsonPath('data.shipments.0.inventory_name', 'Gudang Jakarta')
            ->assertJsonPath('data.shipments.0.tracking_history.0.description', 'Shipment received by courier')
            ->assertJsonPath('data.shipments.0.tracking_history.0.datetime', '2026-08-01 09:00')
            ->assertJsonMissingPath('data.shipments.0.tracking_history.0.date');
    }

    public function test_track_without_awb_returns_422(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'currency_code' => 'IDR',
        ]);
        $inventory = Inventory::factory()->create();
        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'service_code' => 'REG',
            'cost' => 0,
            'currency_code' => 'IDR',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/orders/{$order->number}/shipments/{$shipment->id}/track")
            ->assertStatus(422)
            ->assertJsonPath('message', 'This shipment has no airway bill (AWB) to track yet.');
    }
}
