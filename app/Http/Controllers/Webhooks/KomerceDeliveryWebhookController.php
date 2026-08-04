<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Actions\Shipping\ApplyDeliveryWebhookStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receiver for Komerce Shipping Delivery webhooks registered at
 * https://collaborator.komerce.id/webhook (Add Webhook URL).
 *
 * Payload: { order_no, cnote, status }
 */
final class KomerceDeliveryWebhookController extends Controller
{
    public function __invoke(Request $request, ApplyDeliveryWebhookStatus $apply): JsonResponse
    {
        if (! komerce_enabled()) {
            return response()->json(['status' => 'disabled'], 503);
        }

        $payload = $request->validate([
            'order_no' => ['nullable', 'string'],
            'cnote' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        $orderNo = trim((string) ($payload['order_no'] ?? ''));
        $cnote = isset($payload['cnote']) ? trim((string) $payload['cnote']) : null;
        $status = isset($payload['status']) ? trim((string) $payload['status']) : null;

        if ($orderNo === '' && ($cnote === null || $cnote === '')) {
            return response()->json(['status' => 'ignored']);
        }

        $result = $apply->handle($orderNo, $cnote, $status);

        return match ($result) {
            'not_found' => response()->json(['status' => $result], 404),
            default => response()->json(['status' => $result]),
        };
    }
}
