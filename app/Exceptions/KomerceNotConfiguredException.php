<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a Komerce/RajaOngkir client is used while the integration is
 * disabled (no API key / KOMERCE_ENABLED=false). Extends RuntimeException so
 * existing checkout/label/tracking error handling degrades gracefully.
 */
final class KomerceNotConfiguredException extends RuntimeException
{
    public static function make(): self
    {
        return new self(__('The Komerce payment & shipping integration is not configured.'));
    }
}
