<?php

declare(strict_types=1);

namespace App\Livewire\Shopper\SlideOvers;

use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Models\OrderShipment;
use App\Services\Komerce\ShippingDeliveryClient;
use Shopper\Core\Models\Carrier;
use Shopper\Livewire\SlideOvers\CreateShippingLabel as BaseCreateShippingLabel;

final class CreateShippingLabel extends BaseCreateShippingLabel
{
    public function mount(): void
    {
        parent::mount();

        $shipment = OrderShipment::query()
            ->where('order_id', $this->order->id)
            ->first();

        $carrierId = $this->order->shippingOption?->carrier_id;

        if (! $carrierId && $shipment) {
            $carrierCode = strtolower((string) ($shipment->carrier_code ?: $shipment->carrier_name));
            $carrierId = Carrier::query()
                ->where('slug', $carrierCode)
                ->orWhere('name', 'like', "%{$carrierCode}%")
                ->value('id');
        }

        if (! $carrierId) {
            $carrierId = Carrier::query()->where('is_enabled', true)->value('id');
        }

        $awb = $shipment?->awb ?? $shipment?->tracking_number;

        $state = $this->form->getState();
        $state['carrier_id'] = $carrierId;
        if ($awb !== null && $awb !== '') {
            $state['tracking_number'] = $awb;
            $state['tracking_url'] = url("/account/orders/{$this->order->id}/track");
        }

        $this->form->fill($state);
    }

    public function save(): void
    {
        $shipment = OrderShipment::query()
            ->where('order_id', $this->order->id)
            ->first();

        // If AWB is not yet generated, auto-trigger creation via Komerce API before saving Shopper native fulfillment
        if ($shipment && (! $shipment->awb && ! $shipment->tracking_number) && komerce_shipping_delivery_enabled()) {
            try {
                $deliveryClient = resolve(ShippingDeliveryClient::class);
                (new CreateRajaOngkirDeliveryForShipment((int) $shipment->id))->handle($deliveryClient);
                $shipment->refresh();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($shipment) {
            $awb = $shipment->awb ?? $shipment->tracking_number;
            $carrierCode = strtolower((string) ($shipment->carrier_code ?: $shipment->carrier_name));
            $carrierId = Carrier::query()
                ->where('slug', $carrierCode)
                ->orWhere('name', 'like', "%{$carrierCode}%")
                ->value('id') ?? Carrier::query()->where('is_enabled', true)->value('id');

            $state = $this->form->getState();
            if (empty($state['carrier_id'])) {
                $state['carrier_id'] = $carrierId;
            }
            if ($awb !== null && $awb !== '') {
                $state['tracking_number'] = $awb;
                $state['tracking_url'] = url("/account/orders/{$this->order->id}/track");
            }
            $this->form->fill($state);
        }

        parent::save();
    }
}
