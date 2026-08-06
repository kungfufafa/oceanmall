<?php

declare(strict_types=1);

namespace App\Livewire\Shopper;

use App\Models\OrderShipment;
use Illuminate\Contracts\View\View;
use Shopper\Livewire\Components\Orders\OrderSummary as BaseOrderSummary;

/**
 * Override Shopper's OrderSummary to read shipping cost from the custom
 * `order_shipments` table instead of `sh_carrier_options` (which is empty
 * for Komerce/RajaOngkir orders).
 */
final class OrderSummary extends BaseOrderSummary
{
    public function render(): View
    {
        $this->order->loadMissing(['shippingOption.carrier', 'paymentMethod', 'items', 'zone.countries']);

        $shippingOption = $this->order->shippingOption;
        $carrier = $shippingOption?->carrier;
        $paymentMethod = $this->order->paymentMethod;
        $subtotal = $this->order->total();

        // Read shipping cost from our order_shipments table (Komerce/RajaOngkir).
        // Fall back to Shopper's native shippingOption->price for non-Komerce orders.
        $shippingPrice = (int) OrderShipment::query()
            ->where('order_id', $this->order->id)
            ->sum('cost');

        if ($shippingPrice === 0 && $shippingOption) {
            $shippingPrice = $shippingOption->price ?? 0;
        }

        $taxAmount = $this->order->tax_amount ?? 0;
        $isTaxInclusive = $this->resolveTaxInclusivity();

        return view('shopper::livewire.components.orders.order-summary', [
            'subtotal' => $subtotal,
            'shippingPrice' => $shippingPrice,
            'shippingOption' => $shippingOption,
            'taxAmount' => $taxAmount,
            'isTaxInclusive' => $isTaxInclusive,
            'carrierLogoUrl' => $carrier?->logo(),
            'paymentLogoUrl' => $paymentMethod?->logo(),
            'itemsCount' => $this->order->items->count(),
            'total' => $this->order->price_amount !== null
                ? $this->order->price_amount
                : ($subtotal + $shippingPrice + ($isTaxInclusive ? 0 : $taxAmount)),
        ]);
    }

    // ponytail: resolveTaxInclusivity is private in parent, redeclare here.
    // Upgrade path: PR to Shopper to make it protected.
    private function resolveTaxInclusivity(): bool
    {
        $zone = $this->order->zone;

        if (! $zone) {
            return false;
        }

        $country = $zone->countries()->first();

        if (! $country) {
            return false;
        }

        $taxZone = resolve(\Shopper\Core\Models\Contracts\TaxZone::class)::query()
            ->where('country_id', $country->id)
            ->first();

        if (! $taxZone) {
            return false;
        }

        return $taxZone->is_tax_inclusive;
    }
}
