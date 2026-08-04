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
        $this->ensureKomerceEnabled();

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
        $this->ensureKomerceEnabled();

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
        $this->ensureKomerceEnabled();

        return Http::baseUrl($this->baseUrl('komerce.rajaongkir.delivery_base_url'))
            ->timeout($this->timeout())
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'x-api-key' => $this->apiKey('komerce.shipping_delivery_api_key'),
            ]);
    }

    /**
     * Guard every outbound Komerce call behind the integration switch so an
     * unconfigured store never hits the collaborator API.
     */
    protected function ensureKomerceEnabled(): void
    {
        if (! komerce_enabled()) {
            throw KomerceNotConfiguredException::make();
        }
    }

    private function apiKey(string $configKey): string
    {
        $specific = trim((string) config($configKey, ''));

        if ($specific !== '') {
            return $specific;
        }

        return trim((string) config('komerce.api_key', ''));
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
