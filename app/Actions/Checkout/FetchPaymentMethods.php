<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Services\Komerce\PaymentClient;
use InvalidArgumentException;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Payment\Facades\Payment;
use Shopper\Payment\Services\PaymentProcessingService;
use Throwable;

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
        $official = $this->officialKomerceMethods();

        return $zone->paymentMethods()
            ->where('is_enabled', true)
            ->get()
            ->filter(fn (PaymentMethod $method): bool => $this->isAvailable($method, $stripeEnabled, $official))
            ->map(fn (PaymentMethod $method): array => $this->toArray($method, $service, $official))
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>|null  $official
     */
    private function isAvailable(PaymentMethod $method, bool $stripeEnabled, ?array $official): bool
    {
        return match ($method->driver ?? 'manual') {
            'stripe' => $stripeEnabled,
            'komerce' => $this->komerceMethodAvailable($method, $official),
            default => Payment::isConfigured($method->driver ?? 'manual'),
        };
    }

    /**
     * @param  list<array<string, mixed>>|null  $official
     */
    private function komerceMethodAvailable(PaymentMethod $method, ?array $official): bool
    {
        $meta = $this->decodeMeta($method->metadata);
        $paymentType = strtolower((string) ($meta['payment_type'] ?? 'bank_transfer'));

        $locallyReady = $paymentType === 'qris'
            ? qrisly_enabled() || komerce_payment_enabled()
            : komerce_payment_enabled();

        if (! $locallyReady) {
            return false;
        }

        if ($official === null) {
            return true;
        }

        return $this->officialMatch($meta, $official) !== null;
    }

    /**
     * @param  list<array<string, mixed>>|null  $official
     * @return array<string, mixed>
     */
    private function toArray(PaymentMethod $method, PaymentProcessingService $service, ?array $official): array
    {
        $meta = $this->decodeMeta($method->metadata);

        try {
            $logo = $service->getLogoUrl($method);
        } catch (InvalidArgumentException) {
            $logo = null;
        }

        $match = $official !== null ? $this->officialMatch($meta, $official) : null;

        return [
            'id' => $method->id,
            'title' => $method->title,
            'slug' => $method->slug,
            'driver' => $method->driver,
            'description' => $method->description,
            'logo' => is_string($match['logo_url'] ?? null) && $match['logo_url'] !== ''
                ? (string) $match['logo_url']
                : $logo,
            'channel_code' => $meta['channel_code'] ?? null,
            'payment_type' => $meta['payment_type'] ?? null,
            'min_amount' => is_numeric($match['min_amount'] ?? null) ? (int) $match['min_amount'] : null,
            'max_amount' => is_numeric($match['max_amount'] ?? null) ? (int) $match['max_amount'] : null,
        ];
    }

    /**
     * @return list<array<string, mixed>>|null Null = official catalog unavailable
     */
    private function officialKomerceMethods(): ?array
    {
        if (! komerce_payment_enabled()) {
            return null;
        }

        try {
            $response = resolve(PaymentClient::class)->listMethods();
        } catch (Throwable) {
            return null;
        }

        $rows = data_get($response, 'data', []);
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, static fn (mixed $row): bool => is_array($row)));
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  list<array<string, mixed>>  $official
     * @return array<string, mixed>|null
     */
    private function officialMatch(array $meta, array $official): ?array
    {
        $paymentType = strtolower((string) ($meta['payment_type'] ?? 'bank_transfer'));
        $channel = strtoupper(trim((string) ($meta['channel_code'] ?? '')));

        foreach ($official as $row) {
            $officialType = strtolower(trim((string) ($row['payment_type'] ?? '')));
            $officialType = $officialType === 'va' ? 'bank_transfer' : $officialType;
            $officialBank = strtoupper(trim((string) ($row['bank_code'] ?? '')));

            if ($paymentType === 'qris' && $officialType === 'qris') {
                return $row;
            }

            if ($paymentType === 'bank_transfer' && $officialType === 'bank_transfer' && $channel !== '' && $officialBank === $channel) {
                return $row;
            }
        }

        return null;
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
