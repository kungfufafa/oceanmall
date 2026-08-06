<?php

declare(strict_types=1);

namespace App\Domain\Shipping\DTO;

readonly class ShippingRateData
{
    public function __construct(
        public string $courierCode,
        public string $courierName,
        public string $serviceCode,
        public string $serviceName,
        public int $cost,
        public ?string $etdDays = null,
        public ?string $description = null,
    ) {}
}
