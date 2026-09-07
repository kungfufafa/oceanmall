<?php

declare(strict_types=1);

namespace Tests\Feature\Komerce;

use App\Actions\Shipping\CancelKomerceDelivery;
use App\Actions\Shipping\NormalizeShipmentStatus;
use App\Actions\Shipping\ReconcileKomerceShipment;
use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Listeners\CancelKomerceDeliveryOnOrderCancelled;
use App\Models\OrderShipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Events\Orders\OrderCancelled;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Tests\TestCase;

final class ReconcileKomerceShipmentTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDelivery(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
        config()->set('komerce.pickup_time', '10:00:00');
        config()->set('komerce.pickup_vehicle', 'Motor');
    }

    /**
     * @return array{0: Order, 1: OrderShipment}
     */
    private function registeredShipment(array $shipment = []): array
    {
        $order = Order::factory()->create([
            'currency_code' => 'IDR',
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
        ]);
        $inventory = Inventory::factory()->create();
        $row = OrderShipment::query()->create(array_merge([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'carrier_name' => 'JNE',
            'service_code' => 'REG',
            'status' => 'pending',
            'metadata' => [
                'komerce' => [
                    'order_id' => '44',
                    'order_no' => 'RO-SYNC-1',
                ],
            ],
        ], $shipment));

        return [$order, $row];
    }

    public function test_reconcile_copies_awb_and_status_from_komerce_detail(): void
    {
        $this->fakeDelivery();
        [, $shipment] = $this->registeredShipment();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/detail*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'order_no' => 'RO-SYNC-1',
                    'order_id' => 44,
                    'awb' => 'JNE-FROM-DETAIL',
                    'last_status' => 'ON_PROCESS',
                ],
            ]),
        ]);

        $shipment = resolve(ReconcileKomerceShipment::class)->handle($shipment);

        $this->assertSame('JNE-FROM-DETAIL', $shipment->awb);
        $this->assertSame('JNE-FROM-DETAIL', $shipment->tracking_number);
        $this->assertSame(NormalizeShipmentStatus::IN_TRANSIT, $shipment->status);
        $this->assertSame('JNE-FROM-DETAIL', data_get($shipment->metadata, 'komerce.awb'));
        $this->assertSame('ON_PROCESS', data_get($shipment->metadata, 'komerce.tracking_status'));
    }

    public function test_reconcile_marks_cancelled_when_komerce_cancels_the_delivery(): void
    {
        $this->fakeDelivery();
        [, $shipment] = $this->registeredShipment();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/detail*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'order_no' => 'RO-SYNC-1',
                    'status' => 'CANCELLED',
                ],
            ]),
        ]);

        $shipment = resolve(ReconcileKomerceShipment::class)->handle($shipment);

        $this->assertSame(NormalizeShipmentStatus::CANCELLED, $shipment->status);
    }

    public function test_job_does_not_store_again_when_komerce_detail_already_has_awb(): void
    {
        $this->fakeDelivery();
        [, $shipment] = $this->registeredShipment();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/detail*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'order_no' => 'RO-SYNC-1',
                    'awb' => 'JNE-ALREADY',
                ],
            ]),
            'https://delivery.example.test/order/api/v1/orders/store' => Http::response([
                'meta' => ['code' => 201, 'status' => 'success'],
                'data' => ['order_id' => 99, 'order_no' => 'RO-SHOULD-NOT'],
            ]),
            'https://delivery.example.test/order/api/v1/pickup/request' => Http::response([
                'meta' => ['code' => 201, 'status' => 'success'],
                'data' => [['status' => 'success', 'order_no' => 'RO-SYNC-1', 'awb' => 'JNE-PICKUP']],
            ]),
        ]);

        resolve(CreateRajaOngkirDeliveryForShipment::class, [
            'orderShipmentId' => $shipment->id,
        ])->handle();

        $shipment->refresh();
        $this->assertSame('JNE-ALREADY', $shipment->awb);

        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_contains($request->url(), '/orders/store'));
        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_contains($request->url(), '/pickup/request'));
    }

    public function test_tracking_refresh_reconciles_registered_shipments_without_awb(): void
    {
        $this->fakeDelivery();
        [, $shipment] = $this->registeredShipment();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/detail*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'order_no' => 'RO-SYNC-1',
                    'awb' => 'JNE-POLL',
                    'last_status' => 'PICKED',
                ],
            ]),
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'airway_bill' => 'JNE-POLL',
                    'last_status' => 'PICKED',
                    'history' => [],
                ],
            ]),
        ]);

        $this->artisan('komerce:refresh-shipment-tracking')->assertSuccessful();

        $shipment->refresh();
        $this->assertSame('JNE-POLL', $shipment->awb);
        $this->assertSame(NormalizeShipmentStatus::PICKED_UP, $shipment->status);
    }

    public function test_cancelling_an_order_cancels_the_matching_komerce_delivery(): void
    {
        $this->fakeDelivery();
        [$order] = $this->registeredShipment();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/cancel' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => ['order_no' => 'RO-SYNC-1'],
            ]),
        ]);

        resolve(CancelKomerceDeliveryOnOrderCancelled::class)
            ->handle(new OrderCancelled($order));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === 'https://delivery.example.test/order/api/v1/orders/cancel'
            && $request->data() === ['order_no' => 'RO-SYNC-1']);

        $this->assertSame(
            NormalizeShipmentStatus::CANCELLED,
            OrderShipment::query()->where('order_id', $order->id)->value('status'),
        );
    }

    public function test_cancel_delivery_action_is_idempotent_after_first_cancel(): void
    {
        $this->fakeDelivery();
        [$order, $shipment] = $this->registeredShipment([
            'status' => NormalizeShipmentStatus::CANCELLED,
            'metadata' => [
                'komerce' => [
                    'order_no' => 'RO-SYNC-1',
                    'cancelled_at' => now()->toIso8601String(),
                ],
            ],
        ]);

        Http::fake();

        resolve(CancelKomerceDelivery::class)->handle($order);

        Http::assertNothingSent();
        $this->assertSame(NormalizeShipmentStatus::CANCELLED, $shipment->fresh()->status);
    }
}
