<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\CountryByZoneData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Zone;

final class GetCountriesByZone
{
    public const string CACHE_KEY = 'countries_by_zone';

    public const int CACHE_TTL = 7200;

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY.'_'.app()->getLocale());
    }

    /**
     * @return Collection<int, CountryByZoneData>
     */
    public function handle(): Collection
    {
        $cacheKey = self::CACHE_KEY.'_'.app()->getLocale();

        $rows = Cache::remember($cacheKey, self::CACHE_TTL, fn (): array => Zone::query()
            ->with(['currency', 'countries'])
            ->scopes('enabled')
            ->get()
            ->flatMap(fn (Zone $zone) => $zone->countries->map(fn (Country $country): array => [
                'zone_id' => $zone->id,
                'zone_name' => $zone->name,
                'zone_code' => $zone->code,
                'country_id' => $country->id,
                'country_name' => $country->name,
                'country_code' => $country->cca2,
                'country_flag' => $country->svg_flag,
                'currency_code' => $zone->currency->code,
            ]))
            ->values()
            ->all());

        return collect($rows)->map(fn (array $row): CountryByZoneData => CountryByZoneData::fromArray($row));
    }
}
