<?php

declare(strict_types=1);

namespace Tests\Feature\Cpanel;

use App\Addons\KomerceRajaOngkir\KomerceRajaOngkirAddon;
use App\Livewire\Shopper\InventoryForm;
use App\Livewire\Shopper\KomerceOrderShipping;
use App\Livewire\Shopper\OrderCustomer;
use App\Livewire\Shopper\OrderFulfillment;
use App\Livewire\Shopper\OrderSummary;
use App\Livewire\Shopper\Pages\OrderShipments;
use App\Livewire\Shopper\SlideOvers\CreateShippingLabel;
use Livewire\Mechanisms\ComponentRegistry;
use Shopper\Facades\Shopper;
use Tests\TestCase;

final class KomerceRajaOngkirAddonTest extends TestCase
{
    public function test_shopper_registers_komerce_rajaongkir_addon_and_panel_components(): void
    {
        $this->assertTrue(Shopper::hasAddon('komerce-rajaongkir'));
        $this->assertInstanceOf(KomerceRajaOngkirAddon::class, Shopper::getAddon('komerce-rajaongkir'));
        $this->assertSame('Komerce / RajaOngkir', Shopper::getAddon('komerce-rajaongkir')->getName());

        $registry = app(ComponentRegistry::class);

        $this->assertSame(KomerceOrderShipping::class, $registry->getClass('komerce-order-shipping'));
        $this->assertSame(InventoryForm::class, $registry->getClass('shopper-settings.locations.form'));
        $this->assertSame(OrderSummary::class, $registry->getClass('shopper-order-summary'));
        $this->assertSame(OrderCustomer::class, $registry->getClass('shopper-order-customer'));
        $this->assertSame(OrderFulfillment::class, $registry->getClass('shopper-order-fulfillment'));
        $this->assertSame(OrderShipments::class, $registry->getClass('shopper-order-shipments'));
        $this->assertSame(CreateShippingLabel::class, $registry->getClass('shopper-slide-overs.create-shipping-label'));
        $this->assertSame(OrderShipments::class, config('shopper.components.order.pages.order-shipments'));
    }
}
