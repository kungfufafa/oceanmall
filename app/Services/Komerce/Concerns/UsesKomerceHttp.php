<?php

declare(strict_types=1);

namespace App\Services\Komerce\Concerns;

use App\Exceptions\KomerceNotConfiguredException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

trait UsesKomerceHttp
{
    protected function paymentHttp(): PendingRequest
    {
        $this->ensureServiceEnabled(komerce_payment_enabled());

        return Http::baseUrl($this->baseUrl('komerce.payment_base_url'))
            ->timeout($this->timeout())
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'x-api-key' => $this->apiKey('komerce.payment_api_key'),
            ]);
    }

    protected function qrislyHttp(): PendingRequest
    {
        if (! qrisly_enabled()) {
            throw KomerceNotConfiguredException::make();
        }

        return Http::baseUrl($this->baseUrl('komerce.qrisly_base_url'))
            ->timeout($this->timeout())
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'x-api-key' => $this->apiKey('komerce.qrisly_api_key'),
            ]);
    }

    protected function shippingCostHttp(): PendingRequest
    {
        $this->ensureServiceEnabled(komerce_shipping_cost_enabled());

        return Http::baseUrl($this->baseUrl('komerce.rajaongkir.cost_base_url'))
            ->timeout($this->timeout())
            ->acceptJson()
            ->asForm()
            ->withHeaders([
                'key' => $this->apiKey('komerce.shipping_cost_api_key'),
            ]);
    }

    protected function deliveryHttp(): PendingRequest
    {
        $this->ensureServiceEnabled(komerce_shipping_delivery_enabled());

        return Http::baseUrl($this->baseUrl('komerce.rajaongkir.delivery_base_url'))
            ->timeout($this->timeout())
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'x-api-key' => $this->apiKey('komerce.shipping_delivery_api_key'),
            ]);
    }

    private function ensureServiceEnabled(bool $enabled): void
    {
        if (! $enabled) {
            throw KomerceNotConfiguredException::make();
        }
    }

    private function apiKey(string $configKey): string
    {
        $apiKey = trim((string) config($configKey, ''));

        if ($apiKey === '') {
            throw KomerceNotConfiguredException::make();
        }

        return $apiKey;
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
