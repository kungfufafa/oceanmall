<?php

declare(strict_types=1);

namespace App\Services\Komerce;

use App\Exceptions\KomerceNotConfiguredException;
use App\Services\Komerce\Concerns\UsesKomerceHttp;
use InvalidArgumentException;

/**
 * QRISLY product API (optional).
 *
 * Docs: https://rajaongkir.com/docs/qrisly/getting-started/available-endpoints
 * Only used when {@see qrisly_enabled()} is true.
 */
final class QrislyClient
{
    use UsesKomerceHttp;

    private const GENERATE_ENDPOINT = '/api/v1/qrisly/generate-qris';

    private const STATUS_ENDPOINT = '/api/v1/qrisly/payment-status';

    /**
     * Generate a dynamic QRIS for an amount using the merchant's uploaded qris_id.
     *
     * @param  array{qris_id: int|string, amount: int, output_type?: string, unique_amount?: bool}  $payload
     * @return array<string, mixed>
     */
    public function generateQris(array $payload): array
    {
        $this->ensureQrislyEnabled();

        if (! isset($payload['qris_id'], $payload['amount'])) {
            throw new InvalidArgumentException('QRISLY generate-qris requires qris_id and amount.');
        }

        $response = $this->qrislyHttp()
            ->post(self::GENERATE_ENDPOINT, [
                'qris_id' => $payload['qris_id'],
                'amount' => (int) $payload['amount'],
                'output_type' => $payload['output_type'] ?? 'string',
                'unique_amount' => (bool) ($payload['unique_amount'] ?? config('komerce.qrisly_unique_amount', true)),
            ])
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaymentStatus(string|int $historyId): array
    {
        $this->ensureQrislyEnabled();

        $historyId = trim((string) $historyId);

        if ($historyId === '') {
            throw new InvalidArgumentException('QRISLY payment-status requires a history_id.');
        }

        $response = $this->qrislyHttp()
            ->get(self::STATUS_ENDPOINT.'/'.rawurlencode($historyId))
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }

    private function ensureQrislyEnabled(): void
    {
        if (! qrisly_enabled()) {
            throw KomerceNotConfiguredException::make();
        }
    }
}
