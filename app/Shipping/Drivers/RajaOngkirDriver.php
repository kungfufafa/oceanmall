<?php

declare(strict_types=1);

namespace App\Shipping\Drivers;

use Illuminate\Support\Collection;
use Shopper\Shipping\DataTransferObjects\Address;
use Shopper\Shipping\Drivers\Driver;

final class RajaOngkirDriver extends Driver
{
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
        return true;
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
        return collect();
    }
}
