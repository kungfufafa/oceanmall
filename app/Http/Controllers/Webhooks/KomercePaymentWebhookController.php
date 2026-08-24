<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Actions\Checkout\MarkOrderPaidFromKomerce;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shopper\Payment\Exceptions\PaymentException;
use Shopper\Payment\Facades\Payment;

final class KomercePaymentWebhookController extends Controller
{
    public function __invoke(Request $request, MarkOrderPaidFromKomerce $markOrderPaid): JsonResponse
    {
        try {
            $result = Payment::driver('komerce')->handleWebhook(
                payload: ['_raw_body' => $request->getContent()],
                headers: [
                    'x-callback-api-key' => (string) $request->header('X-Callback-Api-Key', ''),
                ],
            );
        } catch (PaymentException) {
            return response()->json(['status' => 'invalid_secret'], 401);
        }

        if ($result->isIgnored() || $result->action !== 'captured' || ! komerce_payment_enabled()) {
            return response()->json(['status' => 'handled']);
        }

        $payload = $request->validate([
            'payment_id' => ['required', 'string'],
            'order_id' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'amount' => ['nullable', 'integer'],
        ]);

        $markOrderPaid->handle($result->reference ?? $payload['payment_id']);

        return response()->json(['status' => 'handled']);
    }
}
