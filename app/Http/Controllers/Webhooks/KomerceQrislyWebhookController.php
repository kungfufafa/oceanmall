<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Actions\Checkout\MarkOrderPaidFromKomerce;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receiver for QRISLY outbound webhooks registered in Collaborator.
 *
 * Docs expect HTTP 200 with `{ success, message }`.
 * QRISLY docs do not define a signature header. This endpoint treats the
 * webhook only as a signal; MarkOrderPaidFromKomerce independently fetches
 * the authenticated status before any order transition.
 *
 * @see https://rajaongkir.com/docs/qrisly/getting-started/webhook
 */
final class KomerceQrislyWebhookController extends Controller
{
    public function __invoke(Request $request, MarkOrderPaidFromKomerce $markOrderPaid): JsonResponse
    {
        if (! qrisly_enabled()) {
            return response()->json([
                'success' => false,
                'message' => 'QRISLY is not configured.',
                'status' => 'disabled',
            ], 503);
        }

        $event = strtolower(trim((string) $request->input('event', '')));
        $paymentStatus = strtolower(trim((string) (
            $request->input('data.payment_status')
            ?? $request->input('data.status')
            ?? $request->input('payment_status')
            ?? ''
        )));

        $historyId = trim((string) (
            $request->input('data.qris_history_id')
            ?? $request->input('data.history_id')
            ?? $request->input('qris_history_id')
            ?? $request->input('history_id')
            ?? ''
        ));

        if ($historyId === '') {
            return response()->json([
                'success' => true,
                'message' => 'Ignored: missing history id.',
                'status' => 'ignored',
            ]);
        }

        $isSuccessEvent = $event === 'payment.success'
            || in_array($paymentStatus, ['paid', 'success', 'succeeded'], true);

        if (! $isSuccessEvent) {
            return response()->json([
                'success' => true,
                'message' => 'Ignored non-paid event.',
                'status' => 'ignored',
            ]);
        }

        $status = $markOrderPaid->handle($historyId, 'qrisly');

        $http = match ($status) {
            'no_transaction', 'no_order' => 404,
            'not_paid', 'cancelled_order' => 409,
            'amount_mismatch' => 422,
            default => 200,
        };

        return response()->json([
            'success' => $http === 200,
            'message' => $status,
            'status' => $status,
        ], $http);
    }
}
