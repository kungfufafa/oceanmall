<?php

declare(strict_types=1);

namespace App\Domain\Shipping\DTO;

readonly class DeliveryOrderRequestData
{
    public function __construct(
        public string|int $shipmentId,
        public string $orderNumber,
        public string|int $originId,
        public string|int $destinationSubdistrictId,
        public string $senderName,
        public string $senderPhone,
        public string $receiverName,
        public string $receiverPhone,
        public string $receiverAddress,
        public string $courier,
        public string $service,
        public int $weightInGrams,
        public array $items = [],
    ) {}
}
