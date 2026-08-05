<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Shopper\Core\Models\Carrier;
use Shopper\Core\Models\Zone;

final class EnabledCarriers
{
    /**
     * Enabled carriers for storefront display (global cpanel list).
     *
     * @return Collection<int, Carrier>
     */
    public static function forDisplay(): Collection
    {
        return self::baseQuery()->get();
    }

    /**
     * RajaOngkir/Komerce courier codes for rate lookup (excludes manual driver).
     *
     * @return list<string>
     */
    public static function rajaOngkirSlugs(?Zone $zone = null): array
    {
        return self::baseQuery($zone)
            ->where('driver', '!=', 'manual')
            ->pluck('slug')
            ->map(static fn (mixed $slug): string => strtolower(trim((string) $slug)))
            ->filter(static fn (string $slug): bool => $slug !== '')
            ->values()
            ->all();
    }

    /**
     * @return Builder<Carrier>
     */
    private static function baseQuery(?Zone $zone = null): Builder
    {
        $query = Carrier::query()->where('is_enabled', true);

        if ($zone !== null) {
            $carrierIds = $zone->carriers()->allRelatedIds();
            $query->whereIn('id', $carrierIds);
        }

        return $query->orderBy('id');
    }
}
