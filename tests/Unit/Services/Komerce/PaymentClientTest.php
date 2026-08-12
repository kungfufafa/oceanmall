<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Komerce;

use App\Services\Komerce\PaymentClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PaymentClientTest extends TestCase
{
    public function test_create_virtual_account_posts_json_payload_with_api_key_header(): void
    {
        config()->set('komerce.payment_api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        config()->set('komerce.timeout', 15);

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'pay_123',
                    'order_id' => 'ORDER-123',
                ],
            ]),
        ]);

        $response = (new PaymentClient)->createVirtualAccount([
            'channel_code' => 'BRIVA',
            'order_id' => 'ORDER-123',
            'amount' => 125000,
            'customer' => [
                'name' => 'Jane Customer',
                'email' => 'jane@example.test',
                'phone' => '08123456789',
            ],
        ]);

        $this->assertSame('pay_123', $response['data']['payment_id']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://payment.example.test/user/api/v1/user/payment/create'
                && $request->hasHeader('x-api-key', 'test-komerce-key')
                && $request->hasHeader('Content-Type', 'application/json')
                && $request['payment_type'] === 'bank_transfer'
                && $request['channel_code'] === 'BRIVA'
                && $request['order_id'] === 'ORDER-123'
                && $request['amount'] === 125000
                && $request['customer']['email'] === 'jane@example.test';
        });
    }

    public function test_create_qris_posts_json_payload_with_qris_payment_type(): void
    {
        config()->set('komerce.payment_api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'pay_qris_123',
                ],
            ]),
        ]);

        $response = (new PaymentClient)->createQris([
            'channel_code' => 'BRIVA',
            'order_id' => 'ORDER-QRIS-123',
            'amount' => 99000,
            'customer' => [
                'name' => 'Jane Customer',
                'email' => 'jane@example.test',
                'phone' => '08123456789',
            ],
        ]);

        $this->assertSame('pay_qris_123', $response['data']['payment_id']);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://payment.example.test/user/api/v1/user/payment/create'
                && $request->hasHeader('x-api-key', 'test-komerce-key')
                && $request['payment_type'] === 'qris'
                && ! array_key_exists('channel_code', $request->data());
        });
    }

    public function test_create_virtual_account_uses_only_payment_api_key(): void
    {
        config()->set('komerce.payment_api_key', 'payment-key');
        config()->set('komerce.qrisly_api_key', 'qrisly-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::response([
                'success' => true,
                'data' => ['payment_id' => 'pay_va'],
            ]),
        ]);

        (new PaymentClient)->createVirtualAccount([
            'channel_code' => 'BRIVA',
            'order_id' => 'ORDER-VA',
            'amount' => 10000,
            'customer' => ['name' => 'A', 'email' => 'a@test', 'phone' => '1'],
        ]);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-api-key', 'payment-key'));
    }

    public function test_create_qris_uses_payment_api_key(): void
    {
        config()->set('komerce.payment_api_key', 'payment-key');
        config()->set('komerce.qrisly_api_key', 'qrisly-key');
        config()->set('komerce.qrisly_qris_id', '99');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::response([
                'success' => true,
                'data' => ['payment_id' => 'pay_qris'],
            ]),
        ]);

        (new PaymentClient)->createQris([
            'order_id' => 'ORDER-QRIS',
            'amount' => 1000,
            'customer' => ['name' => 'A', 'email' => 'a@test', 'phone' => '1'],
        ]);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-api-key', 'payment-key'));
    }

    public function test_get_status_fetches_payment_status_by_reference(): void
    {
        config()->set('komerce.payment_api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/pay_123' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'pay_123',
                    'status' => 'PAID',
                ],
            ]),
        ]);

        $response = (new PaymentClient)->getStatus('pay_123');

        $this->assertSame('PAID', $response['data']['status']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://payment.example.test/user/api/v1/user/payment/status/pay_123'
                && $request->hasHeader('x-api-key', 'test-komerce-key');
        });
    }

    public function test_get_status_reuses_response_for_three_second_provider_limit(): void
    {
        config()->set('komerce.payment_api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/pay_rate_limited' => Http::response([
                'data' => ['payment_id' => 'pay_rate_limited', 'status' => 'PENDING'],
            ]),
        ]);

        $client = new PaymentClient;
        $first = $client->getStatus('pay_rate_limited');
        $second = $client->getStatus('pay_rate_limited');

        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_cancel_posts_payment_id_and_reason(): void
    {
        config()->set('komerce.payment_api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/cancel' => Http::response([
                'success' => true,
                'data' => ['payment_id' => 'pay_123', 'status' => 'CANCELLED'],
            ]),
        ]);

        $response = (new PaymentClient)->cancel('pay_123', 'Payment expired');

        $this->assertSame('CANCELLED', $response['data']['status']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://payment.example.test/user/api/v1/user/payment/cancel'
                && $request['payment_id'] === 'pay_123'
                && $request['reason'] === 'Payment expired';
        });
    }
}
