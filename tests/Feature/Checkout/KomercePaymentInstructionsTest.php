<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Actions\Checkout\CreateKomercePayment;
use App\Actions\Checkout\ResolveKomercePaymentInstructions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;
use Tests\TestCase;

final class KomercePaymentInstructionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.payment_api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        config()->set('komerce.webhook_secret', 'webhook-secret');
        config()->set('shopper.payment.drivers.stripe.enabled', false);
    }

    public function test_create_payment_persists_instructions_on_order_metadata(): void
    {
        Http::fake([
            'payment.example.test/*' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-PERSIST-1',
                    'virtual_account_number' => '1234567890',
                    'bank_code' => 'BCA',
                    'amount' => 150000,
                    'expiry_date' => '2026-08-05T12:00:00+07:00',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $method = PaymentMethod::factory()->create([
            'driver' => 'komerce',
            'is_enabled' => true,
            'metadata' => json_encode(['channel_code' => 'BCA', 'payment_type' => 'bank_transfer']),
        ]);

        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'payment_method_id' => $method->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
            'price_amount' => 150000,
            'currency_code' => 'IDR',
            'number' => 'ORD-PERSIST-1',
            'metadata' => json_encode([]),
        ]);

        $instructions = resolve(CreateKomercePayment::class)->handle($order, [
            'channel_code' => 'BCA',
            'payment_type' => 'bank_transfer',
        ]);

        $order->refresh();
        $meta = json_decode((string) $order->getAttribute('metadata'), true);
        $this->assertIsArray($meta);
        $this->assertSame('1234567890', data_get($meta, 'komerce.payment_instructions.virtual_account_number'));
        $this->assertSame(150000, data_get($meta, 'komerce.payment_instructions.amount'));
        $this->assertSame($instructions['payment_id'], data_get($meta, 'komerce.payment_instructions.payment_id'));
    }

    public function test_resolve_instructions_from_order_after_session_cleared(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
            'price_amount' => 99000,
            'currency_code' => 'IDR',
            'metadata' => json_encode([
                'komerce' => [
                    'payment_ref' => 'KOMPAY-RESOLVE-1',
                    'provider' => 'payment_api',
                    'payment_type' => 'bank_transfer',
                    'payment_instructions' => [
                        'payment_id' => 'KOMPAY-RESOLVE-1',
                        'payment_type' => 'bank_transfer',
                        'provider' => 'payment_api',
                        'virtual_account_number' => '9988776655',
                        'bank_code' => 'BCA',
                        'qris_string' => null,
                        'expiry_date' => '2026-08-05T12:00:00+07:00',
                        'amount' => 99000,
                        'currency_code' => 'IDR',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $resolved = resolve(ResolveKomercePaymentInstructions::class)->handle($order);

        $this->assertNotNull($resolved);
        $this->assertSame('9988776655', $resolved['virtual_account_number']);
        $this->assertSame(99000, $resolved['amount']);
    }

    public function test_checkout_success_reads_instructions_from_order_when_session_empty(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
            'price_amount' => 50000,
            'currency_code' => 'IDR',
            'metadata' => json_encode([
                'komerce' => [
                    'payment_instructions' => [
                        'payment_id' => 'KOMPAY-SUCCESS-1',
                        'payment_type' => 'bank_transfer',
                        'provider' => 'payment_api',
                        'virtual_account_number' => '1122334455',
                        'bank_code' => 'BCA',
                        'qris_string' => null,
                        'expiry_date' => null,
                        'amount' => 50000,
                        'currency_code' => 'IDR',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->actingAs($user)
            ->get(route('shop.checkout.success', ['order' => $order->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('shop/checkout-success')
                ->where('komercePayment.virtual_account_number', '1122334455')
                ->where('komercePayment.amount', 50000)
            );
    }

    public function test_account_order_show_includes_pending_komerce_payment(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
            'price_amount' => 75000,
            'currency_code' => 'IDR',
            'metadata' => json_encode([
                'komerce' => [
                    'payment_instructions' => [
                        'payment_id' => 'KOMPAY-ACCOUNT-1',
                        'payment_type' => 'qris',
                        'provider' => 'payment_api',
                        'virtual_account_number' => null,
                        'bank_code' => null,
                        'qris_string' => '00020101021226650016COM.EXAMPLE.WWW01189360000000000000002015ID20260604000000303UME52045',
                        'expiry_date' => '2026-08-05T12:00:00+07:00',
                        'amount' => 75000,
                        'currency_code' => 'IDR',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->actingAs($user)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('account/order-show')
                ->where('komercePayment.payment_type', 'qris')
                ->where('komercePayment.amount', 75000)
                ->where('canRetryPayment', true)
            );
    }

    public function test_customer_can_retry_komerce_payment_for_pending_order(): void
    {
        Http::fake([
            'payment.example.test/*' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-RETRY-2',
                    'virtual_account_number' => '5555666677',
                    'bank_code' => 'BCA',
                    'amount' => 88000,
                    'expiry_date' => '2026-08-05T18:00:00+07:00',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $method = PaymentMethod::factory()->create([
            'driver' => 'komerce',
            'is_enabled' => true,
            'metadata' => json_encode(['channel_code' => 'BCA', 'payment_type' => 'bank_transfer']),
        ]);

        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'payment_method_id' => $method->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
            'price_amount' => 88000,
            'currency_code' => 'IDR',
            'number' => 'ORD-RETRY-1',
            'metadata' => json_encode([
                'komerce' => [
                    'payment_instructions' => null,
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'payment_method_id' => $method->id,
            'driver' => 'komerce',
            'reference' => 'KOMPAY-RETRY-OLD',
            'type' => TransactionType::Initiate,
            'amount' => 88000,
            'currency_code' => 'IDR',
            'status' => TransactionStatus::Pending,
            'metadata' => ['komerce_payment_ref' => 'KOMPAY-RETRY-OLD'],
        ]);

        $this->actingAs($user)
            ->post(route('account.orders.retry-payment', $order))
            ->assertRedirect(route('account.orders.show', $order));

        $order->refresh();
        $meta = json_decode((string) $order->getAttribute('metadata'), true);
        $this->assertSame('5555666677', data_get($meta, 'komerce.payment_instructions.virtual_account_number'));
        $this->assertSame('KOMPAY-RETRY-2', data_get($meta, 'komerce.payment_instructions.payment_id'));
    }

    public function test_customer_can_sync_pending_komerce_payment_to_paid(): void
    {
        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/KOMPAY-SYNC-1' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-SYNC-1',
                    'status' => 'PAID',
                    'amount' => 66000,
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $method = PaymentMethod::factory()->create([
            'driver' => 'komerce',
            'is_enabled' => true,
            'metadata' => json_encode(['channel_code' => 'BCA', 'payment_type' => 'bank_transfer']),
        ]);

        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'payment_method_id' => $method->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
            'price_amount' => 66000,
            'currency_code' => 'IDR',
            'metadata' => json_encode([
                'komerce' => [
                    'payment_ref' => 'KOMPAY-SYNC-1',
                    'provider' => 'payment_api',
                    'payment_instructions' => [
                        'payment_id' => 'KOMPAY-SYNC-1',
                        'payment_type' => 'bank_transfer',
                        'provider' => 'payment_api',
                        'virtual_account_number' => '1212121212',
                        'bank_code' => 'BCA',
                        'qris_string' => null,
                        'expiry_date' => null,
                        'amount' => 66000,
                        'currency_code' => 'IDR',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'payment_method_id' => $method->id,
            'driver' => 'komerce',
            'reference' => 'KOMPAY-SYNC-1',
            'type' => TransactionType::Initiate,
            'amount' => 66000,
            'currency_code' => 'IDR',
            'status' => TransactionStatus::Pending,
            'metadata' => ['komerce_payment_ref' => 'KOMPAY-SYNC-1'],
        ]);

        $this->actingAs($user)
            ->from(route('shop.checkout.success', ['order' => $order->id]))
            ->post(route('account.orders.sync-payment', $order))
            ->assertRedirect(route('shop.checkout.success', ['order' => $order->id]))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
    }

    public function test_silent_sync_does_not_flash_when_still_unpaid(): void
    {
        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/KOMPAY-SYNC-2' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-SYNC-2',
                    'status' => 'PENDING',
                    'amount' => 44000,
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
            'price_amount' => 44000,
            'currency_code' => 'IDR',
            'metadata' => json_encode([
                'komerce' => [
                    'payment_ref' => 'KOMPAY-SYNC-2',
                    'provider' => 'payment_api',
                    'payment_instructions' => [
                        'payment_id' => 'KOMPAY-SYNC-2',
                        'payment_type' => 'bank_transfer',
                        'provider' => 'payment_api',
                        'virtual_account_number' => '3434343434',
                        'bank_code' => 'BCA',
                        'amount' => 44000,
                        'currency_code' => 'IDR',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'driver' => 'komerce',
            'reference' => 'KOMPAY-SYNC-2',
            'type' => TransactionType::Initiate,
            'amount' => 44000,
            'currency_code' => 'IDR',
            'status' => TransactionStatus::Pending,
        ]);

        $this->actingAs($user)
            ->from(route('account.orders.show', $order))
            ->post(route('account.orders.sync-payment', $order), ['silent' => 1])
            ->assertRedirect(route('account.orders.show', $order))
            ->assertSessionMissing('info')
            ->assertSessionMissing('success');

        $order->refresh();
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
    }

    public function test_checkout_success_revisit_does_not_wipe_active_cart(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
            'price_amount' => 25000,
            'currency_code' => 'IDR',
            'metadata' => json_encode([
                'komerce' => [
                    'payment_instructions' => [
                        'payment_id' => 'KOMPAY-CART-1',
                        'payment_type' => 'bank_transfer',
                        'provider' => 'payment_api',
                        'virtual_account_number' => '7778889990',
                        'bank_code' => 'BCA',
                        'amount' => 25000,
                        'currency_code' => 'IDR',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $cart = \Shopper\Cart\Models\Cart::query()->create([
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
        ]);

        $this->actingAs($user);
        session()->put(config('shopper.cart.session.key', 'shopper_cart'), $cart->id);

        $this->get(route('shop.checkout.success', ['order' => $order->id]))
            ->assertOk();

        $this->assertSame(
            $cart->id,
            session()->get(config('shopper.cart.session.key', 'shopper_cart')),
        );
    }
}
