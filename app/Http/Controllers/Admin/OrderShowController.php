<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\OrderShipmentOpsPresenter;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Shopper\Core\Models\Order;

/**
 * @deprecated Prefer Shopper /cpanel order detail + Livewire Komerce panel.
 * Kept for any residual Inertia callers; web routes redirect to cpanel.
 */
final class OrderShowController extends Controller
{
    public function __invoke(Order $order, OrderShipmentOpsPresenter $presenter): Response
    {
        Gate::authorize('override-allocation', $order);

        $order->loadMissing(['items', 'customer', 'shippingAddress']);
        $shipments = $presenter->shipments($order);
        $printableCount = $presenter->printableCount($shipments);

        return Inertia::render('admin/order-show', [
            'order' => [
                'id' => $order->id,
                'number' => (string) $order->number,
                'status' => $order->status?->value ?? $order->status,
                'payment_status' => $order->payment_status?->value ?? $order->payment_status,
                'shipping_status' => $order->shipping_status?->value ?? $order->shipping_status,
                'price_amount' => (int) $order->price_amount,
                'currency_code' => $order->currency_code,
                'customer_email' => $order->customer?->email,
                'customer_name' => trim(implode(' ', array_filter([
                    $order->customer?->first_name,
                    $order->customer?->last_name,
                ]))) ?: null,
            ],
            'shipments' => $shipments,
            'inventories' => $presenter->inventories(),
            'komerceEnabled' => komerce_enabled(),
            'canPrintAnyLabel' => $printableCount > 0,
            'printableShipmentCount' => $printableCount,
        ]);
    }
}
