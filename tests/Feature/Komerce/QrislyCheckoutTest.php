<?php

declare(strict_types=1);

namespace Tests\Feature\Komerce;

use App\Actions\Checkout\CreateKomercePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Models\PaymentTransaction;
use Tests\TestCase;

final class QrislyCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_qrisly_enabled_requires_key_and_qris_id(): void
    {
        config()->set('komerce.enabled', null);
        config()->set('komerce.api_key', 'x');
        config()->set('komerce.qrisly_api_key', '');
        config()->set('komerce.qrisly_qris_id', '');
        $this->assertFalse(qrisly_enabled());

        config()->set('komerce.qrisly_api_key', 'qrisly-key');
        config()->set('komerce.qrisly_qris_id', '');
        $this->assertFalse(qrisly_enabled());

        config()->set('komerce.qrisly_qris_id', '18');
        $this->assertTrue(qrisly_enabled());
    }

    public function test_create_qris_uses_qrisly_when_enabled(): void
    {
        config()->set('komerce.api_key', 'legacy');
        config()->set('komerce.payment_api_key', 'payment-key');
        config()->set('komerce.qrisly_api_key', 'qrisly-key');
        config()->set('komerce.qrisly_qris_id', '18');
        config()->set('komerce.qrisly_base_url', 'https://qrisly.example.test/user');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        config()->set('komerce.webhook_secret', 'webhook-secret');

        $paymentMethod = PaymentMethod::factory()->create(['driver' => 'komerce', 'is_enabled' => true]);
        $order = Order::factory()->create([
            'number' => 'ORD-QRISLY-1',
            'price_amount' => 50000,
            'currency_code' => 'IDR',
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ]);

        Http::fake([
            'https://qrisly.example.test/user/api/v1/qrisly/generate-qris' => Http::response([
                'success' => true,
                'data' => [
                    'history_id' => 9001,
                    'qris_string' => 'QRISLY-STRING',
                    'final_amount' => 50001,
                    'expiry_time' => '2026-08-04 23:00:00',
                ],
            ]),
            'https://payment.example.test/user/*' => Http::response(['success' => false], 500),
        ]);

        $instructions = resolve(CreateKomercePayment::class)->handle($order, [
            'payment_type' => 'qris',
            'driver' => 'komerce',
        ]);

        $this->assertSame('9001', $instructions['payment_id']);
        $this->assertSame('qrisly', $instructions['provider']);
        $this->assertSame('QRISLY-STRING', $instructions['qris_string']);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/qrisly/generate-qris')
            && $request->hasHeader('x-api-key', 'qrisly-key'));
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/payment/create'));

        $this->assertDatabaseHas((new PaymentTransaction)->getTable(), [
            'order_id' => $order->id,
            'reference' => '9001',
            'status' => TransactionStatus::Pending->value,
        ]);
    }

    public function test_create_qris_falls_back_to_payment_api_when_qrisly_key_empty(): void
    {
        config()->set('komerce.api_key', 'legacy');
        config()->set('komerce.payment_api_key', 'payment-key');
        config()->set('komerce.qrisly_api_key', '');
        config()->set('komerce.qrisly_qris_id', '');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        config()->set('komerce.webhook_secret', 'webhook-secret');

        $paymentMethod = PaymentMethod::factory()->create(['driver' => 'komerce', 'is_enabled' => true]);
        $order = Order::factory()->create([
            'number' => 'ORD-QRIS-FALLBACK',
            'price_amount' => 25000,
            'currency_code' => 'IDR',
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ]);

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-QRIS-1',
                    'qris_string' => 'PAYMENT-API-QRIS',
                    'expiry_date' => '2026-08-04 23:00:00',
                    'amount' => 25000,
                ],
            ]),
        ]);

        $instructions = resolve(CreateKomercePayment::class)->handle($order, [
            'payment_type' => 'qris',
            'driver' => 'komerce',
        ]);

        $this->assertSame('KOMPAY-QRIS-1', $instructions['payment_id']);
        $this->assertSame('payment_api', $instructions['provider']);
        $this->assertSame('PAYMENT-API-QRIS', $instructions['qris_string']);
    }
}
