<?php

declare(strict_types=1);

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\PaymentRequestData;
use App\Domain\Payment\DTO\PaymentResultData;
use App\Domain\Payment\DTO\PaymentWebhookResultData;

interface PaymentDriverContract
{
    /**
     * Create a new payment transaction (Virtual Account or QRIS) with provider.
     */
    public function createPayment(PaymentRequestData $data): PaymentResultData;

    /**
     * Verify incoming payment callback/webhook payload and signature.
     */
    public function verifyWebhook(array $payload, string $signature): PaymentWebhookResultData;

    /**
     * Synchronize and fetch latest payment status for transaction reference.
     */
    public function syncStatus(string $paymentRef): PaymentResultData;
}
