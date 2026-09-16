<?php

declare(strict_types=1);

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;
use Tests\TestCase;

/**
 * Vue checkout-success, Vue order-show, and Expo poll GET every 10s. Without
 * an inbound payment webhook (localhost / empty public URL) that poll must
 * still move pending → paid so the living flow can reach AWB.
 */
final class ReconcileUnpaidOrderOnViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('komerce.payment_api_key', 'test-payment-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
    }

    public function test_api_order_show_marks_paid_when_provider_already_captured(): void
    {
        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/KOMPAY-VIEW-API' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-VIEW-API',
                    'status' => 'PAID',
                    'amount' => 55000,
                ],
            ]),
        ]);

        [$user, $order] = $this->pendingKomerceOrder('KOMPAY-VIEW-API', 55000);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/orders/{$order->number}")
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.status', 'processing');

        $this->assertSame(PaymentStatus::Paid, $order->refresh()->payment_status);
    }

    public function test_vue_order_show_marks_paid_when_provider_already_captured(): void
    {
        $this->withoutVite();

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/KOMPAY-VIEW-WEB' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-VIEW-WEB',
                    'status' => 'PAID',
                    'amount' => 66000,
                ],
            ]),
        ]);

        [$user, $order] = $this->pendingKomerceOrder('KOMPAY-VIEW-WEB', 66000);

        $this->actingAs($user)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('account/order-show')
                ->where('order.payment_status', 'paid')
                ->where('komercePayment', null));

        $this->assertSame(PaymentStatus::Paid, $order->refresh()->payment_status);
    }

    public function test_vue_checkout_success_marks_paid_when_provider_already_captured(): void
    {
        $this->withoutVite();

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/KOMPAY-VIEW-SUCCESS' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-VIEW-SUCCESS',
                    'status' => 'PAID',
                    'amount' => 88000,
                ],
            ]),
        ]);

        [$user, $order] = $this->pendingKomerceOrder('KOMPAY-VIEW-SUCCESS', 88000);

        $this->actingAs($user)
            ->get(route('shop.checkout.success', ['order' => $order->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('shop/checkout-success')
                ->where('order.payment_status', 'paid')
                ->where('komercePayment', null));

        $this->assertSame(PaymentStatus::Paid, $order->refresh()->payment_status);
    }

    public function test_order_show_stays_ok_when_provider_status_fails(): void
    {
        $this->withoutVite();

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/KOMPAY-VIEW-DOWN' => Http::response([
                'meta' => ['code' => 500, 'status' => 'error', 'message' => 'upstream'],
            ], 500),
        ]);

        [$user, $order] = $this->pendingKomerceOrder('KOMPAY-VIEW-DOWN', 44000);

        $this->actingAs($user)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('account/order-show')
                ->where('order.payment_status', 'pending'));

        Sanctum::actingAs($user);
        $this->getJson("/api/v1/orders/{$order->number}")
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'pending');

        $this->assertSame(PaymentStatus::Pending, $order->refresh()->payment_status);
    }

    public function test_paid_order_show_does_not_call_provider_status(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'payment_status' => PaymentStatus::Paid,
            'status' => OrderStatus::Processing,
            'price_amount' => 33000,
            'currency_code' => 'IDR',
            'metadata' => json_encode([
                'komerce' => [
                    'payment_ref' => 'KOMPAY-ALREADY-PAID',
                    'provider' => 'payment_api',
                    'payment_instructions' => [
                        'payment_id' => 'KOMPAY-ALREADY-PAID',
                        'payment_type' => 'bank_transfer',
                        'provider' => 'payment_api',
                        'amount' => 33000,
                        'currency_code' => 'IDR',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        Sanctum::actingAs($user);
        $this->getJson("/api/v1/orders/{$order->number}")
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'paid');

        Http::assertNothingSent();
    }

    /**
     * @return array{0: User, 1: Order}
     */
    private function pendingKomerceOrder(string $paymentId, int $amount): array
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
            'price_amount' => $amount,
            'currency_code' => 'IDR',
            'metadata' => json_encode([
                'komerce' => [
                    'payment_ref' => $paymentId,
                    'provider' => 'payment_api',
                    'payment_instructions' => [
                        'payment_id' => $paymentId,
                        'payment_type' => 'bank_transfer',
                        'provider' => 'payment_api',
                        'virtual_account_number' => '8808001111',
                        'bank_code' => 'BCA',
                        'amount' => $amount,
                        'currency_code' => 'IDR',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'driver' => 'komerce',
            'reference' => $paymentId,
            'type' => TransactionType::Initiate,
            'amount' => $amount,
            'currency_code' => 'IDR',
            'status' => TransactionStatus::Pending,
        ]);

        return [$user, $order];
    }
}
