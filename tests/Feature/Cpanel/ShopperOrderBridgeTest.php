<?php

declare(strict_types=1);

namespace Tests\Feature\Cpanel;

use App\Actions\Checkout\CancelShopperOrderViaKomerce;
use App\Livewire\Shopper\KomerceOrderShipping;
use App\Livewire\Shopper\Pages\OrderDetail;
use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ShopperOrderBridgeTest extends TestCase
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
        config()->set('komerce.payment_api_key', 'test-payment-key');
        config()->set('komerce.shipping_delivery_api_key', 'test-delivery-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
    }

    public function test_shopper_mark_paid_does_not_force_paid_when_komerce_is_unpaid(): void
    {
        $this->fakeKomerce();
        $admin = $this->admin();
        $order = Order::factory()->create([
            'currency_code' => 'IDR',
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
            'metadata' => json_encode([
                'komerce' => [
                    'payment_ref' => 'PAY-UNPAID-1',
                    'provider' => 'payment_api',
                ],
            ], JSON_THROW_ON_ERROR),
        ]);
        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'driver' => 'komerce',
            'type' => TransactionType::Initiate,
            'status' => TransactionStatus::Pending,
            'amount' => 10000,
            'currency_code' => 'IDR',
            'reference' => 'PAY-UNPAID-1',
        ]);

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/PAY-UNPAID-1' => Http::response([
                'success' => true,
                'data' => ['payment_id' => 'PAY-UNPAID-1', 'status' => 'PENDING', 'amount' => 10000],
            ]),
        ]);

        Livewire::actingAs($admin)
            ->test(OrderDetail::class, ['order' => $order])
            ->callAction('markPaid');

        $this->assertSame(PaymentStatus::Pending, $order->refresh()->payment_status);
    }

    public function test_panel_does_not_mark_paid_locally_without_komerce(): void
    {
        $this->fakeKomerce();
        $admin = $this->admin();
        $order = Order::factory()->create([
            'currency_code' => 'IDR',
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
        ]);
        $inventory = Inventory::factory()->create();
        OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'service_code' => 'REG',
            'cost' => 1000,
            'currency_code' => 'IDR',
            'status' => 'pending',
        ]);

        Http::fake();

        Livewire::actingAs($admin)
            ->test(KomerceOrderShipping::class, ['order' => $order])
            ->call('markPaidAndProcessDelivery')
            ->assertSet('overrideError', 'Tidak ada referensi pembayaran Komerce. Jangan tandai lunas manual.');

        $this->assertSame(PaymentStatus::Pending, $order->refresh()->payment_status);
    }

    public function test_shopper_cancel_cancels_matching_komerce_delivery(): void
    {
        $this->fakeKomerce();
        $order = Order::factory()->create([
            'currency_code' => 'IDR',
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
        ]);
        $inventory = Inventory::factory()->create();
        OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'service_code' => 'REG',
            'cost' => 1000,
            'currency_code' => 'IDR',
            'status' => 'pending',
            'metadata' => ['komerce' => ['order_no' => 'RO-BRIDGE-1']],
        ]);

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/cancel' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => ['order_no' => 'RO-BRIDGE-1'],
            ]),
        ]);

        resolve(CancelShopperOrderViaKomerce::class)->handle($order);

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_contains($request->url(), '/orders/cancel')
            && data_get($request->data(), 'order_no') === 'RO-BRIDGE-1');
    }
}
