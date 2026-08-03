<?php

declare(strict_types=1);

namespace Tests\Feature\Account;

use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Tests\TestCase;

final class ShipmentTrackingRefreshTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDeliveryConfig(): void
    {
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
    }

    /**
     * @param  array<string, mixed>  $shipmentOverrides
     * @return array{0: User, 1: Order, 2: OrderShipment}
     */
    private function customerOrderShipment(array $shipmentOverrides = []): array
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'currency_code' => 'IDR',
        ]);
        $inventory = Inventory::factory()->create(['name' => 'Gudang Jakarta']);

        $shipment = OrderShipment::query()->create(array_merge([
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
        ], $shipmentOverrides));

        return [$customer, $order, $shipment];
    }

    private function fakeTrackingResponse(): void
    {
        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'status' => 'ON_PROCESS',
                    'manifest' => [
                        [
                            'manifest_description' => 'Shipment received by courier',
                            'manifest_date' => '2026-08-01',
                            'manifest_time' => '09:00',
                            'city_name' => 'Jakarta',
                        ],
                        [
                            'manifest_description' => 'In transit to destination',
                            'manifest_date' => '2026-08-02',
                            'manifest_time' => '14:30',
                            'city_name' => 'Cirebon',
                        ],
                    ],
                ],
            ]),
        ]);
    }

    public function test_customer_can_refresh_tracking_and_history_is_stored(): void
    {
        $this->fakeDeliveryConfig();
        $this->fakeTrackingResponse();

        [$customer, $order, $shipment] = $this->customerOrderShipment();

        $this->actingAs($customer)
            ->from(route('account.orders.show', $order))
            ->post(route('account.orders.shipments.track', ['order' => $order, 'shipment' => $shipment]))
            ->assertRedirect(route('account.orders.show', $order));

        $shipment->refresh();

        $history = data_get($shipment->metadata, 'komerce.tracking_history');
        $this->assertIsArray($history);
        $this->assertCount(2, $history);
        $this->assertSame('Shipment received by courier', $history[0]['description']);
        $this->assertSame('2026-08-01 09:00', $history[0]['datetime']);
        $this->assertSame('Jakarta', $history[0]['location']);
        $this->assertSame('ON_PROCESS', data_get($shipment->metadata, 'komerce.tracking_status'));
        $this->assertSame('ON_PROCESS', $shipment->status);
    }

    public function test_track_requires_an_airway_bill(): void
    {
        $this->fakeDeliveryConfig();
        Http::fake();

        [$customer, $order, $shipment] = $this->customerOrderShipment([
            'awb' => null,
            'tracking_number' => null,
            'status' => 'pending',
        ]);

        $this->actingAs($customer)
            ->from(route('account.orders.show', $order))
            ->post(route('account.orders.shipments.track', ['order' => $order, 'shipment' => $shipment]))
            ->assertRedirect(route('account.orders.show', $order))
            ->assertSessionHasErrors('tracking');

        Http::assertNothingSent();
    }

    public function test_track_is_forbidden_for_non_owner(): void
    {
        $this->fakeDeliveryConfig();
        Http::fake();

        [, $order, $shipment] = $this->customerOrderShipment();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->post(route('account.orders.shipments.track', ['order' => $order, 'shipment' => $shipment]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_order_show_exposes_tracking_history_after_refresh(): void
    {
        $this->withoutVite();
        $this->fakeDeliveryConfig();
        $this->fakeTrackingResponse();

        [$customer, $order, $shipment] = $this->customerOrderShipment();

        $this->actingAs($customer)
            ->from(route('account.orders.show', $order))
            ->post(route('account.orders.shipments.track', ['order' => $order, 'shipment' => $shipment]));

        $this->actingAs($customer)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('account/order-show')
                    ->has('shipments.0.tracking_history', 2)
                    ->where('shipments.0.tracking_history.0.description', 'Shipment received by courier'),
            );
    }
}
