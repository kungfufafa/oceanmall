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

    private const UPLOAD_ENDPOINT = '/api/v1/qrisly/upload-qris';

    private const GENERATE_ENDPOINT = '/api/v1/qrisly/generate-qris';

    private const STATUS_ENDPOINT = '/api/v1/qrisly/payment-status';

    private const MAX_QRIS_IMAGE_BYTES = 5_242_880;

    /**
     * Upload a master QRIS image. Official: POST multipart/form-data
     * /api/v1/qrisly/upload-qris with `name` and `qris_image` (PNG/JPG, max 5MB).
     *
     * @return array<string, mixed>
     */
    public function uploadQris(string $name, string $imagePath): array
    {
        $name = trim($name);

        if ($name === '' || strlen($name) > 100) {
            throw new InvalidArgumentException('QRISLY upload-qris name is required and must be at most 100 characters.');
        }

        if (! is_file($imagePath) || ! is_readable($imagePath)) {
            throw new InvalidArgumentException('QRISLY upload-qris requires a readable PNG/JPG image file.');
        }

        $size = filesize($imagePath);
        if ($size === false || $size > self::MAX_QRIS_IMAGE_BYTES) {
            throw new InvalidArgumentException('QRISLY upload-qris image must be at most 5MB.');
        }

        $mime = (string) (mime_content_type($imagePath) ?: '');
        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/jpg'], true)) {
            throw new InvalidArgumentException('QRISLY upload-qris image must be PNG or JPG.');
        }

        $contents = file_get_contents($imagePath);
        if ($contents === false) {
            throw new InvalidArgumentException('QRISLY upload-qris could not read the image file.');
        }

        $response = $this->qrislyMultipartHttp()
            ->attach('qris_image', $contents, basename($imagePath), ['Content-Type' => $mime])
            ->post(self::UPLOAD_ENDPOINT, [
                'name' => $name,
            ])
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }

    /**
     * Generate a dynamic QRIS for an amount using the merchant's uploaded qris_id.
     *
     * Official: amount >= 1000 and <= 100000000; output_type string|image;
     * unique_amount required boolean.
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

        $amount = (int) $payload['amount'];
        if ($amount < 1000 || $amount > 100_000_000) {
            throw new InvalidArgumentException('QRISLY generate-qris amount must be between 1000 and 100000000.');
        }

        $outputType = strtolower(trim((string) ($payload['output_type'] ?? 'string')));
        if (! in_array($outputType, ['string', 'image'], true)) {
            throw new InvalidArgumentException('QRISLY generate-qris output_type must be string or image.');
        }

        $response = $this->qrislyHttp()
            ->post(self::GENERATE_ENDPOINT, [
                'qris_id' => $payload['qris_id'],
                'amount' => $amount,
                'output_type' => $outputType,
                'unique_amount' => array_key_exists('unique_amount', $payload)
                    ? (bool) $payload['unique_amount']
                    : (bool) config('komerce.qrisly_unique_amount', true),
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
