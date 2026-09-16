<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Shopper\Core\Models\Order;

/**
 * Shared Cost-vs-Delivery pin contract.
 *
 * RajaOngkir Cost quotes only need origin/destination IDs.
 * Komerce Delivery calculate + AWB require origin and destination pin points.
 */
final class KomercePinReady
{
    public const INTRO = 'Resi Komerce membutuhkan pinpoint gudang dan tujuan.';

    public const ORIGIN_MISSING = 'Pinpoint gudang belum diisi.';

    public const DESTINATION_MISSING = 'Pinpoint tujuan belum diisi.';

    public function inventoryHasPinPoint(?Model $inventory): bool
    {
        return $inventory !== null
            && is_numeric($inventory->getAttribute('latitude'))
            && is_numeric($inventory->getAttribute('longitude'));
    }

    public function orderHasDestinationPin(?Order $order): bool
    {
        if (! $order instanceof Order) {
            return false;
        }

        $metadata = $order->getAttribute('metadata');

        if (is_string($metadata) && trim($metadata) !== '') {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($metadata)) {
            $metadata = [];
        }

        $address = data_get($metadata, 'shipping_address', []);
        if (! is_array($address)) {
            $address = [];
        }

        foreach ([$address, $metadata] as $source) {
            if ($this->arrayHasPinPoint($source)) {
                return true;
            }
        }

        $shippingAddress = $order->shippingAddress;

        return $this->inventoryHasPinPoint($shippingAddress);
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function addressHasDestinationPin(array $address): bool
    {
        return $this->arrayHasPinPoint($address);
    }

    /**
     * Cost quotes work without a destination pin. Delivery AWB does not.
     * Fail closed before payment when Shipping Delivery is enabled.
     *
     * @param  array<string, mixed>  $address
     */
    public function placeOrderDestinationError(array $address): ?string
    {
        if (! komerce_shipping_delivery_enabled()) {
            return null;
        }

        if ($this->addressHasDestinationPin($address)) {
            return null;
        }

        return $this->message(true, false);
    }

    public function message(bool $originReady, bool $destinationReady): ?string
    {
        if ($originReady && $destinationReady) {
            return null;
        }

        $parts = [self::INTRO];

        if (! $originReady) {
            $parts[] = self::ORIGIN_MISSING;
        }

        if (! $destinationReady) {
            $parts[] = self::DESTINATION_MISSING;
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function arrayHasPinPoint(array $source): bool
    {
        $pin = $source['rajaongkir_pin_point'] ?? $source['pin_point'] ?? null;
        if (is_string($pin) && str_contains($pin, ',')) {
            return true;
        }

        return is_numeric($source['latitude'] ?? null)
            && is_numeric($source['longitude'] ?? null);
    }
}
