<?php

declare(strict_types=1);

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Contracts\PaymentDriverContract;
use App\Domain\Payment\DTO\PaymentRequestData;
use App\Domain\Payment\DTO\PaymentResultData;
use App\Domain\Payment\DTO\PaymentWebhookResultData;

class PaymentService
{
    public function __construct(
        protected PaymentDriverContract $driver,
    ) {}

    public function createPayment(PaymentRequestData $data): PaymentResultData
    {
        return $this->driver->createPayment($data);
    }

    public function handleWebhook(array $payload, string $signature): PaymentWebhookResultData
    {
        return $this->driver->verifyWebhook($payload, $signature);
    }

    public function syncStatus(string $paymentRef): PaymentResultData
    {
        return $this->driver->syncStatus($paymentRef);
    }
}
