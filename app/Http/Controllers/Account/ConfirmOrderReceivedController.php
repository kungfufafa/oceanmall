<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Account\ConfirmOrderReceived;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Shopper\Core\Models\Order;

final class ConfirmOrderReceivedController extends Controller
{
    public function __invoke(Order $order, ConfirmOrderReceived $confirm): RedirectResponse
    {
        abort_unless($order->customer_id === auth()->id(), 403);

        try {
            $confirm->handle($order);
        } catch (ValidationException $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => collect($e->errors())->flatten()->first()
                    ?? 'Pesanan belum bisa ditandai diterima.',
            ]);

            return back()->withErrors($e->errors());
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Pesanan ditandai sudah diterima.',
        ]);

        return back();
    }
}
