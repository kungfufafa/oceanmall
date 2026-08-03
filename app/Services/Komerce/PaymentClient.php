<?php

declare(strict_types=1);

namespace App\Services\Komerce;

use App\Services\Komerce\Concerns\UsesKomerceHttp;

final class PaymentClient
{
    use UsesKomerceHttp;

    private const CREATE_ENDPOINT = '/api/v1/user/payment/create';

    private const STATUS_ENDPOINT = '/api/v1/user/payment/status';

    /**
     * Create a virtual-account payment.
     *
     * Expected payload keys follow Komerce Payment docs:
     * channel_code, order_id, amount, customer{name,email,phone}, and optional
     * items, expiry_duration, callback_url, callback_API_KEY.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createVirtualAccount(array $payload): array
    {
        return $this->createPayment([
            ...$payload,
            'payment_type' => 'bank_transfer',
        ]);
    }

    /**
     * Create a QRIS payment.
     *
     * Expected payload keys follow Komerce Payment docs:
     * order_id, amount, customer{name,email,phone}, and optional items,
     * expiry_duration, callback_url, callback_API_KEY.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createQris(array $payload): array
    {
        unset($payload['channel_code']);

        return $this->createPayment([
            ...$payload,
            'payment_type' => 'qris',
        ]);
    }

    /**
     * Fetch payment status for a Komerce payment ID/reference.
     *
     * @return array<string, mixed>
     */
    public function getStatus(string $reference): array
    {
        $response = $this->paymentHttp()
            ->get(self::STATUS_ENDPOINT.'/'.rawurlencode($reference))
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function createPayment(array $payload): array
    {
        $response = $this->paymentHttp()
            ->post(self::CREATE_ENDPOINT, $payload)
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }
}
