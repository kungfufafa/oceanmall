<?php

declare(strict_types=1);

namespace App\Domain\Payment\DTO;

readonly class PaymentWebhookResultData
{
    public function __construct(
        public bool $isValid,
        public ?string $paymentRef,
        public ?string $orderNumber,
        public string $status,
        public ?string $paidAt = null,
        public array $rawPayload = [],
    ) {}
}
