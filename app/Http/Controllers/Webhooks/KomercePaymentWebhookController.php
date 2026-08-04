<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Actions\Checkout\MarkOrderPaidFromKomerce;
use App\Http\Controllers\Concerns\VerifiesKomerceWebhookSecret;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KomercePaymentWebhookController extends Controller
{
    use VerifiesKomerceWebhookSecret;

    public function __invoke(Request $request, MarkOrderPaidFromKomerce $markOrderPaid): JsonResponse
    {
        // Validate the callback secret first so a forged request is always
        // rejected, then short-circuit if the integration is switched off.
        if (! $this->hasValidKomerceWebhookSecret($request)) {
            return response()->json(['status' => 'invalid_secret'], 401);
        }

        if (! komerce_enabled()) {
            return response()->json(['status' => 'disabled'], 503);
        }

        // Expected Komerce callback payload:
        // { payment_id, order_id, status, amount }
        $payload = $request->validate([
            'payment_id' => ['required', 'string'],
            'order_id' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'amount' => ['nullable', 'integer'],
        ]);

        if (strtoupper($payload['status']) !== 'PAID') {
            return response()->json(['status' => 'ignored']);
        }

        $status = $markOrderPaid->handle($payload['payment_id']);

        return match ($status) {
            'no_transaction', 'no_order' => response()->json(['status' => $status], 404),
            'not_paid' => response()->json(['status' => $status], 409),
            'amount_mismatch' => response()->json(['status' => $status], 422),
            default => response()->json(['status' => $status]),
        };
    }
}
