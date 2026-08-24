<?php

declare(strict_types=1);

namespace Tests\Support;

trait SignsKomercePaymentCallbacks
{
    /**
     * @param  array<string, mixed>  $payload
     */
    protected function postSignedKomercePaymentWebhook(
        array $payload,
        string $secret = 'webhook-secret',
        ?string $overrideSignature = null,
    ): \Illuminate\Testing\TestResponse {
        return $this->postSignedKomerceWebhook(
            '/webhooks/komerce/payment',
            $payload,
            $secret,
            $overrideSignature,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function postSignedKomerceDeliveryWebhook(
        array $payload,
        string $secret = 'webhook-secret',
        ?string $overrideSignature = null,
    ): \Illuminate\Testing\TestResponse {
        return $this->postSignedKomerceWebhook(
            '/webhooks/komerce/delivery',
            $payload,
            $secret,
            $overrideSignature,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function postSignedKomerceQrislyWebhook(
        array $payload,
        string $secret = 'webhook-secret',
        ?string $overrideSignature = null,
    ): \Illuminate\Testing\TestResponse {
        return $this->postSignedKomerceWebhook(
            '/webhooks/komerce/qrisly',
            $payload,
            $secret,
            $overrideSignature,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function postSignedKomerceWebhook(
        string $uri,
        array $payload,
        string $secret = 'webhook-secret',
        ?string $overrideSignature = null,
    ): \Illuminate\Testing\TestResponse {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = $overrideSignature ?? hash_hmac('sha256', $body, $secret);

        return $this->call(
            'POST',
            $uri,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_CALLBACK_API_KEY' => $signature,
            ],
            $body,
        );
    }
}
