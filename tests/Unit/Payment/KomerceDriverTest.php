<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use App\Models\User;
use App\Payment\KomerceDriver;
use App\Support\KomerceCallbackSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\OrderItem;
use Shopper\Payment\Exceptions\PaymentException;
use Shopper\Payment\Facades\Payment;
use Tests\TestCase;

final class KomerceDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('komerce.payment_api_key', 'test-payment-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        config()->set('komerce.webhook_secret', 'webhook-secret');
    }

    public function test_shopper_registers_configured_komerce_payment_driver(): void
    {
        $this->assertContains('komerce', Payment::availableDrivers());
        $this->assertTrue(Payment::isConfigured('komerce'));

        $driver = Payment::driver('komerce');

        $this->assertInstanceOf(KomerceDriver::class, $driver);
        $this->assertTrue($driver->supportsWebhooks());
        $this->assertFalse($driver->supportsRefunds());
    }

    public function test_initiate_payment_creates_va_through_payment_api(): void
    {
        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-1',
                    'va_number' => '1234567890',
                    'bank_code' => 'BCA',
                    'amount' => 150000,
                    'status' => 'PENDING',
                    'expired_at' => '2026-08-05T12:00:00+07:00',
                ],
            ]),
        ]);

        $order = $this->order();

        $result = Payment::driver('komerce')->initiatePayment(
            amount: 150000,
            currency: 'IDR',
            context: [
                'order' => $order,
                'payment_type' => 'bank_transfer',
                'channel_code' => 'BCA',
            ],
        );

        $this->assertTrue($result->success);
        $this->assertSame('pending', $result->status);
        $this->assertSame('KOMPAY-1', $result->reference);
        $this->assertSame('1234567890', $result->data['virtual_account_number']);
        $this->assertSame('payment_api', $result->data['provider']);

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://payment.example.test/user/api/v1/user/payment/create'
                && $request->hasHeader('x-api-key', 'test-payment-key')
                && $request['payment_type'] === 'bank_transfer'
                && $request['channel_code'] === 'BCA';
        });
    }

    public function test_handle_webhook_accepts_raw_hmac_and_maps_paid_to_captured(): void
    {
        $raw = json_encode([
            'payment_id' => 'KOMPAY-1',
            'order_id' => 'ORD-1',
            'status' => 'PAID',
            'amount' => 150000,
        ], JSON_THROW_ON_ERROR);

        $result = Payment::driver('komerce')->handleWebhook(
            payload: ['_raw_body' => $raw],
            headers: [
                'x-callback-api-key' => KomerceCallbackSignature::sign($raw, 'webhook-secret'),
            ],
        );

        $this->assertSame('captured', $result->action);
        $this->assertSame('KOMPAY-1', $result->reference);
        $this->assertSame(150000, $result->amount);
    }

    public function test_handle_webhook_rejects_invalid_signature(): void
    {
        $this->expectException(PaymentException::class);

        Payment::driver('komerce')->handleWebhook(
            payload: ['_raw_body' => '{"status":"PAID"}'],
            headers: ['x-callback-api-key' => 'not-the-hmac'],
        );
    }

    public function test_retrieve_payment_maps_paid_status_to_captured(): void
    {
        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/KOMPAY-1' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-1',
                    'status' => 'PAID',
                    'amount' => 150000,
                ],
            ]),
        ]);

        $result = Payment::driver('komerce')->retrievePayment('KOMPAY-1');

        $this->assertSame('captured', $result->status);
        $this->assertSame(150000, $result->amount);
        $this->assertSame('payment_api', $result->data['provider']);
    }

    public function test_cancel_payment_posts_to_payment_api(): void
    {
        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/cancel' => Http::response([
                'success' => true,
                'data' => ['status' => 'CANCELED'],
            ]),
        ]);

        $result = Payment::driver('komerce')->cancelPayment('pay_123');

        $this->assertTrue($result->success);
        $this->assertSame('canceled', $result->status);

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://payment.example.test/user/api/v1/user/payment/cancel'
            && $request['payment_id'] === 'pay_123');
    }

    private function order(): Order
    {
        $user = User::factory()->create([
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'email' => 'budi@example.test',
            'phone_number' => '081234567890',
        ]);

        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'price_amount' => 150000,
            'currency_code' => 'IDR',
            'number' => 'ORD-DRIVER-1',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'name' => 'Kopi',
            'quantity' => 1,
            'unit_price_amount' => 150000,
        ]);

        return $order->fresh(['customer', 'items']);
    }
}
