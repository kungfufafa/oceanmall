<?php

declare(strict_types=1);

namespace App\Domain\Shipping\DTO;

readonly class ShippingTrackingData
{
    public function __construct(
        public string $waybillNumber,
        public string $courierCode,
        public string $status,
        public array $history = [],
        public ?string $deliveredAt = null,
        public array $rawResponse = [],
    ) {}
}
