<?php

declare(strict_types=1);

namespace App\Actions\Shipping;

final class NormalizeShipmentStatus
{
    public const PENDING = 'pending';

    public const LABELED = 'labeled';

    public const PICKED_UP = 'picked_up';

    public const IN_TRANSIT = 'in_transit';

    public const DELIVERED = 'delivered';

    /** @var list<string> */
    public const STABLE = [
        self::PENDING,
        self::LABELED,
        self::PICKED_UP,
        self::IN_TRANSIT,
        self::DELIVERED,
    ];

    /**
     * Map a raw carrier / RajaOngkir status string into a stable shipment status.
     */
    public function handle(?string $raw, ?string $fallback = null): ?string
    {
        $candidate = trim((string) $raw);

        if ($candidate === '') {
            return $this->stableOrNull($fallback);
        }

        $upper = strtoupper($candidate);
        $lower = strtolower($candidate);

        if (
            str_contains($lower, 'deliver')
            || str_contains($lower, 'selesai')
            || in_array($upper, ['DELIVERED', 'DELIVERY_SUCCESS', 'SUCCESS', 'COMPLETED', 'POD', 'SELESAI'], true)
        ) {
            return self::DELIVERED;
        }

        if (
            str_contains($lower, 'cancel')
            || str_contains($lower, 'batal')
            || in_array($upper, ['CANCELLED', 'CANCELED', 'DIBATALKAN'], true)
        ) {
            return $this->stableOrNull($fallback) ?? self::LABELED;
        }

        if (
            str_contains($lower, 'transit')
            || str_contains($lower, 'on_process')
            || str_contains($lower, 'on process')
            || str_contains($lower, 'dikirim')
            || in_array($upper, ['ON_PROCESS', 'OTW', 'IN_TRANSIT', 'SHIPPING', 'PROCESS', 'DIKIRIM'], true)
        ) {
            return self::IN_TRANSIT;
        }

        if (
            str_contains($lower, 'pick')
            || str_contains($lower, 'dijemput')
            || in_array($upper, ['PICKED_UP', 'PICKED UP', 'PICKEDUP', 'PICKED', 'PICKUP', 'DIJEMPUT'], true)
        ) {
            return self::PICKED_UP;
        }

        if (
            str_contains($lower, 'diajukan')
            || in_array($lower, [self::LABELED, 'label', 'labeled'], true)
            || in_array($upper, ['LABELED', 'DIAJUKAN'], true)
        ) {
            return self::LABELED;
        }

        if (in_array($lower, [self::PENDING, 'ready'], true)) {
            return self::PENDING;
        }

        return $this->stableOrNull($fallback) ?? self::IN_TRANSIT;
    }

    private function stableOrNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));

        return in_array($normalized, self::STABLE, true) ? $normalized : null;
    }
}
