<?php

declare(strict_types=1);

namespace App\Domain\Shipping\DTO;

readonly class ShippingRateRequestData
{
    public function __construct(
        public string|int $originId,
        public string|int $destinationSubdistrictId,
        public int $weightInGrams,
        public array $couriers = [],
        public array $items = [],
    ) {}
}
