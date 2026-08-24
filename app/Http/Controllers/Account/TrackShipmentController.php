<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Shipping\RefreshShipmentTracking;
use App\Http\Controllers\Controller;
use App\Models\OrderShipment;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use RuntimeException;
use Shopper\Core\Models\Order;
use Throwable;

final class TrackShipmentController extends Controller
{
    public function __invoke(Order $order, OrderShipment $shipment, RefreshShipmentTracking $refreshTracking): RedirectResponse
    {
        abort_unless($order->customer_id === auth()->id(), 403);
        abort_unless($shipment->order_id === $order->id, 404);

        try {
            $refreshTracking->handle($shipment);
        } catch (RuntimeException $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors(['tracking' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Gagal memperbarui pelacakan. Coba lagi nanti.',
            ]);

            return back()->withErrors([
                'tracking' => 'Gagal memperbarui pelacakan. Coba lagi nanti.',
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Status pengiriman diperbarui.',
        ]);

        return back();
    }
}
