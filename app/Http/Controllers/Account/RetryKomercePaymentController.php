<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Checkout\RetryKomercePayment;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Shopper\Core\Models\Order;
use Throwable;

final class RetryKomercePaymentController extends Controller
{
    public function __invoke(Order $order): RedirectResponse
    {
        abort_unless($order->customer_id === auth()->id(), 403);

        try {
            resolve(RetryKomercePayment::class)->handle($order);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'payment' => 'Gagal membuat ulang pembayaran. Coba lagi sebentar atau hubungi CS.',
            ]);
        }

        return redirect()
            ->route('account.orders.show', $order)
            ->with('success', 'Instruksi pembayaran baru sudah siap.');
    }
}
