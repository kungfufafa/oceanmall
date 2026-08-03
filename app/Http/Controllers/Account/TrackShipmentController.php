<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Shipping\RefreshShipmentTracking;
use App\Http\Controllers\Controller;
use App\Models\OrderShipment;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Shopper\Core\Models\Order;

final class TrackShipmentController extends Controller
{
    public function __invoke(Order $order, OrderShipment $shipment, RefreshShipmentTracking $refreshTracking): RedirectResponse
    {
        abort_unless($order->customer_id === auth()->id(), 403);
        abort_unless($shipment->order_id === $order->id, 404);

        try {
            $refreshTracking->handle($shipment);
        } catch (RuntimeException $e) {
            return back()->withErrors(['tracking' => $e->getMessage()]);
        }

        return back()->with('status', __('Tracking updated.'));
    }
}
