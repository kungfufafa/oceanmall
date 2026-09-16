<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Tests\TestCase;

final class OrderCancellationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_own_unpaid_order(): void
    {
        config()->set('komerce.payment_api_key', 'test-komerce-key');
        config()->set('komerce.enabled', true);
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/cancel' => Http::response([
                'success' => true,
                'data' => ['status' => 'CANCELLED'],
            ]),
        ]);

        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
            'currency_code' => 'IDR',
            'metadata' => json_encode([
                'komerce' => ['payment_ref' => 'pay_cancel_api_1'],
            ], JSON_THROW_ON_ERROR),
        ]);

        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/orders/{$order->number}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.payment_status', 'voided')
            ->assertJsonPath('data.cancelled_reason', 'Cancelled by customer')
            ->assertJsonPath('data.cancelled_reason_label', 'Pesanan dibatalkan oleh Anda.')
            ->assertJsonPath('data.can_retry_payment', false)
            ->assertJsonPath('data.payment', null);

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame(PaymentStatus::Voided, $order->payment_status);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://payment.example.test/user/api/v1/user/payment/cancel'
            && $request['payment_id'] === 'pay_cancel_api_1');
    }

    public function test_customer_cannot_cancel_another_users_order(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $owner->id,
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
        ]);

        Sanctum::actingAs($intruder);

        $this->postJson("/api/v1/orders/{$order->number}/cancel")
            ->assertNotFound();

        $this->assertSame(OrderStatus::New, $order->fresh()->status);
    }

    public function test_paid_order_cannot_be_cancelled(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/orders/{$order->number}/cancel")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Pesanan sudah dibayar dan tidak bisa dibatalkan.');

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
    }

    public function test_order_show_exposes_the_same_cancelled_reason_label_as_vue(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Cancelled,
            'payment_status' => PaymentStatus::Voided,
            'metadata' => json_encode([
                'komerce' => ['cancelled_reason' => 'Payment expired'],
            ], JSON_THROW_ON_ERROR),
        ]);

        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/orders/{$order->number}")
            ->assertOk()
            ->assertJsonPath('data.cancelled_reason', 'Payment expired')
            ->assertJsonPath('data.cancelled_reason_label', 'Pesanan dibatalkan otomatis karena pembayaran kedaluwarsa.')
            ->assertJsonPath('data.can_retry_payment', false)
            ->assertJsonPath('data.payment', null);
    }

    public function test_already_cancelled_order_returns_422(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Cancelled,
            'payment_status' => PaymentStatus::Voided,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/orders/{$order->number}/cancel")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Pesanan sudah dibatalkan.');
    }
}
