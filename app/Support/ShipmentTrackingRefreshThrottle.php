<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Server-side throttle for automatic tracking refresh on order view.
 *
 * App policy only: at most one automatic history-airway-bill call per
 * shipment per 60 seconds. Official Delivery docs do not publish a poll
 * interval for API-SD-004 (Unknown) — this is not a Komerce SLA.
 *
 * Expo/Vue already GET the order every 10s while unpaid. 60s is 6× that
 * poll so repeated unpaid-or-paid order-page reads cannot hammer Komerce.
 * Cache::add is the per-shipment lock (same komerce: cache family as
 * PaymentClient). Manual Lacak still calls RefreshShipmentTracking
 * directly and is not gated by claim().
 */
final class ShipmentTrackingRefreshThrottle
{
    public const SECONDS = 60;

    public static function key(int $shipmentId): string
    {
        return 'komerce:shipment-tracking:'.$shipmentId;
    }

    public static function claim(int $shipmentId): bool
    {
        return Cache::add(self::key($shipmentId), true, now()->addSeconds(self::SECONDS));
    }

    public static function mark(int $shipmentId): void
    {
        Cache::put(self::key($shipmentId), true, now()->addSeconds(self::SECONDS));
    }
}
