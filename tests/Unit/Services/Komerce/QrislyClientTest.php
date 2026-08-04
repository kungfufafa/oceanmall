<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Komerce;

use App\Exceptions\KomerceNotConfiguredException;
use App\Services\Komerce\QrislyClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class QrislyClientTest extends TestCase
{
    public function test_generate_qris_posts_to_qrisly_endpoint_with_api_key(): void
    {
        config()->set('komerce.api_key', 'legacy');
        config()->set('komerce.qrisly_api_key', 'qrisly-key');
        config()->set('komerce.qrisly_qris_id', '18');
        config()->set('komerce.qrisly_base_url', 'https://qrisly.example.test/user');
        config()->set('komerce.qrisly_unique_amount', true);

        Http::fake([
            'https://qrisly.example.test/user/api/v1/qrisly/generate-qris' => Http::response([
                'success' => true,
                'data' => [
                    'history_id' => 1778,
                    'qris_string' => '00020101',
                    'final_amount' => 1003,
                    'payment_status' => 'unpaid',
                    'expiry_time' => '2026-08-04 12:00:00',
                ],
            ]),
        ]);

        $response = (new QrislyClient)->generateQris([
            'qris_id' => 18,
            'amount' => 1000,
            'output_type' => 'string',
        ]);

        $this->assertSame(1778, $response['data']['history_id']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://qrisly.example.test/user/api/v1/qrisly/generate-qris'
                && $request->hasHeader('x-api-key', 'qrisly-key')
                && $request['qris_id'] === 18
                && $request['amount'] === 1000
                && $request['output_type'] === 'string'
                && $request['unique_amount'] === true;
        });
    }

    public function test_get_payment_status_hits_history_endpoint(): void
    {
        config()->set('komerce.qrisly_api_key', 'qrisly-key');
        config()->set('komerce.qrisly_qris_id', '18');
        config()->set('komerce.qrisly_base_url', 'https://qrisly.example.test/user');

        Http::fake([
            'https://qrisly.example.test/user/api/v1/qrisly/payment-status/1778' => Http::response([
                'data' => [
                    'history_id' => 1778,
                    'payment_status' => 'paid',
                ],
            ]),
        ]);

        $response = (new QrislyClient)->getPaymentStatus(1778);

        $this->assertSame('paid', $response['data']['payment_status']);
    }

    public function test_client_throws_when_qrisly_disabled(): void
    {
        config()->set('komerce.enabled', true);
        config()->set('komerce.api_key', 'x');
        config()->set('komerce.qrisly_api_key', '');
        config()->set('komerce.qrisly_qris_id', '');

        $this->expectException(KomerceNotConfiguredException::class);

        (new QrislyClient)->generateQris(['qris_id' => 1, 'amount' => 1000]);
    }
}
