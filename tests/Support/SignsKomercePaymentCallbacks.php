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
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = $overrideSignature ?? hash_hmac('sha256', $body, $secret);

        return $this->call(
            'POST',
            '/webhooks/komerce/payment',
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
