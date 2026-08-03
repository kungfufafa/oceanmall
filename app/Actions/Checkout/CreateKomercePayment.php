<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Services\Komerce\PaymentClient;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Shopper\Core\Models\Order;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;

final class CreateKomercePayment
{
    public function __construct(private readonly PaymentClient $payments) {}

    /**
     * Create a Komerce VA or QRIS payment for a pending order.
     *
     * @param  array<string, mixed>  $selectedMethod  Payment method data from checkout session.
     * @return array<string, mixed> Payment instructions to show the customer.
     */
    public function handle(Order $order, array $selectedMethod): array
    {
        $paymentType = (string) ($selectedMethod['payment_type'] ?? 'bank_transfer');
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

        $paymentId = $this->validatedPaymentId($response);

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
                    'komerce_response' => $response,
                ],
            ],
        );

        return [
            'payment_id' => $paymentId,
            'payment_type' => $paymentType,
            'virtual_account_number' => data_get($response, 'data.virtual_account_number'),
            'bank_code' => data_get($response, 'data.bank_code') ?? ($channelCode ?? null),
            'qris_string' => data_get($response, 'data.qris_string'),
            'expiry_date' => data_get($response, 'data.expiry_date'),
            'amount' => (int) (data_get($response, 'data.amount') ?? $order->price_amount),
            'currency_code' => $order->currency_code,
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function validatedPaymentId(array $response): string
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
