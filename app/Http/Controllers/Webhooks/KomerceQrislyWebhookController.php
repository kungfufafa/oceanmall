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
        $ack = [
            'success' => true,
            'message' => 'Webhook received successfully',
        ];

        if (! qrisly_enabled()) {
            return response()->json($ack);
        }

        $event = strtolower(trim((string) $request->input('event', '')));
        $paymentStatus = strtolower(trim((string) (
            $request->input('data.status')
            ?? $request->input('data.payment_status')
            ?? $request->input('payment_status')
            ?? ''
        )));

        $historyId = trim((string) (
            $request->input('data.history_id')
            ?? $request->input('history_id')
            ?? $request->input('data.qris_history_id')
            ?? $request->input('qris_history_id')
            ?? ''
        ));

        $isSuccessEvent = $event === 'payment.success'
            || in_array($paymentStatus, ['paid', 'success', 'succeeded'], true);

        if ($historyId !== '' && $isSuccessEvent) {
            $markOrderPaid->handle($historyId, 'qrisly');
        }

        return response()->json($ack);
    }
}
