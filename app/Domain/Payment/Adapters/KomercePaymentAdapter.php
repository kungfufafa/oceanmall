<?php

declare(strict_types=1);

namespace App\Domain\Payment\Adapters;

use App\Domain\Payment\Contracts\PaymentDriverContract;
use App\Domain\Payment\DTO\PaymentRequestData;
use App\Domain\Payment\DTO\PaymentResultData;
use App\Domain\Payment\DTO\PaymentWebhookResultData;
use App\Services\Komerce\PaymentClient;
use App\Services\Komerce\QrislyClient;
use App\Support\KomerceCallbackSignature;

class KomercePaymentAdapter implements PaymentDriverContract
{
    public function __construct(
        protected PaymentClient $paymentClient,
        protected QrislyClient $qrislyClient,
    ) {}

    public function createPayment(PaymentRequestData $data): PaymentResultData
    {
        $payload = [
            'order_id' => $data->orderNumber,
            'amount' => $data->amount,
            'customer' => [
                'name' => $data->customerName ?? 'Customer',
                'email' => $data->customerEmail ?? 'customer@oceanmall.test',
                'phone' => $data->customerPhone ?? '08123456789',
            ],
            'items' => $data->items,
            'expiry_duration' => $data->expiresInMinutes,
        ];

        if ($data->callbackUrl) {
            $payload['callback_url'] = $data->callbackUrl;
        }

        if (trim((string) config('komerce.webhook_secret', '')) !== '') {
            $payload['callback_API_KEY'] = config('komerce.webhook_secret');
        }

        if ($data->paymentType === 'qris' && qrisly_enabled()) {
            $response = $this->qrislyClient->createDynamicQris([
                'order_id' => $data->orderNumber,
                'amount' => $data->amount,
                'customer_name' => $data->customerName ?? 'Customer',
                'customer_email' => $data->customerEmail ?? 'customer@oceanmall.test',
                'customer_phone' => $data->customerPhone ?? '08123456789',
            ]);

            $dataObj = data_get($response, 'data', []);

            return new PaymentResultData(
                transactionId: (string) data_get($dataObj, 'id', data_get($dataObj, 'transaction_id', $data->orderNumber)),
                paymentRef: (string) data_get($dataObj, 'qris_id', data_get($dataObj, 'qris_content', $data->orderNumber)),
                status: 'pending',
                paymentType: 'qris',
                channelCode: 'qrisly',
                qrString: (string) data_get($dataObj, 'qris_content', data_get($dataObj, 'qr_string')),
                qrUrl: (string) data_get($dataObj, 'qris_url', data_get($dataObj, 'qr_code_url')),
                amount: $data->amount,
                expiresAt: (string) data_get($dataObj, 'expired_at'),
                rawResponse: $response,
            );
        }

        if ($data->paymentType === 'qris') {
            $response = $this->paymentClient->createQris($payload);
        } else {
            $payload['channel_code'] = $data->channelCode ?? 'bca';
            $response = $this->paymentClient->createVirtualAccount($payload);
        }

        $dataObj = data_get($response, 'data', []);

        return new PaymentResultData(
            transactionId: (string) data_get($dataObj, 'id', data_get($dataObj, 'payment_id', $data->orderNumber)),
            paymentRef: (string) data_get($dataObj, 'payment_reference', data_get($dataObj, 'va_number', $data->orderNumber)),
            status: 'pending',
            paymentType: $data->paymentType,
            channelCode: $data->channelCode,
            vaNumber: (string) data_get($dataObj, 'va_number'),
            bankName: (string) data_get($dataObj, 'bank_code', $data->channelCode),
            qrString: (string) data_get($dataObj, 'qr_string'),
            qrUrl: (string) data_get($dataObj, 'payment_url'),
            amount: (int) data_get($dataObj, 'amount', $data->amount),
            expiresAt: (string) data_get($dataObj, 'expired_at'),
            rawResponse: $response,
        );
    }

    public function verifyWebhook(array $payload, string $signature): PaymentWebhookResultData
    {
        $secret = (string) config('komerce.webhook_secret', '');
        $rawContent = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $isValid = KomerceCallbackSignature::verify($rawContent, $signature, $secret);

        $statusStr = strtolower((string) data_get($payload, 'status', data_get($payload, 'payment_status', '')));
        $mappedStatus = match ($statusStr) {
            'paid', 'settlement', 'success', 'successful' => 'paid',
            'expired', 'expire' => 'expired',
            'cancelled', 'cancel', 'failed' => 'cancelled',
            default => 'pending',
        };

        return new PaymentWebhookResultData(
            isValid: $isValid,
            paymentRef: (string) data_get($payload, 'payment_reference', data_get($payload, 'payment_id', data_get($payload, 'va_number'))),
            orderNumber: (string) data_get($payload, 'order_id', data_get($payload, 'external_id')),
            status: $mappedStatus,
            paidAt: (string) data_get($payload, 'paid_at', now()->toIso8601String()),
            rawPayload: $payload,
        );
    }

    public function syncStatus(string $paymentRef): PaymentResultData
    {
        $response = $this->paymentClient->getStatus($paymentRef);
        $dataObj = data_get($response, 'data', []);

        $statusStr = strtolower((string) data_get($dataObj, 'status', 'pending'));
        $mappedStatus = match ($statusStr) {
            'paid', 'settlement', 'success' => 'paid',
            'expired' => 'expired',
            'cancelled', 'failed' => 'cancelled',
            default => 'pending',
        };

        return new PaymentResultData(
            transactionId: (string) data_get($dataObj, 'id', $paymentRef),
            paymentRef: $paymentRef,
            status: $mappedStatus,
            paymentType: (string) data_get($dataObj, 'payment_type', 'bank_transfer'),
            channelCode: (string) data_get($dataObj, 'channel_code'),
            vaNumber: (string) data_get($dataObj, 'va_number'),
            bankName: (string) data_get($dataObj, 'bank_code'),
            qrString: (string) data_get($dataObj, 'qr_string'),
            qrUrl: (string) data_get($dataObj, 'payment_url'),
            amount: (int) data_get($dataObj, 'amount', 0),
            expiresAt: (string) data_get($dataObj, 'expired_at'),
            rawResponse: $response,
        );
    }
}
