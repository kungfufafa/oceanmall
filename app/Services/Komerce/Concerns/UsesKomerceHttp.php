<?php

declare(strict_types=1);

namespace App\Services\Komerce\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

trait UsesKomerceHttp
{
    protected function paymentHttp(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl('komerce.payment_base_url'))
            ->timeout($this->timeout())
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'x-api-key' => $this->apiKey(),
            ]);
    }

    protected function shippingCostHttp(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl('komerce.rajaongkir.cost_base_url'))
            ->timeout($this->timeout())
            ->acceptJson()
            ->asForm()
            ->withHeaders([
                'key' => $this->apiKey(),
            ]);
    }

    private function apiKey(): string
    {
        return (string) config('komerce.api_key', '');
    }

    private function baseUrl(string $key): string
    {
        return rtrim((string) config($key, ''), '/');
    }

    private function timeout(): int
    {
        return (int) config('komerce.timeout', 30);
    }
}
