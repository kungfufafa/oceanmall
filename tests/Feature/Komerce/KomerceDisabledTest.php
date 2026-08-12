<?php

declare(strict_types=1);

namespace Tests\Feature\Komerce;

use App\Exceptions\KomerceNotConfiguredException;
use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Models\OrderShipment;
use App\Models\User;
use App\Services\Komerce\ShippingCostClient;
use App\Services\Komerce\ShippingDeliveryClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class KomerceDisabledTest extends TestCase
{
    use RefreshDatabase;

    private function disableKomerce(): void
    {
        config()->set('komerce.payment_api_key', '');
        config()->set('komerce.shipping_cost_api_key', '');
        config()->set('komerce.shipping_delivery_api_key', '');
        config()->set('komerce.qrisly_api_key', '');
        config()->set('komerce.qrisly_qris_id', '');
        config()->set('komerce.enabled', null);
    }

    private function admin(): User
    {
        $this->configureShopperCpanel();

        $admin = User::factory()->create();
        Role::query()->firstOrCreate(['name' => config('shopper.admin.roles.admin'), 'guard_name' => 'web']);
        $admin->assignRole(config('shopper.admin.roles.admin'));

        return $admin;
    }

    public function test_komerce_is_disabled_when_no_api_key(): void
    {
        $this->disableKomerce();

        $this->assertFalse(komerce_enabled());
    }

    public function test_legacy_general_key_does_not_enable_any_service(): void
    {
        config()->set('komerce.enabled', null);
        config()->set('komerce.api_key', 'live-key');

        $this->assertFalse(komerce_enabled());
        $this->assertFalse(komerce_payment_enabled());
        $this->assertFalse(komerce_shipping_cost_enabled());
        $this->assertFalse(komerce_shipping_delivery_enabled());
    }

    public function test_komerce_is_enabled_when_only_shipping_cost_key_present(): void
    {
        $this->disableKomerce();
        config()->set('komerce.shipping_cost_api_key', 'shipping-cost-key');

        $this->assertTrue(komerce_enabled());
    }

    public function test_explicit_disable_flag_overrides_a_present_api_key(): void
    {
        config()->set('komerce.payment_api_key', 'payment-key');
        config()->set('komerce.enabled', false);

        $this->assertFalse(komerce_enabled());
        $this->assertFalse(komerce_payment_enabled());
    }

    public function test_delivery_client_throws_when_disabled(): void
    {
        $this->disableKomerce();
        Http::fake();

        $this->expectException(KomerceNotConfiguredException::class);

        resolve(ShippingDeliveryClient::class)->storeOrder(['order_no' => 'X']);
    }

    public function test_shipping_cost_client_throws_when_disabled(): void
    {
        $this->disableKomerce();
        Http::fake();

        $this->expectException(KomerceNotConfiguredException::class);

        resolve(ShippingCostClient::class)->calculate(['id' => 1], ['id' => 2], 1000, ['jne']);
    }

    public function test_delivery_job_is_a_noop_when_disabled(): void
    {
        $this->disableKomerce();
        Http::fake();

        $order = Order::factory()->create(['currency_code' => 'IDR']);
        $inventory = Inventory::factory()->create(['rajaongkir_origin_id' => '501']);
        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'service_code' => 'REG',
            'status' => 'pending',
        ]);

        resolve(CreateRajaOngkirDeliveryForShipment::class, ['orderShipmentId' => $shipment->id])
            ->handle(resolve(ShippingDeliveryClient::class));

        Http::assertNothingSent();
        $this->assertNull($shipment->refresh()->awb);
        $this->assertSame('pending', $shipment->status);
    }

    public function test_payment_webhook_returns_503_when_disabled(): void
    {
        $this->disableKomerce();
        config()->set('komerce.webhook_secret', 'webhook-secret');
        Http::fake();

        $payload = [
            'payment_id' => 'KOMPAY-1',
            'status' => 'PAID',
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $body, 'webhook-secret');

        // A valid HMAC still short-circuits to 503 because the integration is off
        // (no API key), so no remote payment-status call is ever attempted.
        $this->call(
            'POST',
            route('webhooks.komerce.payment'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_CALLBACK_API_KEY' => $signature,
            ],
            $body,
        )->assertStatus(503)->assertJson(['status' => 'disabled']);

        Http::assertNothingSent();
    }

    public function test_admin_label_shows_friendly_error_when_disabled(): void
    {
        $this->disableKomerce();
        Http::fake();

        $order = Order::factory()->create(['currency_code' => 'IDR']);
        $inventory = Inventory::factory()->create();
        OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'service_code' => 'REG',
            'status' => 'labeled',
            'awb' => 'JNE-AWB',
            'metadata' => ['komerce' => ['order_no' => 'RO-ORDER-1']],
        ]);

        $this->from(route('account.orders.show', $order))
            ->actingAs($this->admin())
            ->get(route('shopper.orders.fulfillment.print-label', $order))
            ->assertSessionHasErrors('label');

        Http::assertNothingSent();
    }

    public function test_customer_tracking_shows_friendly_error_when_disabled(): void
    {
        $this->disableKomerce();
        Http::fake();

        $customer = User::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'currency_code' => 'IDR']);
        $inventory = Inventory::factory()->create();
        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'service_code' => 'REG',
            'status' => 'labeled',
            'awb' => 'JNE-AWB',
        ]);

        $this->from(route('account.orders.show', $order))
            ->actingAs($customer)
            ->post(route('account.orders.shipments.track', ['order' => $order, 'shipment' => $shipment]))
            ->assertRedirect(route('account.orders.show', $order))
            ->assertSessionHasErrors('tracking');

        Http::assertNothingSent();
    }
}
