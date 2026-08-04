<?php

declare(strict_types=1);

namespace Tests\Feature\Komerce;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;
use Tests\Support\SignsKomercePaymentCallbacks;
use Tests\TestCase;

final class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;
    use SignsKomercePaymentCallbacks;

    public function test_paid_callback_marks_order_paid_once_when_remote_status_is_paid(): void
    {
        config()->set('komerce.webhook_secret', 'webhook-secret');
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');

        $order = Order::factory()->create([
            'number' => 'ORDER-1001',
            'price_amount' => 100000,
            'currency_code' => 'IDR',
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $transaction = PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'driver' => 'komerce',
            'type' => TransactionType::Initiate,
            'status' => TransactionStatus::Pending,
            'amount' => 100000,
            'currency_code' => 'IDR',
            'reference' => 'KOMPAY-1001',
        ]);

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/KOMPAY-1001' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-1001',
                    'status' => 'PAID',
                    'amount' => 100000,
                ],
            ]),
        ]);

        $payload = [
            'payment_id' => 'KOMPAY-1001',
            'order_id' => 'ORDER-1001',
            'status' => 'PAID',
            'amount' => 100000,
        ];

        $this->postSignedKomercePaymentWebhook($payload)
            ->assertOk()
            ->assertJson(['status' => 'handled']);

        $this->travel(1)->minutes();

        $this->postSignedKomercePaymentWebhook($payload)
            ->assertOk()
            ->assertJson(['status' => 'already_processed']);

        $order->refresh();
        $transaction->refresh();

        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(TransactionStatus::Success, $transaction->status);
        $this->assertSame(TransactionType::Capture, $transaction->type);

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://payment.example.test/user/api/v1/user/payment/status/KOMPAY-1001'
                && $request->hasHeader('x-api-key', 'test-komerce-key');
        });
    }

    public function test_callback_with_invalid_hmac_signature_is_rejected(): void
    {
        config()->set('komerce.webhook_secret', 'webhook-secret');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');

        Http::fake();

        $this->postSignedKomercePaymentWebhook([
            'payment_id' => 'KOMPAY-1001',
            'order_id' => 'ORDER-1001',
            'status' => 'PAID',
            'amount' => 100000,
        ], overrideSignature: 'not-a-valid-hmac')
            ->assertUnauthorized()
            ->assertJson(['status' => 'invalid_secret']);

        Http::assertNothingSent();
    }

    public function test_callback_rejecting_plain_secret_header_requires_hmac(): void
    {
        config()->set('komerce.webhook_secret', 'webhook-secret');
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        Http::fake();

        $this->withHeader('X-Callback-Api-Key', 'webhook-secret')
            ->postJson('/webhooks/komerce/payment', [
                'payment_id' => 'KOMPAY-1001',
                'order_id' => 'ORDER-1001',
                'status' => 'PAID',
                'amount' => 100000,
            ])
            ->assertUnauthorized()
            ->assertJson(['status' => 'invalid_secret']);

        Http::assertNothingSent();
    }

    public function test_paid_callback_rejects_amount_mismatch(): void
    {
        config()->set('komerce.webhook_secret', 'webhook-secret');
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');

        $order = Order::factory()->create([
            'number' => 'ORDER-AMOUNT',
            'price_amount' => 100000,
            'currency_code' => 'IDR',
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
        ]);

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'driver' => 'komerce',
            'type' => TransactionType::Initiate,
            'status' => TransactionStatus::Pending,
            'amount' => 100000,
            'currency_code' => 'IDR',
            'reference' => 'KOMPAY-AMOUNT',
        ]);

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/KOMPAY-AMOUNT' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-AMOUNT',
                    'status' => 'PAID',
                    'amount' => 1,
                ],
            ]),
        ]);

        $this->postSignedKomercePaymentWebhook([
            'payment_id' => 'KOMPAY-AMOUNT',
            'order_id' => 'ORDER-AMOUNT',
            'status' => 'PAID',
            'amount' => 1,
        ])
            ->assertStatus(422)
            ->assertJson(['status' => 'amount_mismatch']);

        $order->refresh();
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
    }

    public function test_paid_callback_can_find_order_by_komerce_payment_ref_metadata(): void
    {
        config()->set('komerce.webhook_secret', 'webhook-secret');
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');

        $order = Order::factory()->create([
            'number' => 'ORDER-1002',
            'price_amount' => 150000,
            'currency_code' => 'IDR',
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
        ]);

        DB::table($order->getTable())
            ->where('id', $order->id)
            ->update([
                'metadata' => json_encode(['komerce_payment_ref' => 'KOMPAY-1002'], JSON_THROW_ON_ERROR),
            ]);

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/KOMPAY-1002' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-1002',
                    'status' => 'PAID',
                    'amount' => 150000,
                ],
            ]),
        ]);

        $this->postSignedKomercePaymentWebhook([
            'payment_id' => 'KOMPAY-1002',
            'order_id' => 'ORDER-1002',
            'status' => 'PAID',
            'amount' => 150000,
        ])
            ->assertOk()
            ->assertJson(['status' => 'handled']);

        $order->refresh();

        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);

        $transaction = PaymentTransaction::query()
            ->where('order_id', $order->id)
            ->where('driver', 'komerce')
            ->where('reference', 'KOMPAY-1002')
            ->firstOrFail();

        $this->assertSame(TransactionType::Capture, $transaction->type);
        $this->assertSame(TransactionStatus::Success, $transaction->status);
        $this->assertSame(150000, $transaction->amount);
        $this->assertSame('IDR', $transaction->currency_code);
    }
}
