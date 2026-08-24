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
 * Official endpoint table lists merchant webhook as PUT. This controller
 * is registered for POST and PUT. Payload: { order_no, cnote, status }
 *
 * The official Delivery webhook contract does not define a signature header.
 * Its payload is treated only as a refresh signal; the action verifies the
 * resulting state through the authenticated Shipping Delivery tracking API.
 */
final class KomerceDeliveryWebhookController extends Controller
{
    public function __invoke(Request $request, ApplyDeliveryWebhookStatus $apply): JsonResponse
    {
        if (! komerce_shipping_delivery_enabled()) {
            return response()->json(['status' => 'disabled'], 503);
        }

        $payload = $request->validate([
            'order_no' => ['required', 'string'],
            'cnote' => ['nullable', 'string'],
            'status' => ['required', 'string'],
        ]);

        $orderNo = trim((string) ($payload['order_no'] ?? ''));
        $cnote = isset($payload['cnote']) ? trim((string) $payload['cnote']) : null;
        $status = isset($payload['status']) ? trim((string) $payload['status']) : null;

        $apply->handle($orderNo, $cnote, $status);

        return response()->json(['status' => 'handled']);
    }
}
