<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Request-scoped RajaOngkir destination IDs for Shopper's ShippingDriver.
 *
 * Shopper's Address DTO has no subdistrict id, so checkout fills this context
 * before CarrierRateService / RajaOngkirDriver::calculateRates().
 */
final class RajaOngkirQuoteContext
{
    private ?string $originId = null;

    private ?string $destinationId = null;

    /** @var list<string> */
    private array $couriers = [];

    /**
     * @param  list<string>  $couriers
     */
    public function set(string $originId, string $destinationId, array $couriers = []): void
    {
        $this->originId = $originId;
        $this->destinationId = $destinationId;
        $this->couriers = array_values(array_filter(
            $couriers,
            static fn (string $courier): bool => trim($courier) !== '',
        ));
    }

    public function originId(): ?string
    {
        return $this->originId;
    }

    public function destinationId(): ?string
    {
        return $this->destinationId;
    }

    /**
     * @return list<string>
     */
    public function couriers(): array
    {
        return $this->couriers;
    }

    public function hasQuote(): bool
    {
        return $this->originId !== null
            && $this->originId !== ''
            && $this->destinationId !== null
            && $this->destinationId !== '';
    }

    public function clear(): void
    {
        $this->originId = null;
        $this->destinationId = null;
        $this->couriers = [];
    }
}
