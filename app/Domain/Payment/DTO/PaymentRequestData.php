<?php

declare(strict_types=1);

namespace App\Domain\Payment\DTO;

readonly class PaymentRequestData
{
    public function __construct(
        public string $orderId,
        public string $orderNumber,
        public int $amount,
        public string $paymentType,
        public ?string $channelCode = null,
        public ?string $customerName = null,
        public ?string $customerEmail = null,
        public ?string $customerPhone = null,
        public array $items = [],
        public ?string $callbackUrl = null,
        public ?int $expiresInMinutes = 1440,
    ) {}
}
