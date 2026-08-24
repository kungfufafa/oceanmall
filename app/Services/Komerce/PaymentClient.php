<?php

declare(strict_types=1);

namespace App\Services\Komerce;

use App\Services\Komerce\Concerns\UsesKomerceHttp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class PaymentClient
{
    use UsesKomerceHttp;

    private const METHODS_ENDPOINT = '/api/v1/user/methods';

    private const CREATE_ENDPOINT = '/api/v1/user/payment/create';

    private const STATUS_ENDPOINT = '/api/v1/user/payment/status';

    private const CANCEL_ENDPOINT = '/api/v1/user/payment/cancel';

    private const METHODS_CACHE_KEY = 'komerce:payment-methods';

    private const METHODS_UNAVAILABLE_CACHE_KEY = 'komerce:payment-methods:unavailable';

    /**
     * Official payment-method catalog. Cached for one hour per provider guidance.
     * Failures are remembered briefly so checkout does not wait on a dead catalog.
     *
     * @return array<string, mixed>
     */
    public function listMethods(): array
    {
        $cached = Cache::get(self::METHODS_CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        if (Cache::has(self::METHODS_UNAVAILABLE_CACHE_KEY)) {
            throw new RuntimeException('Komerce payment methods catalog is temporarily unavailable.');
        }

        try {
            $response = $this->paymentHttp()
                ->connectTimeout(2)
                ->timeout(min(5, max(1, $this->timeout())))
                ->get(self::METHODS_ENDPOINT)
                ->throw()
                ->json();
        } catch (Throwable $e) {
            Cache::put(self::METHODS_UNAVAILABLE_CACHE_KEY, true, now()->addSeconds(45));
            Log::warning('Komerce payment methods catalog unavailable.', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $payload = is_array($response) ? $response : [];
        Cache::put(self::METHODS_CACHE_KEY, $payload, now()->addHour());

        return $payload;
    }

    /**
     * Create a virtual-account payment.
     *
     * Expected payload keys follow Komerce Payment docs:
     * channel_code, order_id, amount, customer{name,email,phone}, and optional
     * items, expiry_duration, callback_url, callback_API_KEY.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createVirtualAccount(array $payload): array
    {
        return $this->createPayment([
            ...$payload,
            'payment_type' => 'bank_transfer',
        ]);
    }

    /**
     * Create a QRIS payment.
     *
     * Expected payload keys follow Komerce Payment docs:
     * order_id, amount, customer{name,email,phone}, and optional items,
     * expiry_duration, callback_url, callback_API_KEY.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createQris(array $payload): array
    {
        unset($payload['channel_code']);

        return $this->createPayment([
            ...$payload,
            'payment_type' => 'qris',
        ]);
    }

    /**
     * Fetch payment status for a Komerce payment ID/reference.
     *
     * @return array<string, mixed>
     */
    public function getStatus(string $reference): array
    {
        $reference = trim($reference);

        if ($reference === '') {
            throw new InvalidArgumentException('Komerce payment reference is required.');
        }

        // Official contract permits at most one status request every three
        // seconds for each payment_id. Reuse the same response inside that
        // window instead of sending a second provider request.
        $response = Cache::remember(
            'komerce:payment-status:'.hash('sha256', $reference),
            now()->addSeconds(3),
            fn (): mixed => $this->paymentHttp()
                ->get(self::STATUS_ENDPOINT.'/'.rawurlencode($reference))
                ->throw()
                ->json(),
        );

        return is_array($response) ? $response : [];
    }

    /**
     * Cancel a pending Komerce payment.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $paymentId, string $reason = 'Order payment expired'): array
    {
        $response = $this->paymentHttp()
            ->post(self::CANCEL_ENDPOINT, [
                'payment_id' => $paymentId,
                'reason' => $reason,
            ])
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function createPayment(array $payload): array
    {
        $response = $this->paymentHttp()
            ->post(self::CREATE_ENDPOINT, $payload)
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }
}
