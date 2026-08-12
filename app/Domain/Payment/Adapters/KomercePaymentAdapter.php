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
use InvalidArgumentException;
use RuntimeException;

class KomercePaymentAdapter implements PaymentDriverContract
{
    public function __construct(
        protected PaymentClient $paymentClient,
        protected QrislyClient $qrislyClient,
    ) {}

    public function createPayment(PaymentRequestData $data): PaymentResultData
    {
        if ($data->amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        if (! in_array($data->paymentType, ['bank_transfer', 'qris'], true)) {
            throw new InvalidArgumentException('Payment type must be bank_transfer or qris.');
        }

        if ($data->paymentType === 'qris' && qrisly_enabled()) {
            return $this->createQrislyPayment($data);
        }

        $this->assertPaymentCustomer($data);

        if ($data->items === []) {
            throw new InvalidArgumentException('Komerce Payment API requires at least one real order item.');
        }

        $payload = [
            'order_id' => $data->orderNumber,
            'amount' => $data->amount,
            'customer' => [
                'name' => trim((string) $data->customerName),
                'email' => trim((string) $data->customerEmail),
                'phone' => trim((string) $data->customerPhone),
            ],
            'items' => $data->items,
        ];

        if ($data->callbackUrl) {
            $payload['callback_url'] = $data->callbackUrl;
        }

        if (trim((string) config('komerce.webhook_secret', '')) !== '') {
            $payload['callback_API_KEY'] = config('komerce.webhook_secret');
        }

        if ($data->paymentType === 'qris') {
            $response = $this->paymentClient->createQris($payload);
        } else {
            $channelCode = trim((string) $data->channelCode);
            if ($channelCode === '') {
                throw new InvalidArgumentException('Virtual account payment requires an explicit channel_code.');
            }

            if ($data->amount < 10_000) {
                throw new InvalidArgumentException('Virtual account payment amount must be at least Rp10,000.');
            }

            $payload['channel_code'] = $channelCode;
            $payload['expiry_duration'] = max(3600, ((int) $data->expiresInMinutes) * 60);
            $response = $this->paymentClient->createVirtualAccount($payload);
        }

        $dataObj = data_get($response, 'data', []);
        $paymentId = trim((string) data_get($dataObj, 'payment_id'));

        if ($paymentId === '') {
            throw new RuntimeException('Komerce Payment API response does not contain data.payment_id.');
        }

        return new PaymentResultData(
            transactionId: $paymentId,
            paymentRef: $paymentId,
            status: strtolower((string) data_get($dataObj, 'status', 'pending')),
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

    private function createQrislyPayment(PaymentRequestData $data): PaymentResultData
    {
        if ($data->amount < 1000) {
            throw new InvalidArgumentException('QRISLY payment amount must be at least Rp1,000.');
        }

        $qrisId = config('komerce.qrisly_qris_id');
        $response = $this->qrislyClient->generateQris([
            'qris_id' => is_numeric($qrisId) ? (int) $qrisId : (string) $qrisId,
            'amount' => $data->amount,
            'output_type' => 'string',
            'unique_amount' => (bool) config('komerce.qrisly_unique_amount', true),
        ]);
        $dataObj = data_get($response, 'data', []);
        $historyId = trim((string) data_get($dataObj, 'history_id'));
        $qrString = trim((string) data_get($dataObj, 'qris_string'));

        if ($historyId === '' || $qrString === '') {
            throw new RuntimeException('QRISLY response is missing data.history_id or data.qris_string.');
        }

        return new PaymentResultData(
            transactionId: $historyId,
            paymentRef: $historyId,
            status: strtolower((string) data_get($dataObj, 'payment_status', 'unpaid')),
            paymentType: 'qris',
            channelCode: 'qrisly',
            qrString: $qrString,
            amount: (int) data_get($dataObj, 'final_amount', $data->amount),
            expiresAt: $this->nullableString(data_get($dataObj, 'expiry_time')),
            rawResponse: $response,
        );
    }

    private function assertPaymentCustomer(PaymentRequestData $data): void
    {
        foreach ([
            'name' => $data->customerName,
            'email' => $data->customerEmail,
            'phone' => $data->customerPhone,
        ] as $field => $value) {
            if (trim((string) $value) === '') {
                throw new InvalidArgumentException("Komerce Payment API requires a real customer {$field}.");
            }
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value !== '' ? $value : null;
    }

    public function verifyWebhook(array $payload, string $signature): PaymentWebhookResultData
    {
        $secret = (string) config('komerce.webhook_secret', '');
        $rawContent = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $isValid = KomerceCallbackSignature::isValid($rawContent, $secret, $signature);

        $statusStr = strtolower((string) data_get($payload, 'status', data_get($payload, 'payment_status', '')));
        $mappedStatus = match ($statusStr) {
            'paid' => 'paid',
            'expired' => 'expired',
            'canceled' => 'cancelled',
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
            'paid' => 'paid',
            'expired' => 'expired',
            'canceled' => 'cancelled',
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
