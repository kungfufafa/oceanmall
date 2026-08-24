<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use RuntimeException;
use Shopper\Core\Models\Order;
use Shopper\Payment\DataTransferObjects\PaymentResult;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Facades\Payment;
use Shopper\Payment\Models\PaymentTransaction;

final class CreateKomercePayment
{
    /**
     * Create a Komerce VA or QRIS payment for a pending order via Shopper's
     * registered `komerce` payment driver, then persist instructions on the order.
     *
     * @param  array<string, mixed>  $selectedMethod  Payment method data from checkout session.
     * @return array<string, mixed> Payment instructions to show the customer.
     */
    public function handle(Order $order, array $selectedMethod): array
    {
        $result = Payment::driver('komerce')->initiatePayment(
            amount: (int) $order->price_amount,
            currency: (string) $order->currency_code,
            context: [
                'order' => $order,
                'order_id' => $order->id,
                'order_number' => $order->number,
                'payment_type' => $selectedMethod['payment_type'] ?? 'bank_transfer',
                'channel_code' => $selectedMethod['channel_code'] ?? null,
            ],
        );

        if (! $result->success || $result->reference === null || trim($result->reference) === '') {
            throw new RuntimeException($result->message ?? __('Unable to create Komerce payment.'));
        }

        $instructions = $this->instructionsFromResult($result, $order);
        $rawResponse = is_array($result->data['raw_response'] ?? null) ? $result->data['raw_response'] : [];
        $provider = (string) ($result->data['provider'] ?? 'payment_api');
        $paymentType = (string) ($result->data['payment_type'] ?? 'bank_transfer');

        $this->persistTransaction(
            $order,
            $result->reference,
            $rawResponse,
            $instructions['expiry_date'] ?? null,
            $provider,
            $paymentType,
        );
        $this->persistInstructions($order, $instructions);

        return $instructions;
    }

    /**
     * @return array<string, mixed>
     */
    private function instructionsFromResult(PaymentResult $result, Order $order): array
    {
        $data = $result->data;

        return [
            'payment_id' => $result->reference,
            'payment_type' => $data['payment_type'] ?? 'bank_transfer',
            'provider' => $data['provider'] ?? 'payment_api',
            'virtual_account_number' => $data['virtual_account_number'] ?? null,
            'bank_code' => $data['bank_code'] ?? null,
            'qris_string' => $data['qris_string'] ?? null,
            'payment_url' => $data['payment_url'] ?? $result->redirectUrl,
            'expiry_date' => $data['expiry_date'] ?? null,
            'amount' => (int) ($data['amount'] ?? $result->amount ?? $order->price_amount),
            'currency_code' => $data['currency_code'] ?? $order->currency_code,
        ];
    }

    /**
     * @param  array<string, mixed>  $instructions
     */
    private function persistInstructions(Order $order, array $instructions): void
    {
        $order->refresh();
        $orderMetadata = $this->decodeMetadata($order->getAttribute('metadata'));
        $komerceMeta = is_array($orderMetadata['komerce'] ?? null) ? $orderMetadata['komerce'] : [];
        $komerceMeta['payment_instructions'] = $instructions;
        $orderMetadata['komerce'] = $komerceMeta;

        $order->forceFill([
            'metadata' => json_encode($orderMetadata, JSON_THROW_ON_ERROR),
        ])->save();
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
}
