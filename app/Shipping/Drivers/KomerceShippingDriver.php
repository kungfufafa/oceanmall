<?php

declare(strict_types=1);

namespace App\Shipping\Drivers;

use Illuminate\Support\Collection;
use Shopper\Shipping\DataTransferObjects\Address;
use Shopper\Shipping\Drivers\Driver;

final class KomerceShippingDriver extends Driver
{
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
        return true;
    }

    public function supportsRealTimeRates(): bool
    {
        return true;
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
}
