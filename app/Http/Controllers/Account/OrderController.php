<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\OrderShipment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\ShippingStatus;
use Shopper\Core\Models\Order;

final class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = (string) $request->string('tab', 'all');

        $query = Order::query()
            ->where('customer_id', auth()->id())
            ->with(['items.product.media', 'shippingAddress']);

        $query = match ($tab) {
            'not-shipped' => $query->where('shipping_status', ShippingStatus::Unfulfilled),
            'cancelled' => $query->where('status', OrderStatus::Cancelled),
            default => $query,
        };

        return Inertia::render('account/orders', [
            'orders' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => ['tab' => $tab],
        ]);
    }

    public function show(Order $order): Response
    {
        abort_unless($order->customer_id === auth()->id(), 403);

        $order->load([
            'items.product.media',
            'shippingAddress',
            'billingAddress',
            'shippingOption.carrier',
        ]);

        $order->shippingAddress?->append('full_name');
        $order->billingAddress?->append('full_name');

        $shipments = OrderShipment::query()
            ->where('order_id', $order->id)
            ->with('inventory')
            ->orderBy('id')
            ->get()
            ->map(static fn (OrderShipment $shipment): array => [
                'id' => $shipment->id,
                'inventory_name' => $shipment->inventory?->name,
                'status' => $shipment->status,
                'awb' => $shipment->awb,
                'tracking_number' => $shipment->tracking_number,
                'carrier' => $shipment->carrier_name ?? $shipment->carrier_code,
                'service' => $shipment->service_name ?? $shipment->service_code,
                'cost' => $shipment->cost,
                'currency' => $shipment->currency_code,
            ]);

        return Inertia::render('account/order-show', [
            'order' => $order,
            'shipments' => $shipments,
        ]);
    }
}
