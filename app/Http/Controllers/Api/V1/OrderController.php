<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Account\CancelOrderByCustomer;
use App\Actions\Account\ConfirmOrderReceived;
use App\Actions\Checkout\ReconcileUnpaidKomerceOrderOnView;
use App\Actions\Checkout\ResolveKomercePaymentInstructions;
use App\Actions\Checkout\RetryKomercePayment;
use App\Actions\Checkout\SyncKomercePaymentStatus;
use App\Actions\Shipping\RefreshShipmentTracking;
use App\Actions\Shipping\RefreshShipmentTrackingOnView;
use App\Http\Controllers\Controller;
use App\Models\OrderShipment;
use App\Models\User;
use App\Support\BuyerShipmentPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Shopper\Core\Models\Order;
use Throwable;

final class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        $orders = Order::query()
            ->where('customer_id', $user->id)
            ->with(['items'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $orders->getCollection()->map(fn (Order $order): array => $this->orderSummary($order))->values()->all(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, string $number): JsonResponse
    {
        $order = $this->ownedOrder($request, $number);
        $order = resolve(ReconcileUnpaidKomerceOrderOnView::class)->handle($order);
        $order = resolve(RefreshShipmentTrackingOnView::class)->handle($order);
        $order->load(['items.product.media', 'shippingAddress']);

        $presenter = resolve(BuyerShipmentPresenter::class);
        $shipments = OrderShipment::query()
            ->where('order_id', $order->id)
            ->with('inventory')
            ->orderBy('id')
            ->get()
            ->map(fn (OrderShipment $shipment): array => $presenter->payload($shipment))
            ->values()
            ->all();

        $resolve = resolve(ResolveKomercePaymentInstructions::class);

        return response()->json([
            'data' => [
                ...$this->orderSummary($order),
                'items' => $order->items->map(static fn ($item): array => [
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (int) $item->unit_price_amount,
                ])->values()->all(),
                'shipments' => $shipments,
                'payment' => $resolve->handle($order),
                'can_retry_payment' => $resolve->canRetry($order),
                'can_cancel' => CancelOrderByCustomer::isCancellable($order),
            ],
        ]);
    }

    public function cancel(Request $request, string $number): JsonResponse
    {
        $order = $this->ownedOrder($request, $number);

        try {
            resolve(CancelOrderByCustomer::class)->handle($order);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Pesanan tidak bisa dibatalkan.',
                'errors' => $e->errors(),
            ], 422);
        }

        return $this->show($request, $number);
    }

    public function retryPayment(Request $request, string $number): JsonResponse
    {
        $order = $this->ownedOrder($request, $number);

        try {
            $instructions = resolve(RetryKomercePayment::class)->handle($order);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => ['payment' => $instructions]]);
    }

    public function syncPayment(Request $request, string $number): JsonResponse
    {
        $order = $this->ownedOrder($request, $number);

        try {
            $result = resolve(SyncKomercePaymentStatus::class)->handle($order);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Belum bisa cek status pembayaran.'], 422);
        }

        $order->refresh();

        return response()->json([
            'data' => [
                'sync' => $result,
                'payment_status' => $order->payment_status->value,
                'payment' => resolve(ResolveKomercePaymentInstructions::class)->handle($order),
            ],
        ]);
    }

    public function track(Request $request, string $number, int $shipment): JsonResponse
    {
        $order = $this->ownedOrder($request, $number);
        $row = OrderShipment::query()
            ->where('order_id', $order->id)
            ->where('id', $shipment)
            ->firstOrFail();

        try {
            resolve(RefreshShipmentTracking::class)->handle($row);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Gagal memperbarui pelacakan.'], 422);
        }

        return $this->show($request, $number);
    }

    public function confirmReceived(Request $request, string $number): JsonResponse
    {
        $order = $this->ownedOrder($request, $number);

        try {
            resolve(ConfirmOrderReceived::class)->handle($order);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Pesanan belum bisa ditandai diterima.',
                'errors' => $e->errors(),
            ], 422);
        }

        return $this->show($request, $number);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'payment_status' => $order->payment_status->value,
            'shipping_status' => $order->shipping_status->value,
            'amount' => (int) $order->price_amount,
            'currency' => $order->currency_code,
            'created_at' => optional($order->created_at)?->toIso8601String(),
            'cancelled_reason' => CancelOrderByCustomer::cancelledReason($order),
            'cancelled_reason_label' => CancelOrderByCustomer::cancelledReasonLabel($order),
        ];
    }

    private function ownedOrder(Request $request, string $number): Order
    {
        return Order::query()
            ->where('customer_id', $this->customer($request)->id)
            ->where('number', $number)
            ->firstOrFail();
    }

    private function customer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
