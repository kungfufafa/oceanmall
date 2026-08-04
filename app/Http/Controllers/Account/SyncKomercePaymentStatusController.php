<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Checkout\SyncKomercePaymentStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Shopper\Core\Models\Order;
use Throwable;

final class SyncKomercePaymentStatusController extends Controller
{
    public function __invoke(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->customer_id === auth()->id(), 403);

        $silent = $request->boolean('silent');

        try {
            $result = resolve(SyncKomercePaymentStatus::class)->handle($order);
        } catch (Throwable $e) {
            report($e);

            if ($silent) {
                return back();
            }

            return back()->withErrors([
                'payment' => 'Belum bisa cek status pembayaran. Coba lagi sebentar.',
            ]);
        }

        return match ($result) {
            'handled', 'already_paid' => back()->with('success', 'Pembayaran berhasil. Terima kasih!'),
            'no_payment' => $silent
                ? back()
                : back()->withErrors([
                    'payment' => 'Belum ada instruksi pembayaran untuk pesanan ini.',
                ]),
            default => $silent
                ? back()
                : back()->with('info', 'Belum terdeteksi. Pastikan sudah bayar, lalu cek lagi.'),
        };
    }
}
