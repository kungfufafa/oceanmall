<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use InvalidArgumentException;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Payment\Facades\Payment;
use Shopper\Payment\Services\PaymentProcessingService;

final class FetchPaymentMethods
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(int $countryId): array
    {
        $zone = resolve(ResolveZoneForCountry::class)->handle($countryId);

        if (! $zone) {
            return [];
        }

        $stripeEnabled = (bool) config('shopper.payment.drivers.stripe.enabled', false);
        $service = resolve(PaymentProcessingService::class);

        return $zone->paymentMethods()
            ->where('is_enabled', true)
            ->get()
            ->filter(fn (PaymentMethod $method): bool => $this->isAvailable($method, $stripeEnabled))
            ->map(fn (PaymentMethod $method): array => $this->toArray($method, $service))
            ->values()
            ->all();
    }

    private function isAvailable(PaymentMethod $method, bool $stripeEnabled): bool
    {
        return match ($method->driver ?? 'manual') {
            'stripe' => $stripeEnabled,
            'komerce' => $this->komerceMethodAvailable($method),
            default => Payment::isConfigured($method->driver ?? 'manual'),
        };
    }

    private function komerceMethodAvailable(PaymentMethod $method): bool
    {
        $paymentType = strtolower((string) ($this->decodeMeta($method->metadata)['payment_type'] ?? 'bank_transfer'));

        return $paymentType === 'qris'
            ? qrisly_enabled() || komerce_payment_enabled()
            : komerce_payment_enabled();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(PaymentMethod $method, PaymentProcessingService $service): array
    {
        $meta = $this->decodeMeta($method->metadata);

        try {
            $logo = $service->getLogoUrl($method);
        } catch (InvalidArgumentException) {
            $logo = null;
        }

        return [
            'id' => $method->id,
            'title' => $method->title,
            'slug' => $method->slug,
            'driver' => $method->driver,
            'description' => $method->description,
            'logo' => $logo,
            'channel_code' => $meta['channel_code'] ?? null,
            'payment_type' => $meta['payment_type'] ?? null,
        ];
    }

    /**
     * Decode metadata that may be stored as a JSON string or already decoded array.
     *
     * @return array<string, mixed>
     */
    private function decodeMeta(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_string($metadata) && $metadata !== '') {
            $decoded = json_decode($metadata, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
