<?php

declare(strict_types=1);

namespace App\Payment;

use App\Services\Komerce\PaymentClient;
use App\Services\Komerce\QrislyClient;
use App\Support\KomerceCallbackSignature;
use App\Support\KomercePaymentLookupContext;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Shopper\Core\Models\Order;
use Shopper\Payment\DataTransferObjects\PaymentResult;
use Shopper\Payment\DataTransferObjects\WebhookResult;
use Shopper\Payment\Drivers\Driver;
use Shopper\Payment\Exceptions\PaymentException;

/**
 * Shopper payment driver for Komerce Payment API + optional QRISLY.
 *
 * Checkout and account retry go through this driver so /cpanel payment
 * methods, logos, and webhooks share one Shopper contract (same shape as Stripe).
 */
final class KomerceDriver extends Driver
{
    public function __construct(
        private readonly PaymentClient $payments,
        private readonly QrislyClient $qrisly,
        private readonly KomercePaymentLookupContext $lookupContext,
    ) {}

    public function code(): string
    {
        return 'komerce';
    }

    public function name(): string
    {
        return 'Komerce';
    }

    public function logo(): ?string
    {
        $path = public_path('images/payments/qris.svg');

        if (! is_file($path)) {
            return null;
        }

        return asset('images/payments/qris.svg').'?v='.filemtime($path);
    }

    public function isConfigured(): bool
    {
        return komerce_payment_enabled() || qrisly_enabled();
    }

    public function supportsWebhooks(): bool
    {
        return true;
    }

    public function supportsRefunds(): bool
    {
        return false;
    }

    public function initiatePayment(int $amount, string $currency, array $context = []): PaymentResult
    {
        if (! $this->isConfigured()) {
            throw PaymentException::notConfigured($this->code());
        }

        $paymentType = strtolower(trim((string) ($context['payment_type'] ?? 'bank_transfer')));

        if (! in_array($paymentType, ['bank_transfer', 'qris'], true)) {
            throw new RuntimeException('Unsupported Komerce payment type.');
        }

        $order = $this->resolveOrder($context);

        if ($paymentType === 'qris' && qrisly_enabled()) {
            return $this->createViaQrisly($order, $amount, $currency);
        }

        if (! komerce_payment_enabled()) {
            throw new RuntimeException('Komerce Payment API is not configured.');
        }

        return $this->createViaPaymentApi(
            $order,
            $amount,
            $currency,
            $paymentType,
            isset($context['channel_code']) ? (string) $context['channel_code'] : null,
        );
    }

    public function retrievePayment(string $reference): PaymentResult
    {
        $reference = trim($reference);

        if ($reference === '') {
            throw PaymentException::apiError($this->code(), 'Payment reference is required.');
        }

        $provider = $this->lookupContext->provider();

        if ($provider === 'qrisly') {
            if (! qrisly_enabled()) {
                throw PaymentException::notConfigured($this->code());
            }

            $response = $this->qrisly->getPaymentStatus($reference);
            $dataObj = data_get($response, 'data', []);
            $status = $this->normalizePaymentStatus((string) (
                data_get($dataObj, 'payment_status')
                ?? data_get($dataObj, 'status')
                ?? 'pending'
            ));
            $amount = data_get($dataObj, 'final_amount') ?? data_get($dataObj, 'original_amount') ?? data_get($dataObj, 'amount');

            return new PaymentResult(
                success: $status !== 'failed',
                status: $status,
                reference: $reference,
                amount: is_numeric($amount) ? (int) $amount : null,
                data: [
                    'provider' => 'qrisly',
                    'raw_response' => $response,
                ],
            );
        }

        if (! komerce_payment_enabled()) {
            throw PaymentException::notConfigured($this->code());
        }

        $response = $this->payments->getStatus($reference);
        $dataObj = data_get($response, 'data', []);
        $status = $this->normalizePaymentStatus((string) data_get($dataObj, 'status', 'pending'));

        return new PaymentResult(
            success: $status !== 'failed',
            status: $status,
            reference: $reference,
            amount: is_numeric(data_get($dataObj, 'amount')) ? (int) data_get($dataObj, 'amount') : null,
            data: [
                'provider' => 'payment_api',
                'raw_response' => $response,
            ],
        );
    }

    public function cancelPayment(string $reference): PaymentResult
    {
        $reference = trim($reference);

        if ($reference === '') {
            throw PaymentException::apiError($this->code(), 'Payment reference is required.');
        }

        if (! komerce_payment_enabled()) {
            throw PaymentException::notConfigured($this->code());
        }

        $response = $this->payments->cancel($reference);
        $dataObj = data_get($response, 'data', []);
        $status = $this->normalizePaymentStatus((string) data_get($dataObj, 'status', 'canceled'));

        return new PaymentResult(
            success: in_array($status, ['canceled', 'cancelled'], true) || $status === 'pending',
            status: $status === 'pending' ? 'canceled' : $status,
            reference: $reference,
            data: [
                'provider' => 'payment_api',
                'raw_response' => $response,
            ],
        );
    }

    public function handleWebhook(array $payload, array $headers = []): WebhookResult
    {
        $rawBody = is_string($payload['_raw_body'] ?? null) ? $payload['_raw_body'] : '';
        $signature = (string) ($headers['x-callback-api-key']
            ?? $headers['X-Callback-Api-Key']
            ?? $headers['X-Callback-API-Key']
            ?? '');
        $secret = (string) config('komerce.webhook_secret', '');

        if ($rawBody === '' || ! KomerceCallbackSignature::isValid($rawBody, $secret, $signature)) {
            throw PaymentException::webhookVerificationFailed($this->code());
        }

        $decoded = json_decode($rawBody, true);
        $body = is_array($decoded) ? $decoded : $payload;
        unset($body['_raw_body']);

        $status = strtoupper(trim((string) ($body['status'] ?? $body['payment_status'] ?? '')));
        $reference = trim((string) ($body['payment_id'] ?? $body['payment_reference'] ?? ''));
        $amount = is_numeric($body['amount'] ?? null) ? (int) $body['amount'] : null;

        return match ($status) {
            'PAID' => new WebhookResult(
                action: 'captured',
                reference: $reference !== '' ? $reference : null,
                amount: $amount,
                data: ['order_id' => $body['order_id'] ?? null, 'komerce_status' => $status],
            ),
            'EXPIRED' => new WebhookResult(
                action: 'failed',
                reference: $reference !== '' ? $reference : null,
                amount: $amount,
                data: ['komerce_status' => $status],
            ),
            'CANCELED', 'CANCELLED' => new WebhookResult(
                action: 'canceled',
                reference: $reference !== '' ? $reference : null,
                amount: $amount,
                data: ['komerce_status' => $status],
            ),
            default => WebhookResult::ignored(),
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveOrder(array $context): Order
    {
        if (($context['order'] ?? null) instanceof Order) {
            return $context['order'];
        }

        $orderId = $context['order_id'] ?? null;

        if (is_numeric($orderId)) {
            $order = Order::query()->find((int) $orderId);

            if ($order instanceof Order) {
                return $order;
            }
        }

        throw new RuntimeException('Komerce payment requires a Shopper order.');
    }

    private function createViaPaymentApi(
        Order $order,
        int $amount,
        string $currency,
        string $paymentType,
        ?string $channelCode,
    ): PaymentResult {
        $channelCode = $channelCode !== null ? trim($channelCode) : '';

        if ($paymentType === 'bank_transfer' && $channelCode === '') {
            throw new RuntimeException('Komerce virtual account requires an explicit payment channel.');
        }

        if (in_array($paymentType, ['bank_transfer', 'qris'], true) && $amount < 10_000) {
            throw new RuntimeException('Komerce Payment API requires a minimum amount of Rp10,000.');
        }

        $customer = $order->customer;
        $firstName = (string) ($customer?->first_name ?? '');
        $lastName = (string) ($customer?->last_name ?? '');
        $customerName = trim("{$firstName} {$lastName}");
        $customerEmail = trim((string) ($customer?->email ?? ''));
        $customerPhone = $this->customerPhone($order, $customer);

        if ($customerName === '' || $customerEmail === '') {
            throw new RuntimeException('Komerce payment requires the customer name and email from the order.');
        }

        $payload = [
            'order_id' => $order->number,
            'amount' => $amount,
            'customer' => [
                'name' => $customerName,
                'email' => $customerEmail,
                'phone' => $customerPhone,
            ],
            'items' => $this->paymentItems($order),
        ];

        $callbackUrl = Route::has('webhooks.komerce.payment')
            ? route('webhooks.komerce.payment')
            : '';
        $webhookSecret = trim((string) config('komerce.webhook_secret', ''));

        if ($callbackUrl !== '' && $webhookSecret !== '') {
            $payload['callback_url'] = $callbackUrl;
            $payload['callback_API_KEY'] = $webhookSecret;
        }

        if ($channelCode !== '') {
            $payload['channel_code'] = $channelCode;
        }

        if ($paymentType === 'bank_transfer') {
            $payload['expiry_duration'] = max(3600, (int) config('komerce.payment_expiry_duration', 86400));
        }

        $response = $paymentType === 'qris'
            ? $this->payments->createQris($payload)
            : $this->payments->createVirtualAccount($payload);

        $paymentId = $this->validatedPaymentApiId($response);
        $expiryDate = $this->paymentApiExpiry($response);
        $vaNumber = $this->paymentApiVaNumber($response);
        $qrisString = $this->paymentApiQrisString($response);
        $paymentUrl = $this->paymentApiPaymentUrl($response);

        if ($paymentType === 'bank_transfer' && blank($vaNumber) && blank($paymentUrl)) {
            throw new RuntimeException('Komerce VA payment created without a virtual account number.');
        }

        if ($paymentType === 'qris' && blank($qrisString) && blank($paymentUrl)) {
            throw new RuntimeException('Komerce QRIS payment created without a QR payload.');
        }

        return new PaymentResult(
            success: true,
            status: 'pending',
            reference: $paymentId,
            redirectUrl: $paymentUrl,
            amount: (int) (data_get($response, 'data.amount') ?? $amount),
            data: [
                'payment_id' => $paymentId,
                'payment_type' => $paymentType,
                'provider' => 'payment_api',
                'virtual_account_number' => $vaNumber,
                'bank_code' => data_get($response, 'data.bank_code')
                    ?? data_get($response, 'data.bank_name')
                    ?? ($channelCode !== '' ? $channelCode : null),
                'qris_string' => $qrisString,
                'payment_url' => $paymentUrl,
                'expiry_date' => $expiryDate,
                'amount' => (int) (data_get($response, 'data.amount') ?? $amount),
                'currency_code' => $currency,
                'raw_response' => $response,
            ],
        );
    }

    private function createViaQrisly(Order $order, int $amount, string $currency): PaymentResult
    {
        $qrisId = config('komerce.qrisly_qris_id');

        if ($amount < 1000) {
            throw new RuntimeException('QRISLY requires a minimum amount of Rp1,000.');
        }

        $response = $this->qrisly->generateQris([
            'qris_id' => is_numeric($qrisId) ? (int) $qrisId : (string) $qrisId,
            'amount' => $amount,
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
        $finalAmount = data_get($response, 'data.final_amount')
            ?? data_get($response, 'data.original_amount')
            ?? data_get($response, 'final_amount')
            ?? $amount;

        return new PaymentResult(
            success: true,
            status: 'pending',
            reference: $historyId,
            amount: (int) $finalAmount,
            data: [
                'payment_id' => $historyId,
                'payment_type' => 'qris',
                'provider' => 'qrisly',
                'virtual_account_number' => null,
                'bank_code' => null,
                'qris_string' => $qrisString,
                'payment_url' => null,
                'expiry_date' => $expiryDate,
                'amount' => (int) $finalAmount,
                'currency_code' => $currency,
                'raw_response' => $response,
            ],
        );
    }

    /**
     * @return list<array{name: string, quantity: int, price: int}>
     */
    private function paymentItems(Order $order): array
    {
        $order->loadMissing('items');

        $items = $order->items
            ->map(static fn ($item): array => [
                'name' => trim((string) $item->name),
                'quantity' => (int) $item->quantity,
                'price' => (int) $item->unit_price_amount,
            ])
            ->values()
            ->all();

        if ($items === []) {
            throw new RuntimeException('Komerce payment requires at least one real order item.');
        }

        $invalid = collect($items)->contains(static fn (array $item): bool => $item['name'] === ''
            || $item['quantity'] <= 0
            || $item['price'] <= 0);

        if ($invalid) {
            throw new RuntimeException('Order contains an item without a real name, quantity, or unit price.');
        }

        return $items;
    }

    private function customerPhone(Order $order, mixed $customer): string
    {
        $fromProfile = trim((string) ($customer?->phone_number ?? ''));
        if ($fromProfile !== '') {
            return $fromProfile;
        }

        $metadata = $this->decodeMetadata($order->getAttribute('metadata'));
        $fromShipping = trim((string) data_get($metadata, 'shipping_address.phone_number', ''));
        if ($fromShipping !== '') {
            return $fromShipping;
        }

        throw new RuntimeException('Komerce payment requires the customer phone from the profile or shipping address.');
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function paymentApiVaNumber(array $response): ?string
    {
        $value = data_get($response, 'data.va_number')
            ?? data_get($response, 'data.virtual_account_number');

        $value = is_string($value) || is_numeric($value) ? trim((string) $value) : '';

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function paymentApiQrisString(array $response): ?string
    {
        $value = data_get($response, 'data.qr_string')
            ?? data_get($response, 'data.qris_string');

        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function paymentApiExpiry(array $response): mixed
    {
        return data_get($response, 'data.expired_at')
            ?? data_get($response, 'data.expiry_date')
            ?? data_get($response, 'data.expiry_time');
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function paymentApiPaymentUrl(array $response): ?string
    {
        $value = data_get($response, 'data.payment_url');
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : null;
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

    private function normalizePaymentStatus(string $status): string
    {
        return match (strtolower(trim($status))) {
            'paid' => 'captured',
            'unpaid' => 'pending',
            'expired' => 'failed',
            'canceled', 'cancelled' => 'canceled',
            default => 'pending',
        };
    }
}
