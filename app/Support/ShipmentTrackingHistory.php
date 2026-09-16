<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\OrderShipment;

/**
 * Canonical tracking timeline shared by Shopper, Vue, and API/Expo.
 *
 * RefreshShipmentTracking persists {description, datetime, location}.
 * Older rows or provider leftovers may still use `date`/`desc`.
 */
final class ShipmentTrackingHistory
{
    /**
     * @return list<array{description: string, datetime: string|null, location: string|null}>
     */
    public static function fromShipment(OrderShipment $shipment): array
    {
        $history = data_get($shipment->metadata, 'komerce.tracking_history', []);

        if (! is_array($history)) {
            return [];
        }

        return collect($history)
            ->filter(static fn (mixed $entry): bool => is_array($entry))
            ->map(static function (array $entry): array {
                $description = $entry['description'] ?? $entry['desc'] ?? $entry['status'] ?? '';
                $datetime = $entry['datetime'] ?? $entry['date'] ?? null;
                $location = $entry['location'] ?? $entry['city_name'] ?? $entry['city'] ?? null;

                return [
                    'description' => is_scalar($description) ? (string) $description : '',
                    'datetime' => is_scalar($datetime) && trim((string) $datetime) !== ''
                        ? trim((string) $datetime)
                        : null,
                    'location' => is_scalar($location) && trim((string) $location) !== ''
                        ? trim((string) $location)
                        : null,
                ];
            })
            ->values()
            ->all();
    }
}
