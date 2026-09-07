<?php

declare(strict_types=1);

namespace Tests\Feature\Cpanel;

use App\Actions\Account\ConfirmOrderReceived;
use App\Actions\Warehouse\OverrideAllocation;
use App\Livewire\Shopper\KomerceOrderShipping;
use App\Livewire\Shopper\Pages\OrderDetail;
use App\Livewire\Shopper\SlideOvers\ShipmentAddEvent;
use App\Models\OrderShipment;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderShipmentOpsPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Enum\ShipmentStatus;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\OrderShipping;
use Shopper\Livewire\Pages\Order\Detail as ShopperVendorOrderDetail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Root-level hole hunt: Shopper shell must not invent pay/ship state
 * that Komerce does not own.
 */
final class RootAlignmentHoleTest extends TestCase
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

    private function enableKomerce(): void
    {
        config()->set('komerce.payment_api_key', 'test-payment-key');
        config()->set('komerce.shipping_delivery_api_key', 'test-delivery-key');
        config()->set('komerce.webhook_secret', 'webhook-secret');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
    }

    public function test_cpanel_order_detail_uses_oceanmall_bridge_not_vendor_shopper_page(): void
    {
        $this->assertSame(OrderDetail::class, config('shopper.components.order.pages.order-detail'));
        $this->assertNotSame(ShopperVendorOrderDetail::class, config('shopper.components.order.pages.order-detail'));

        $this->enableKomerce();
        $admin = $this->admin();
        $order = Order::factory()->create([
            'currency_code' => 'IDR',
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ]);

        $this->actingAs($admin)
            ->get(route('shopper.orders.detail', $order))
            ->assertOk()
            ->assertSee('Sinkronkan pembayaran Komerce', false);
    }

    public function test_customer_cannot_open_shopper_order_detail_or_add_shipment_events(): void
    {
        $this->enableKomerce();
        $this->configureShopperCpanel();
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'currency_code' => 'IDR',
        ]);

        $this->actingAs($customer)
            ->get(route('shopper.orders.detail', $order))
            ->assertRedirect(route('dashboard'));
    }

    public function test_shopper_shipment_event_cannot_invent_delivered_status(): void
    {
        $this->enableKomerce();
        $admin = $this->admin();
        $order = Order::factory()->create(['currency_code' => 'IDR']);
        $shipping = OrderShipping::factory()->create([
            'order_id' => $order->id,
            'tracking_number' => 'JNE-EVENT',
            'status' => ShipmentStatus::Pending,
        ]);

        Livewire::actingAs($admin)
            ->test(ShipmentAddEvent::class, ['shipment' => $shipping])
            ->set('data.status', ShipmentStatus::Delivered->value)
            ->set('data.occurred_at', now()->toDateTimeString())
            ->call('save');

        $this->assertSame(ShipmentStatus::Pending, $shipping->fresh()->status);
    }

    public function test_archive_action_is_disabled_when_komerce_is_on(): void
    {
        $this->enableKomerce();
        $admin = $this->admin();
        $order = Order::factory()->create([
            'currency_code' => 'IDR',
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
        ]);

        Livewire::actingAs($admin)
            ->test(OrderDetail::class, ['order' => $order])
            ->assertActionHidden('archive');

        $this->assertSame(OrderStatus::New, $order->refresh()->status);
        $this->assertNull($order->archived_at);
    }

    public function test_unsigned_payment_webhook_cannot_mark_paid(): void
    {
        $this->enableKomerce();
        $order = Order::factory()->create([
            'number' => 'ORD-HOLE-PAY',
            'currency_code' => 'IDR',
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->postJson('/webhooks/komerce/payment', [
            'payment_id' => 'PAY-FAKE',
            'order_id' => 'ORD-HOLE-PAY',
            'status' => 'PAID',
            'amount' => 100000,
        ])->assertUnauthorized();

        $this->assertSame(PaymentStatus::Pending, $order->refresh()->payment_status);
    }

    public function test_unpaid_customer_cannot_confirm_received(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'currency_code' => 'IDR',
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Pending,
        ]);
        $inventory = Inventory::factory()->create();
        OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'service_code' => 'REG',
            'cost' => 0,
            'currency_code' => 'IDR',
            'status' => 'labeled',
            'awb' => 'JNE-UNPAID',
        ]);

        $this->expectException(ValidationException::class);
        resolve(ConfirmOrderReceived::class)->handle($order);
    }

    public function test_override_and_print_and_push_are_blocked_at_the_wrong_komerce_step(): void
    {
        $this->enableKomerce();
        $admin = $this->admin();
        $from = Inventory::factory()->create(['is_default' => true]);
        $to = Inventory::factory()->create(['is_default' => false]);
        $product = Product::factory()->standard()->create();
        $product->mutateStock($from->id, 5);
        $product->mutateStock($to->id, 5);

        $order = Order::factory()->create([
            'currency_code' => 'IDR',
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
        ]);
        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $from->id,
            'carrier_code' => 'jne',
            'service_code' => 'REG',
            'cost' => 12000,
            'currency_code' => 'IDR',
            'status' => 'pending',
            'metadata' => ['komerce' => ['order_no' => 'RO-HOLE-1', 'order_id' => '9']],
        ]);
        $line = $shipment->lines()->create([
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'qty' => 1,
        ]);

        $this->assertTrue(resolve(OrderShipmentOpsPresenter::class)->isLockedForOverride($shipment));
        $this->assertFalse(resolve(OrderShipmentOpsPresenter::class)->canPrintLabel($shipment));
        $this->assertTrue(resolve(OrderShipmentOpsPresenter::class)->needsPickup($shipment));

        try {
            resolve(OverrideAllocation::class)->handle($order, [[
                'shipment_line_id' => $line->id,
                'qty' => 1,
                'from_inventory_id' => $from->id,
                'to_inventory_id' => $to->id,
            ]]);
            $this->fail('Override after Komerce registration must fail.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $unpaid = Order::factory()->create([
            'currency_code' => 'IDR',
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ]);
        OrderShipment::query()->create([
            'order_id' => $unpaid->id,
            'inventory_id' => $from->id,
            'carrier_code' => 'jne',
            'service_code' => 'REG',
            'cost' => 1000,
            'currency_code' => 'IDR',
            'status' => 'pending',
        ]);

        Http::fake();

        Livewire::actingAs($admin)
            ->test(KomerceOrderShipping::class, ['order' => $unpaid])
            ->call('processDeliveryOrder')
            ->assertSet('overrideError', 'Lunasi pesanan dulu sebelum mendaftarkan pickup dan resi Komerce.');

        Http::assertNothingSent();
    }
}
