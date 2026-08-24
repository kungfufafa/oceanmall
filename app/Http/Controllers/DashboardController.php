<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Shopper\Core\Models\Order;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $recentOrders = Order::query()
            ->where('customer_id', $user->id)
            ->latest('id')
            ->limit(5)
            ->get(['id', 'number', 'price_amount', 'currency_code', 'status', 'payment_status', 'created_at'])
            ->map(static function (Order $order): array {
                $status = $order->status;
                $payment = $order->payment_status;

                $statusValue = $status instanceof \BackedEnum
                    ? $status->value
                    : (string) $status;
                $paymentValue = $payment instanceof \BackedEnum
                    ? $payment->value
                    : (string) $payment;

                return [
                    'id' => $order->id,
                    'number' => $order->number,
                    'price_amount' => (int) $order->price_amount,
                    'currency_code' => (string) $order->currency_code,
                    'status' => $statusValue,
                    'payment_status' => $paymentValue,
                    'created_at' => optional($order->created_at)?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('dashboard', [
            'recentOrders' => $recentOrders,
            'unreadNotifications' => $user->unreadNotifications()->count(),
        ]);
    }
}
