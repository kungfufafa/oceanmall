<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Account\ConfirmOrderReceived;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Shopper\Core\Models\Order;

final class ConfirmOrderReceivedController extends Controller
{
    public function __invoke(Order $order, ConfirmOrderReceived $confirm): RedirectResponse
    {
        abort_unless($order->customer_id === auth()->id(), 403);

        try {
            $confirm->handle($order);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', __('Thanks! Order marked as received.'));
    }
}
