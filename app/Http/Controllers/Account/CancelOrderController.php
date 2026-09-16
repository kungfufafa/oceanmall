<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Account\CancelOrderByCustomer;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Shopper\Core\Models\Order;

final class CancelOrderController extends Controller
{
    public function __invoke(Order $order, CancelOrderByCustomer $cancel): RedirectResponse
    {
        abort_unless($order->customer_id === auth()->id(), 403);

        try {
            $cancel->handle($order);
        } catch (ValidationException $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => collect($e->errors())->flatten()->first()
                    ?? 'Pesanan tidak bisa dibatalkan.',
            ]);

            return back()->withErrors($e->errors());
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Pesanan dibatalkan.',
        ]);

        return back();
    }
}
