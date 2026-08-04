<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Services\Komerce\PaymentClient;
use App\Services\Komerce\QrislyClient;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Shopper\Core\Models\Order;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;

final class CreateKomercePayment
{
    public function __construct(
        private readonly PaymentClient $payments,
        private readonly QrislyClient $qrisly,
    ) {}

    /**
     * Create a Komerce VA or QRIS payment for a pending order.
     *
     * QRIS routing:
     * - qrisly_enabled() → QRISLY product API (generate-qris)
     * - otherwise → Payment API `payment_type=qris`
     *
     * @param  array<string, mixed>  $selectedMethod  Payment method data from checkout session.
     * @return array<string, mixed> Payment instructions to show the customer.
     */
    public function handle(Order $order, array $selectedMethod): array
    {
        $paymentType = (string) ($selectedMethod['payment_type'] ?? 'bank_transfer');

        if ($paymentType === 'qris' && qrisly_enabled()) {
            return $this->createViaQrisly($order);
        }

        return $this->createViaPaymentApi($order, $selectedMethod, $paymentType);
    }

    /**
     * @param  array<string, mixed>  $selectedMethod
     * @return array<string, mixed>
     */
    private function createViaPaymentApi(Order $order, array $selectedMethod, string $paymentType): array
    {
        $channelCode = $selectedMethod['channel_code'] ?? null;

        $customer = $order->customer;
        $firstName = (string) ($customer?->first_name ?? '');
        $lastName = (string) ($customer?->last_name ?? '');
        $customerName = trim("{$firstName} {$lastName}") ?: 'Customer';

        $payload = [
            'order_id' => $order->number,
            'amount' => (int) $order->price_amount,
            'customer' => [
                'name' => $customerName,
                'email' => (string) ($customer?->email ?? ''),
                'phone' => (string) ($customer?->phone_number ?? ''),
            ],
            'items' => $this->paymentItems($order),
            'callback_url' => Route::has('webhooks.komerce.payment')
                ? route('webhooks.komerce.payment')
                : '',
            'callback_API_KEY' => (string) config('komerce.webhook_secret', ''),
        ];

        if ($channelCode !== null && $channelCode !== '') {
            $payload['channel_code'] = (string) $channelCode;
        }

        $response = $paymentType === 'qris'
            ? $this->payments->createQris($payload)
            : $this->payments->createVirtualAccount($payload);

        $paymentId = $this->validatedPaymentApiId($response);
        $expiryDate = data_get($response, 'data.expiry_date');

        $this->persistTransaction($order, $paymentId, $response, $expiryDate, 'payment_api', $paymentType);

        return [
            'payment_id' => $paymentId,
            'payment_type' => $paymentType,
            'provider' => 'payment_api',
            'virtual_account_number' => data_get($response, 'data.virtual_account_number'),
            'bank_code' => data_get($response, 'data.bank_code') ?? ($channelCode ?? null),
            'qris_string' => data_get($response, 'data.qris_string'),
            'expiry_date' => $expiryDate,
            'amount' => (int) (data_get($response, 'data.amount') ?? $order->price_amount),
            'currency_code' => $order->currency_code,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createViaQrisly(Order $order): array
    {
        $qrisId = config('komerce.qrisly_qris_id');

        $response = $this->qrisly->generateQris([
            'qris_id' => is_numeric($qrisId) ? (int) $qrisId : (string) $qrisId,
            'amount' => (int) $order->price_amount,
            'output_type' => 'string',
            'unique_amount' => (bool) config('komerce.qrisly_unique_amount', true),
        ]);

        $historyId = trim((string) (
            data_get($response, 'data.history_id')
            ?? data_get($response, 'history_id')
            ?? ''
        ));

        if ($historyId === '' || $this->responseIndicatesFailure($response)) {
            $message = data_get($response, 'meta.message')
                ?? data_get($response, 'message')
                ?? __('Unable to create QRISLY payment.');

            throw new RuntimeException('QRISLY payment creation failed: '.$message);
        }

        $expiryDate = data_get($response, 'data.expiry_time')
            ?? data_get($response, 'expiry_time');
        $qrisString = data_get($response, 'data.qris_string')
            ?? data_get($response, 'qris_string');
        $amount = data_get($response, 'data.final_amount')
            ?? data_get($response, 'data.original_amount')
            ?? data_get($response, 'final_amount')
            ?? $order->price_amount;

        $this->persistTransaction($order, $historyId, $response, $expiryDate, 'qrisly', 'qris');

        return [
            'payment_id' => $historyId,
            'payment_type' => 'qris',
            'provider' => 'qrisly',
            'virtual_account_number' => null,
            'bank_code' => null,
            'qris_string' => $qrisString,
            'expiry_date' => $expiryDate,
            'amount' => (int) $amount,
            'currency_code' => $order->currency_code,
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function persistTransaction(
        Order $order,
        string $paymentId,
        array $response,
        mixed $expiryDate,
        string $provider,
        string $paymentType,
    ): void {
        PaymentTransaction::query()->updateOrCreate(
            [
                'order_id' => $order->id,
                'reference' => $paymentId,
            ],
            [
                'payment_method_id' => $order->payment_method_id,
                'driver' => 'komerce',
                'type' => TransactionType::Initiate,
                'amount' => (int) $order->price_amount,
                'currency_code' => $order->currency_code,
                'status' => TransactionStatus::Pending,
                'metadata' => [
                    'komerce_payment_ref' => $paymentId,
                    'komerce_provider' => $provider,
                    'komerce_payment_type' => $paymentType,
                    'komerce_response' => $response,
                    'expiry_date' => $expiryDate,
                ],
            ],
        );

        $orderMetadata = $this->decodeMetadata($order->getAttribute('metadata'));
        $komerceMeta = is_array($orderMetadata['komerce'] ?? null) ? $orderMetadata['komerce'] : [];
        $komerceMeta['payment_ref'] = $paymentId;
        $komerceMeta['provider'] = $provider;
        $komerceMeta['payment_type'] = $paymentType;
        $komerceMeta['expiry_date'] = $expiryDate;

        if ($provider === 'qrisly') {
            $komerceMeta['qrisly_history_id'] = $paymentId;
            $komerceMeta['qrisly_qris_id'] = config('komerce.qrisly_qris_id');
        }

        $orderMetadata['komerce'] = $komerceMeta;
        $order->forceFill([
            'metadata' => json_encode($orderMetadata, JSON_THROW_ON_ERROR),
        ])->save();
    }

    /**
     * @return list<array{name: string, quantity: int, price: int}>
     */
    private function paymentItems(Order $order): array
    {
        $order->loadMissing('items');

        $items = $order->items
            ->map(static fn ($item): array => [
                'name' => (string) ($item->name ?: 'Item'),
                'quantity' => max(1, (int) $item->quantity),
                'price' => max(0, (int) $item->unit_price_amount),
            ])
            ->values()
            ->all();

        if ($items !== []) {
            return $items;
        }

        return [[
            'name' => 'Order '.$order->number,
            'quantity' => 1,
            'price' => max(0, (int) $order->price_amount),
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMetadata(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (! is_string($metadata) || trim($metadata) === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function validatedPaymentApiId(array $response): string
    {
        $paymentId = trim((string) (data_get($response, 'data.payment_id') ?? ''));

        if ($paymentId === '' || ! is_array(data_get($response, 'data')) || $this->responseIndicatesFailure($response)) {
            $message = data_get($response, 'meta.message')
                ?? data_get($response, 'message')
                ?? __('Unable to create Komerce payment.');

            throw new RuntimeException('Komerce payment creation failed: '.$message);
        }

        return $paymentId;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function responseIndicatesFailure(array $response): bool
    {
        if (array_key_exists('success', $response) && $response['success'] === false) {
            return true;
        }

        $status = mb_strtolower(trim((string) (data_get($response, 'meta.status') ?? '')));
        if ($status !== '' && ! in_array($status, ['success', 'succeeded', 'ok'], true)) {
            return true;
        }

        $code = data_get($response, 'meta.code');

        return is_numeric($code) && ((int) $code < 200 || (int) $code >= 300);
    }
}
