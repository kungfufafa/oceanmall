<?php

declare(strict_types=1);

namespace App\Support;

/**
 * RajaOngkir district + pin stored on address.metadata.
 * Merge so a street-only update cannot wipe checkout/AWB fields.
 */
final class AddressRajaOngkirMetadata
{
    /**
     * @var list<string>
     */
    public const KEYS = [
        'rajaongkir_destination_id',
        'rajaongkir_destination_label',
        'rajaongkir_pin_point',
    ];

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public static function merge(mixed $existing, array $incoming): array
    {
        $metadata = self::decode($existing);

        foreach (self::KEYS as $key) {
            if (! array_key_exists($key, $incoming)) {
                continue;
            }

            $value = is_scalar($incoming[$key]) ? trim((string) $incoming[$key]) : '';

            if ($value === '') {
                unset($metadata[$key]);
            } else {
                $metadata[$key] = $value;
            }
        }

        return $metadata;
    }

    /**
     * Only the RajaOngkir keys that were actually posted.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function posted(array $validated, callable $wasPosted): array
    {
        $posted = [];

        foreach (self::KEYS as $key) {
            if (! $wasPosted($key)) {
                continue;
            }

            $posted[$key] = $validated[$key] ?? '';
        }

        return $posted;
    }

    /**
     * @return array<string, mixed>
     */
    public static function decode(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_string($metadata) && trim($metadata) !== '') {
            $decoded = json_decode($metadata, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
