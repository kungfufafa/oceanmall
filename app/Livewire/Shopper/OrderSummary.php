<?php

declare(strict_types=1);

namespace App\Livewire\Shopper;

use App\Models\OrderShipment;
use Illuminate\Contracts\View\View;
use Shopper\Core\Models\Contracts\TaxZone;
use Shopper\Livewire\Components\Orders\OrderSummary as BaseOrderSummary;

class OrderSummary extends BaseOrderSummary
{
    public function render(): View
    {
        $this->order->loadMissing(['shippingOption.carrier', 'paymentMethod', 'items', 'zone.countries']);

        $shippingOption = $this->order->shippingOption;
        $carrier = $shippingOption?->carrier;
        $paymentMethod = $this->order->paymentMethod;
        $subtotal = (float) $this->order->total();

        $shipmentsCost = (float) OrderShipment::query()->where('order_id', $this->order->id)->sum('cost');
        $shippingPrice = $shippingOption?->price 
            ?? ($shipmentsCost > 0 ? $shipmentsCost : max(0, (float) ($this->order->price_amount - $subtotal)));

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

    protected function resolveTaxInclusivity(): bool
    {
        $zone = $this->order->zone;

        if (! $zone) {
            return false;
        }

        $country = $zone->countries()->first();

        if (! $country) {
            return false;
        }

        $taxZone = resolve(TaxZone::class)::query()
            ->where('country_id', $country->id)
            ->first();

        if (! $taxZone) {
            return false;
        }

        return (bool) $taxZone->is_tax_inclusive;
    }
}
