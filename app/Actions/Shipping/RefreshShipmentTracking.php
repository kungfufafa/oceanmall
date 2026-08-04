<?php

declare(strict_types=1);

namespace App\Actions\Shipping;

use App\Models\OrderShipment;
use App\Services\Komerce\ShippingDeliveryClient;
use RuntimeException;

final readonly class RefreshShipmentTracking
{
    public function __construct(
        private ShippingDeliveryClient $delivery,
        private NormalizeShipmentStatus $normalizeStatus,
        private SyncOrderShippingFromShipments $syncOrderShipping,
    ) {}

    /**
     * Fetch the latest RajaOngkir tracking history for a shipment and
     * persist a normalized history + status onto the shipment metadata.
     *
     * @throws RuntimeException When the shipment has no AWB to track yet.
     */
    public function handle(OrderShipment $shipment): OrderShipment
    {
        $awb = $this->airwayBill($shipment);

        if ($awb === null) {
            throw new RuntimeException('This shipment has no airway bill (AWB) to track yet.');
        }

        $shipping = $this->shippingCourier($shipment);

        if ($shipping === null) {
            throw new RuntimeException('This shipment has no courier (shipping) to track yet.');
        }

        $response = $this->delivery->track($awb, $shipping);

        $history = $this->normalizeHistory($response);
        $rawStatus = $this->resolveRawStatus($response, $history);
        $normalized = $this->normalizeStatus->handle($rawStatus, is_string($shipment->status) ? $shipment->status : null);

        $metadata = is_array($shipment->metadata) ? $shipment->metadata : [];
        $komerce = is_array($metadata['komerce'] ?? null) ? $metadata['komerce'] : [];
        $komerce['tracking'] = $response;
        $komerce['tracking_history'] = $history;
        $komerce['tracking_status'] = $rawStatus;
        $komerce['tracked_at'] = now()->toIso8601String();
        $metadata['komerce'] = $komerce;

        $attributes = ['metadata' => $metadata];

        if ($normalized !== null) {
            $attributes['status'] = $normalized;
        }

        $shipment->forceFill($attributes)->save();

        $order = $shipment->order()->first();

        if ($order !== null) {
            $this->syncOrderShipping->handle($order);
        }

        return $shipment->refresh();
    }

    private function airwayBill(OrderShipment $shipment): ?string
    {
        foreach ([$shipment->awb, data_get($shipment->metadata, 'komerce.awb')] as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return trim((string) $candidate);
            }
        }

        return null;
    }

    private function shippingCourier(OrderShipment $shipment): ?string
    {
        foreach ([
            $shipment->carrier_name,
            $shipment->carrier_code,
            data_get($shipment->metadata, 'komerce.shipping'),
            data_get($shipment->metadata, 'komerce.carrier_name'),
        ] as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return trim((string) $candidate);
            }
        }

        return null;
    }

    /**
     * Normalize whichever history/manifest list the delivery API returns
     * into a predictable shape for the storefront timeline.
     *
     * @param  array<string, mixed>  $response
     * @return list<array{description: string, datetime: string|null, location: string|null}>
     */
    private function normalizeHistory(array $response): array
    {
        $entries = [];

        foreach (['data.manifest', 'data.history', 'data.histories', 'data.detail', 'data.tracking_history'] as $path) {
            $value = data_get($response, $path);

            if (is_array($value) && $value !== []) {
                $entries = $value;
                break;
            }
        }

        return collect($entries)
            ->filter(static fn (mixed $entry): bool => is_array($entry))
            ->map(static function (array $entry): array {
                $date = data_get($entry, 'manifest_date') ?? data_get($entry, 'date');
                $time = data_get($entry, 'manifest_time') ?? data_get($entry, 'time');
                $datetime = trim(implode(' ', array_filter([
                    is_scalar($date) ? (string) $date : null,
                    is_scalar($time) ? (string) $time : null,
                ])));

                $description = data_get($entry, 'manifest_description')
                    ?? data_get($entry, 'description')
                    ?? data_get($entry, 'status')
                    ?? data_get($entry, 'desc');

                $location = data_get($entry, 'city_name')
                    ?? data_get($entry, 'city')
                    ?? data_get($entry, 'location');

                return [
                    'description' => is_scalar($description) ? (string) $description : '',
                    'datetime' => $datetime !== '' ? $datetime : null,
                    'location' => is_scalar($location) ? (string) $location : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $response
     * @param  list<array{description: string, datetime: string|null, location: string|null}>  $history
     */
    private function resolveRawStatus(array $response, array $history): ?string
    {
        foreach (['data.status', 'data.delivery_status', 'data.summary.status', 'status'] as $path) {
            $value = data_get($response, $path);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        $last = $history !== [] ? ($history[array_key_last($history)]['description'] ?? null) : null;

        return is_string($last) && $last !== '' ? $last : null;
    }
}
