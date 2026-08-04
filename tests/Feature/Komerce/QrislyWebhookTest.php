<?php

declare(strict_types=1);

namespace Tests\Feature\Komerce;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;
use Tests\TestCase;

final class QrislyWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_qrisly_webhook_marks_order_paid_on_payment_success(): void
    {
        config()->set('komerce.api_key', 'legacy');
        config()->set('komerce.qrisly_api_key', 'qrisly-key');
        config()->set('komerce.qrisly_qris_id', '18');
        config()->set('komerce.qrisly_base_url', 'https://qrisly.example.test/user');

        $order = Order::factory()->create([
            'number' => 'ORD-QRISLY-WH',
            'price_amount' => 10000,
            'currency_code' => 'IDR',
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
            'metadata' => json_encode([
                'komerce' => [
                    'provider' => 'qrisly',
                    'payment_ref' => '9001',
                    'qrisly_history_id' => '9001',
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
            'reference' => '9001',
            'metadata' => ['komerce_provider' => 'qrisly'],
        ]);

        Http::fake([
            'https://qrisly.example.test/user/api/v1/qrisly/payment-status/9001' => Http::response([
                'data' => [
                    'history_id' => 9001,
                    'payment_status' => 'paid',
                    'amount' => 10000,
                ],
            ]),
        ]);

        $this->postJson(route('webhooks.komerce.qrisly'), [
            'event' => 'payment.success',
            'timestamp' => '2026-08-04T10:00:00Z',
            'data' => [
                'qris_history_id' => '9001',
                'payment_status' => 'paid',
            ],
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'handled',
            ]);

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Processing, $order->status);
    }

    public function test_qrisly_webhook_returns_503_when_disabled(): void
    {
        config()->set('komerce.qrisly_api_key', '');
        config()->set('komerce.qrisly_qris_id', '');

        $this->postJson(route('webhooks.komerce.qrisly'), [
            'event' => 'payment.success',
            'data' => ['qris_history_id' => '1'],
        ])->assertStatus(503)->assertJson(['status' => 'disabled']);
    }
}
