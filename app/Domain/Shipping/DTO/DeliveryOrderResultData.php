<?php

declare(strict_types=1);

namespace App\Domain\Shipping\DTO;

readonly class DeliveryOrderResultData
{
    public function __construct(
        public string $deliveryId,
        public string $waybillNumber,
        public string $status,
        public string $courierCode,
        public string $serviceCode,
        public array $rawResponse = [],
    ) {}
}
