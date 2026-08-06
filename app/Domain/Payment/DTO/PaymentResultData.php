<?php

declare(strict_types=1);

namespace App\Domain\Payment\DTO;

readonly class PaymentResultData
{
    public function __construct(
        public string $transactionId,
        public string $paymentRef,
        public string $status,
        public string $paymentType,
        public ?string $channelCode = null,
        public ?string $vaNumber = null,
        public ?string $bankName = null,
        public ?string $qrString = null,
        public ?string $qrUrl = null,
        public int $amount = 0,
        public ?string $expiresAt = null,
        public array $rawResponse = [],
    ) {}
}
