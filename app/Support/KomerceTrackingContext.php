<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Request-scoped courier + last provider payload for Shopper's track().
 *
 * Shipping Delivery tracking requires both `shipping` and `airway_bill`.
 * Shopper's ShippingDriver::track() only accepts the tracking number.
 */
final class KomerceTrackingContext
{
    private ?string $courier = null;

    private ?string $lastPhoneNumber = null;

    /** @var array<string, mixed> */
    private array $lastRaw = [];

    public function setCourier(string $courier): void
    {
        $courier = trim($courier);
        $this->courier = $courier !== '' ? $courier : null;
    }

    public function courier(): ?string
    {
        return $this->courier;
    }

    public function setLastPhoneNumber(?string $digits): void
    {
        $digits = $digits !== null ? preg_replace('/\D+/', '', $digits) : '';
        $digits = is_string($digits) ? substr($digits, -5) : '';
        $this->lastPhoneNumber = $digits !== '' ? $digits : null;
    }

    public function lastPhoneNumber(): ?string
    {
        return $this->lastPhoneNumber;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public function setLastRaw(array $raw): void
    {
        $this->lastRaw = $raw;
    }

    /**
     * @return array<string, mixed>
     */
    public function lastRaw(): array
    {
        return $this->lastRaw;
    }

    public function clear(): void
    {
        $this->courier = null;
        $this->lastPhoneNumber = null;
        $this->lastRaw = [];
    }
}
