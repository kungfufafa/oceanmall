<?php

declare(strict_types=1);

namespace Tests\Feature\Account;

use App\Actions\Shipping\NormalizeShipmentStatus;
use App\Livewire\Shopper\KomerceOrderShipping;
use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Enum\ShippingStatus;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Vue account order, Vue checkout-success, API GET order, and Shopper
 * Komerce panel must refresh tracking on view once a shipment already has
 * AWB/tracking_number.
 *
 * Expo/Vue already GET the order every 10s while unpaid. Automatic refresh
 * is throttled by app policy (60s per shipment) so those polls cannot hammer
 * history-airway-bill. That interval is not a Komerce SLA.
 */
final class RefreshShipmentTrackingOnViewTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDeliveryConfig(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'test-komerce-key');
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
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
            'shipping_status' => ShippingStatus::Shipped,
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
            'metadata' => [
                'komerce' => [
                    'order_no' => 'RO-VIEW-TRACK',
                ],
            ],
        ], $shipmentOverrides));

        return [$customer, $order, $shipment];
    }

    private function fakeTrackingResponse(string $desc = 'Kurir menuju alamat'): void
    {
        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'airway_bill' => 'JNE123456789',
                    'last_status' => 'ON_PROCESS',
                    'history' => [
                        [
                            'desc' => $desc,
                            'date' => '2026-08-16 09:15:00',
                            'code' => '100',
                            'status' => 'ON_PROCESS',
                        ],
                    ],
                ],
            ]),
        ]);
    }

    public function test_vue_order_show_refreshes_tracking_when_shipment_has_awb(): void
    {
        $this->withoutVite();
        $this->fakeDeliveryConfig();
        $this->fakeTrackingResponse();

        [$customer, $order, $shipment] = $this->customerOrderShipment();

        $this->actingAs($customer)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('account/order-show')
                ->has('shipments.0.tracking_history', 1)
                ->where('shipments.0.tracking_history.0.description', 'Kurir menuju alamat')
                ->where('shipments.0.tracking_history.0.datetime', '2026-08-16 09:15:00'));

        $shipment->refresh();
        $this->assertSame('Kurir menuju alamat', data_get($shipment->metadata, 'komerce.tracking_history.0.description'));
        $this->assertSame(NormalizeShipmentStatus::IN_TRANSIT, $shipment->status);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/order/api/v1/orders/history-airway-bill'));
    }

    public function test_vue_checkout_success_refreshes_tracking_when_shipment_has_awb(): void
    {
        $this->withoutVite();
        $this->fakeDeliveryConfig();
        $this->fakeTrackingResponse();

        [$customer, $order, $shipment] = $this->customerOrderShipment();

        $this->actingAs($customer)
            ->get(route('shop.checkout.success', ['order' => $order->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('shop/checkout-success')
                ->has('shipments.0.tracking_history', 1)
                ->where('shipments.0.tracking_history.0.description', 'Kurir menuju alamat')
                ->where('shipments.0.tracking_history.0.datetime', '2026-08-16 09:15:00'));

        $shipment->refresh();
        $this->assertSame('Kurir menuju alamat', data_get($shipment->metadata, 'komerce.tracking_history.0.description'));
        $this->assertSame(NormalizeShipmentStatus::IN_TRANSIT, $shipment->status);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/order/api/v1/orders/history-airway-bill'));
    }

    public function test_api_order_show_refreshes_tracking_when_shipment_has_awb(): void
    {
        $this->fakeDeliveryConfig();
        $this->fakeTrackingResponse();

        [$customer, $order] = $this->customerOrderShipment();
        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/orders/{$order->number}")
            ->assertOk()
            ->assertJsonPath('data.shipments.0.tracking_history.0.description', 'Kurir menuju alamat')
            ->assertJsonPath('data.shipments.0.tracking_history.0.datetime', '2026-08-16 09:15:00');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/order/api/v1/orders/history-airway-bill'));
    }

    public function test_shopper_panel_refreshes_tracking_when_shipment_has_awb(): void
    {
        $this->fakeDeliveryConfig();
        $this->fakeTrackingResponse();

        [, $order, $shipment] = $this->customerOrderShipment();
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(KomerceOrderShipping::class, ['order' => $order])
            ->assertSee('Kurir menuju alamat')
            ->assertSee('2026-08-16 09:15:00');

        $shipment->refresh();
        $this->assertSame('Kurir menuju alamat', data_get($shipment->metadata, 'komerce.tracking_history.0.description'));

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/order/api/v1/orders/history-airway-bill'));
    }

    public function test_repeated_order_show_is_throttled_to_one_provider_call_per_minute(): void
    {
        $this->withoutVite();
        $this->fakeDeliveryConfig();
        $this->fakeTrackingResponse();

        [$customer, $order] = $this->customerOrderShipment();
        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/orders/{$order->number}")->assertOk();
        $this->getJson("/api/v1/orders/{$order->number}")->assertOk();
        $this->actingAs($customer)
            ->get(route('account.orders.show', $order))
            ->assertOk();
        $this->actingAs($customer)
            ->get(route('shop.checkout.success', ['order' => $order->id]))
            ->assertOk();

        Http::assertSentCount(1);

        $this->travel(61)->seconds();

        $this->getJson("/api/v1/orders/{$order->number}")
            ->assertOk()
            ->assertJsonPath('data.shipments.0.tracking_history.0.description', 'Kurir menuju alamat');

        Http::assertSentCount(2);

        $this->actingAs($customer)
            ->get(route('shop.checkout.success', ['order' => $order->id]))
            ->assertOk();

        Http::assertSentCount(2);
    }

    public function test_order_show_stays_ok_when_tracking_provider_fails(): void
    {
        $this->withoutVite();
        $this->fakeDeliveryConfig();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => Http::response([
                'meta' => ['code' => 500, 'status' => 'error', 'message' => 'upstream'],
            ], 500),
        ]);

        [$customer, $order, $shipment] = $this->customerOrderShipment();

        $this->actingAs($customer)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('account/order-show')
                ->where('shipments.0.awb', 'JNE123456789')
                ->where('shipments.0.tracking_number', 'JNE123456789')
                ->where('shipments.0.tracking_history', []));

        Sanctum::actingAs($customer);
        $this->getJson("/api/v1/orders/{$order->number}")
            ->assertOk()
            ->assertJsonPath('data.shipments.0.awb', 'JNE123456789')
            ->assertJsonPath('data.shipments.0.tracking_number', 'JNE123456789');

        $this->actingAs($customer)
            ->get(route('shop.checkout.success', ['order' => $order->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('shop/checkout-success')
                ->where('shipments.0.awb', 'JNE123456789')
                ->where('shipments.0.tracking_number', 'JNE123456789')
                ->where('shipments.0.tracking_history', []));

        $shipment->refresh();
        $this->assertSame('JNE123456789', $shipment->awb);
        $this->assertSame('JNE123456789', $shipment->tracking_number);
        $this->assertSame('labeled', $shipment->status);

        Http::assertSentCount(1);
    }

    public function test_order_show_does_not_call_provider_without_awb_or_tracking_number(): void
    {
        $this->withoutVite();
        $this->fakeDeliveryConfig();
        Http::fake();

        [$customer, $order] = $this->customerOrderShipment([
            'awb' => null,
            'tracking_number' => null,
            'status' => 'pending',
            'metadata' => ['komerce' => ['order_no' => 'RO-VIEW-NO-AWB']],
        ]);

        Sanctum::actingAs($customer);
        $this->getJson("/api/v1/orders/{$order->number}")->assertOk();

        $this->actingAs($customer)
            ->get(route('account.orders.show', $order))
            ->assertOk();
        $this->actingAs($customer)
            ->get(route('shop.checkout.success', ['order' => $order->id]))
            ->assertOk();

        Http::assertNothingSent();
    }

    public function test_order_show_refreshes_when_only_tracking_number_is_present(): void
    {
        $this->fakeDeliveryConfig();
        $this->fakeTrackingResponse();

        [$customer, $order] = $this->customerOrderShipment([
            'awb' => null,
            'tracking_number' => 'JNE123456789',
        ]);
        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/orders/{$order->number}")
            ->assertOk()
            ->assertJsonPath('data.shipments.0.tracking_history.0.description', 'Kurir menuju alamat');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/order/api/v1/orders/history-airway-bill'));
    }

    public function test_manual_lacak_still_hits_provider_inside_the_on_view_window(): void
    {
        $this->fakeDeliveryConfig();
        $this->fakeTrackingResponse();

        [$customer, $order, $shipment] = $this->customerOrderShipment();
        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/orders/{$order->number}")->assertOk();
        Http::assertSentCount(1);

        $this->postJson("/api/v1/orders/{$order->number}/shipments/{$shipment->id}/track")
            ->assertOk()
            ->assertJsonPath('data.shipments.0.tracking_history.0.description', 'Kurir menuju alamat');

        Http::assertSentCount(2);
    }

    public function test_vue_order_show_keeps_manual_lacak_and_does_not_add_a_tracking_poll(): void
    {
        $orderShowPage = file_get_contents(resource_path('js/pages/account/order-show.vue'));

        $this->assertIsString($orderShowPage);
        $this->assertStringContainsString('Lacak paket', $orderShowPage);
        $this->assertStringContainsString('@click="trackShipment(shipment)"', $orderShowPage);
        $this->assertStringContainsString(
            'v-if="shipment.awb || shipment.tracking_number"',
            $orderShowPage,
        );
        $this->assertSame(1, substr_count($orderShowPage, 'setInterval(()'));
        $this->assertStringContainsString('shouldPollPayment', $orderShowPage);
        $this->assertStringNotContainsString('shouldPollTracking', $orderShowPage);
        $this->assertStringNotContainsString('setInterval(() => {\n        void trackShipment', $orderShowPage);
    }

    public function test_vue_checkout_success_shows_on_view_history_and_does_not_add_a_tracking_poll(): void
    {
        $checkoutSuccess = file_get_contents(resource_path('js/pages/shop/checkout-success.vue'));

        $this->assertIsString($checkoutSuccess);
        $this->assertStringContainsString('shipments', $checkoutSuccess);
        $this->assertStringContainsString('tracking_history', $checkoutSuccess);
        $this->assertStringContainsString('event.datetime', $checkoutSuccess);
        $this->assertStringContainsString('shouldPollPayment', $checkoutSuccess);
        $this->assertSame(1, substr_count($checkoutSuccess, 'setInterval(()'));
        $this->assertStringNotContainsString('shouldPollTracking', $checkoutSuccess);
        $this->assertStringNotContainsString('void trackShipment', $checkoutSuccess);
    }

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
}
