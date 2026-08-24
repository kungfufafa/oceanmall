<?php

declare(strict_types=1);

namespace App\Shipping\Drivers;

use App\Actions\Shipping\NormalizeShipmentStatus;
use App\Services\Komerce\ShippingCostClient;
use App\Shipping\RajaOngkirRateMapper;
use App\Support\KomerceTrackingContext;
use App\Support\RajaOngkirQuoteContext;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Shopper\Shipping\DataTransferObjects\Address;
use Shopper\Shipping\DataTransferObjects\Package;
use Shopper\Shipping\DataTransferObjects\Shipment;
use Shopper\Shipping\DataTransferObjects\TrackingEvent;
use Shopper\Shipping\DataTransferObjects\TrackingInfo;
use Shopper\Shipping\Drivers\Driver;
use Shopper\Shipping\Exceptions\ShippingException;
use Throwable;

/**
 * Shopper shipping driver for RajaOngkir Shipping Cost API V2 (rates).
 *
 * Fulfillment (store order / pickup / label) stays on the Komerce Delivery
 * driver so Cost and Delivery credentials are never mixed.
 */
final class RajaOngkirDriver extends Driver
{
    public function __construct(
        private readonly ShippingCostClient $costClient,
        private readonly RajaOngkirQuoteContext $quoteContext,
        private readonly RajaOngkirRateMapper $mapper,
        private readonly KomerceTrackingContext $trackingContext,
        private readonly NormalizeShipmentStatus $normalizeStatus,
    ) {}

    public function code(): string
    {
        return 'rajaongkir';
    }

    public function name(): string
    {
        return 'RajaOngkir';
    }

    public function isConfigured(): bool
    {
        return komerce_shipping_cost_enabled();
    }

    public function supportsRealTimeRates(): bool
    {
        return true;
    }

    public function supportsLabels(): bool
    {
        return false;
    }

    public function supportsTracking(): bool
    {
        return true;
    }

    public function calculateRates(Address $from, Address $to, array $packages): Collection
    {
        if (! $this->isConfigured()) {
            throw ShippingException::notConfigured($this->code());
        }

        if (! $this->quoteContext->hasQuote()) {
            return collect();
        }

        $originId = (string) $this->quoteContext->originId();
        $destinationId = (string) $this->quoteContext->destinationId();
        $couriers = $this->quoteContext->couriers();

        if ($couriers === []) {
            $couriers = array_values(array_filter(
                (array) config('komerce.couriers', []),
                static fn (mixed $courier): bool => is_string($courier) && trim($courier) !== '',
            ));
        }

        if ($couriers === []) {
            return collect();
        }

        $weightGrams = $this->totalWeightGrams($packages);
        $cacheKey = 'shipping_rates:'.sha1("{$originId}:{$destinationId}:{$weightGrams}:".implode(',', $couriers));

        try {
            $response = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($originId, $destinationId, $weightGrams, $couriers): array {
                return $this->costClient->calculate(
                    origin: ['id' => $originId],
                    destination: ['id' => $destinationId],
                    weightGrams: $weightGrams,
                    couriers: $couriers,
                );
            });
        } catch (Throwable $e) {
            throw ShippingException::apiError($this->code(), $e->getMessage());
        }

        if (! is_array($response)) {
            throw ShippingException::invalidResponse($this->code());
        }

        return $this->mapper->toShopperRates($response, $couriers);
    }

    public function createShipment(
        Address $from,
        Address $to,
        array $packages,
        string $serviceCode
    ): Shipment {
        throw ShippingException::notSupported('createShipment', $this->code());
    }

    public function track(string $trackingNumber): TrackingInfo
    {
        if (! $this->isConfigured()) {
            throw ShippingException::notConfigured($this->code());
        }

        $trackingNumber = trim($trackingNumber);
        $courier = strtolower(trim((string) $this->trackingContext->courier()));

        if ($trackingNumber === '') {
            throw ShippingException::apiError($this->code(), 'Airway bill is required.');
        }

        if ($courier === '' || preg_match('/^[a-z0-9_-]+$/', $courier) !== 1) {
            throw ShippingException::apiError($this->code(), 'RajaOngkir courier code is required to track a waybill.');
        }

        try {
            $response = $this->costClient->trackWaybill(
                $trackingNumber,
                $courier,
                $this->trackingContext->lastPhoneNumber(),
            );
        } catch (Throwable $e) {
            throw ShippingException::apiError($this->code(), $e->getMessage());
        }

        if (! is_array($response)) {
            throw ShippingException::invalidResponse($this->code());
        }

        $this->trackingContext->setLastRaw($response);

        $rawStatus = trim((string) (
            data_get($response, 'data.delivery_status.status')
            ?? data_get($response, 'data.details.status')
            ?? data_get($response, 'data.summary.status')
            ?? ''
        ));
        $status = $this->normalizeStatus->handle($rawStatus, NormalizeShipmentStatus::PENDING)
            ?? NormalizeShipmentStatus::PENDING;

        if (data_get($response, 'data.delivered') === true) {
            $status = NormalizeShipmentStatus::DELIVERED;
        }

        $events = $this->manifestEvents($response);
        $deliveredAt = $status === NormalizeShipmentStatus::DELIVERED
            ? ($events !== [] ? $events[array_key_last($events)]->occurredAt : now())
            : null;

        return new TrackingInfo(
            trackingNumber: $trackingNumber,
            status: $status,
            statusDescription: $rawStatus !== '' ? $rawStatus : null,
            deliveredAt: $deliveredAt,
            events: $events,
        );
    }

    /**
     * @param  array<string, mixed>  $response
     * @return list<TrackingEvent>
     */
    private function manifestEvents(array $response): array
    {
        $entries = data_get($response, 'data.manifest', []);

        if (! is_array($entries)) {
            return [];
        }

        return collect($entries)
            ->filter(static fn (mixed $entry): bool => is_array($entry))
            ->map(function (array $entry): TrackingEvent {
                $description = (string) (data_get($entry, 'manifest_description') ?? '');
                $status = (string) (data_get($entry, 'manifest_code') ?? $description);
                $date = data_get($entry, 'manifest_date');
                $time = data_get($entry, 'manifest_time');
                $combined = trim(implode(' ', array_filter([
                    is_scalar($date) ? (string) $date : null,
                    is_scalar($time) ? (string) $time : null,
                ])));

                try {
                    $occurredAt = $combined !== '' ? Carbon::parse($combined) : now();
                } catch (Throwable) {
                    $occurredAt = now();
                }

                $location = data_get($entry, 'city_name');

                return new TrackingEvent(
                    status: $status,
                    description: $description,
                    occurredAt: $occurredAt instanceof DateTimeInterface ? $occurredAt : now(),
                    location: is_scalar($location) ? (string) $location : null,
                );
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, Package>  $packages
     */
    private function totalWeightGrams(array $packages): int
    {
        $grams = array_sum(array_map(fn (Package $package): int => $this->packageWeightGrams($package), $packages));

        return max(1, (int) $grams);
    }

    private function packageWeightGrams(Package $package): int
    {
        $weight = max(0.0, $package->weight);

        if ($package->isImperial()) {
            return max(1, (int) ceil($weight * 453.59237));
        }

        return max(1, (int) ceil($weight * 1000));
    }
}
