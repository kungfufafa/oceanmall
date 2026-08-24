<?php

declare(strict_types=1);

namespace App\Addons\KomerceRajaOngkir;

use App\Livewire\Shopper\InventoryForm;
use App\Livewire\Shopper\KomerceOrderShipping;
use App\Livewire\Shopper\OrderCustomer;
use App\Livewire\Shopper\OrderFulfillment;
use App\Livewire\Shopper\OrderSummary;
use App\Livewire\Shopper\Pages\OrderShipments;
use App\Livewire\Shopper\SlideOvers\CreateShippingLabel;
use Livewire\Livewire;
use Shopper\Addon\BaseAddon;
use Shopper\Core\Models\Order;
use Shopper\ShopperPanel;
use Shopper\View\OrderRenderHook;

/**
 * Shopper panel plugin for Komerce + RajaOngkir.
 *
 * Payment/shipping *drivers* stay on Shopper's Payment/Shipping managers
 * (same pattern as shopper/stripe). This addon owns the admin surface:
 * Livewire overrides, order-detail hook, and inventory origin field.
 */
final class KomerceRajaOngkirAddon extends BaseAddon
{
    public function getId(): string
    {
        return 'komerce-rajaongkir';
    }

    public function getName(): string
    {
        return 'Komerce / RajaOngkir';
    }

    public function register(ShopperPanel $panel): void
    {
        $panel->addonLivewireComponents([
            'settings.locations.form' => InventoryForm::class,
            'order-summary' => OrderSummary::class,
            'order-customer' => OrderCustomer::class,
            'order-fulfillment' => OrderFulfillment::class,
            'order-shipments' => OrderShipments::class,
            'slide-overs.create-shipping-label' => CreateShippingLabel::class,
        ]);

        Livewire::component('komerce-order-shipping', KomerceOrderShipping::class);

        $panel->renderHook(
            OrderRenderHook::DETAIL_MAIN_BEFORE,
            static function (): string {
                $order = self::resolveOrder();

                if (! $order instanceof Order) {
                    return '';
                }

                return view('shopper.partials.komerce-order-shipping-hook', [
                    'order' => $order,
                ])->render();
            },
        );
    }

    private static function resolveOrder(): ?Order
    {
        $routeOrder = request()->route('order') ?? request()->route('id');

        if (! $routeOrder && preg_match('#/orders/([^/]+)/detail#', request()->path(), $matches)) {
            $routeOrder = urldecode($matches[1]);
        }

        if (! $routeOrder && request()->header('referer')) {
            if (preg_match('#/orders/([^/]+)/detail#', (string) request()->header('referer'), $matches)) {
                $routeOrder = urldecode($matches[1]);
            }
        }

        return match (true) {
            $routeOrder instanceof Order => $routeOrder,
            is_numeric($routeOrder) => Order::query()->find($routeOrder),
            is_string($routeOrder) && $routeOrder !== '' => Order::query()->where('number', $routeOrder)->orWhere('id', $routeOrder)->first(),
            default => null,
        };
    }
}
