<?php

declare(strict_types=1);

namespace App\Shipping\Drivers;

use App\Actions\Shipping\NormalizeShipmentStatus;
use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Services\Komerce\ShippingDeliveryClient;
use App\Support\KomerceFulfillmentContext;
use App\Support\KomerceTrackingContext;
use DateTimeInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Shopper\Shipping\DataTransferObjects\Address;
use Shopper\Shipping\DataTransferObjects\Shipment;
use Shopper\Shipping\DataTransferObjects\TrackingEvent;
use Shopper\Shipping\DataTransferObjects\TrackingInfo;
use Shopper\Shipping\Drivers\Driver;
use Shopper\Shipping\Exceptions\ShippingException;
use Throwable;

/**
 * Shopper shipping driver for Komerce Shipping Delivery (AWB, label, tracking).
 *
 * Checkout rates stay on the RajaOngkir Cost driver. This driver is the
 * fulfillment/tracking boundary and uses only the Shipping Delivery key.
 */
final class KomerceShippingDriver extends Driver
{
    public function __construct(
        private readonly ShippingDeliveryClient $delivery,
        private readonly KomerceTrackingContext $trackingContext,
        private readonly KomerceFulfillmentContext $fulfillmentContext,
        private readonly NormalizeShipmentStatus $normalizeStatus,
    ) {}

    public function code(): string
    {
        return 'komerce';
    }

    public function name(): string
    {
        return 'Komerce';
    }

    public function isConfigured(): bool
    {
        return komerce_shipping_delivery_enabled();
    }

    public function supportsRealTimeRates(): bool
    {
        return false;
    }

    public function supportsLabels(): bool
    {
        return true;
    }

    public function supportsTracking(): bool
    {
        return true;
    }

    public function calculateRates(Address $from, Address $to, array $packages): Collection
    {
        return collect();
    }

    public function createShipment(
        Address $from,
        Address $to,
        array $packages,
        string $serviceCode
    ): Shipment {
        if (! $this->isConfigured()) {
            throw ShippingException::notConfigured($this->code());
        }

        $shipment = $this->fulfillmentContext->shipment();

        if ($shipment === null) {
            throw ShippingException::apiError($this->code(), 'Order shipment context is required to create a Komerce delivery.');
        }

        try {
            (new CreateRajaOngkirDeliveryForShipment((int) $shipment->id))->execute($this->delivery);
        } catch (ShippingException $e) {
            throw $e;
        } catch (RequestException|\RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ShippingException::apiError($this->code(), $e->getMessage());
        }

        $shipment->refresh();
        $awb = is_scalar($shipment->awb) ? trim((string) $shipment->awb) : '';

        if ($awb === '') {
            throw ShippingException::apiError($this->code(), 'Komerce delivery did not return an airway bill.');
        }

        return new Shipment(
            trackingNumber: $awb,
            carrierCode: strtolower(trim((string) ($shipment->carrier_code ?: 'komerce'))),
            serviceCode: $serviceCode !== '' ? $serviceCode : (string) $shipment->service_code,
        );
    }

    /**
     * Print Komerce delivery labels. Not on Shopper's ShippingDriver contract;
     * checkout/admin call this through the registered komerce driver.
     *
     * @param  list<string>  $orderNumbers
     * @return array<string, mixed>
     */
    public function printLabels(array $orderNumbers, string $page = ShippingDeliveryClient::DEFAULT_LABEL_PAGE): array
    {
        if (! $this->isConfigured()) {
            throw ShippingException::notConfigured($this->code());
        }

        try {
            return $this->delivery->printLabel($orderNumbers, $page);
        } catch (Throwable $e) {
            throw ShippingException::apiError($this->code(), $e->getMessage());
        }
    }

    public function track(string $trackingNumber): TrackingInfo
    {
        if (! $this->isConfigured()) {
            throw ShippingException::notConfigured($this->code());
        }

        $trackingNumber = trim($trackingNumber);
        $courier = $this->trackingContext->courier();

        if ($trackingNumber === '') {
            throw ShippingException::apiError($this->code(), 'Airway bill is required.');
        }

        if ($courier === null || $courier === '') {
            throw ShippingException::apiError($this->code(), 'Courier (shipping) is required to track a Shipping Delivery airway bill.');
        }

        try {
            $response = $this->delivery->track($trackingNumber, $courier);
        } catch (Throwable $e) {
            throw ShippingException::apiError($this->code(), $e->getMessage());
        }

        if (! is_array($response)) {
            throw ShippingException::invalidResponse($this->code());
        }

        $this->trackingContext->setLastRaw($response);

        $rawStatus = trim((string) (
            data_get($response, 'data.last_status')
            ?? data_get($response, 'data.status')
            ?? ''
        ));
        $status = $this->normalizeStatus->handle($rawStatus, NormalizeShipmentStatus::PENDING)
            ?? NormalizeShipmentStatus::PENDING;

        $events = $this->events($response);
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
    private function events(array $response): array
    {
        $entries = [];

        foreach (['data.history', 'data.manifest', 'data.histories', 'data.tracking_history'] as $path) {
            $value = data_get($response, $path);

            if (is_array($value) && $value !== []) {
                $entries = $value;
                break;
            }
        }

        return collect($entries)
            ->filter(static fn (mixed $entry): bool => is_array($entry))
            ->map(function (array $entry): TrackingEvent {
                $description = data_get($entry, 'desc')
                    ?? data_get($entry, 'description')
                    ?? data_get($entry, 'manifest_description')
                    ?? data_get($entry, 'status')
                    ?? '';
                $status = data_get($entry, 'status') ?? data_get($entry, 'code') ?? $description;
                $occurredAt = $this->occurredAt($entry);

                $location = data_get($entry, 'city_name')
                    ?? data_get($entry, 'city')
                    ?? data_get($entry, 'location');

                return new TrackingEvent(
                    status: is_scalar($status) ? (string) $status : '',
                    description: is_scalar($description) ? (string) $description : '',
                    occurredAt: $occurredAt,
                    location: is_scalar($location) ? (string) $location : null,
                );
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function occurredAt(array $entry): DateTimeInterface
    {
        $date = data_get($entry, 'date') ?? data_get($entry, 'manifest_date');
        $time = data_get($entry, 'time') ?? data_get($entry, 'manifest_time');
        $combined = trim(implode(' ', array_filter([
            is_scalar($date) ? (string) $date : null,
            is_scalar($time) ? (string) $time : null,
        ])));

        if ($combined === '') {
            return now();
        }

        try {
            return Carbon::parse($combined);
        } catch (Throwable) {
            return now();
        }
    }
}
