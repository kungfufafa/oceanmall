<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Shipping\PrintShipmentLabels;
use App\Http\Controllers\Controller;
use App\Services\Komerce\ShippingDeliveryClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Shopper\Core\Models\Order;
use Throwable;

final class PrintShipmentLabelController extends Controller
{
    public function __invoke(Request $request, Order $order, PrintShipmentLabels $printLabels): RedirectResponse|JsonResponse
    {
        Gate::authorize('print-shipment-label', $order);

        $validated = $request->validate([
            'page' => ['nullable', 'string', 'in:'.implode(',', ShippingDeliveryClient::LABEL_PAGES)],
            'shipment' => ['nullable', 'integer'],
        ]);

        $page = $validated['page'] ?? ShippingDeliveryClient::DEFAULT_LABEL_PAGE;

        try {
            $response = $printLabels->handle($order, $page, $validated['shipment'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['label' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['label' => __('Unable to generate the shipping label right now. Please try again later.')]);
        }

        $labelUrl = $this->resolveLabelUrl($response);

        if ($labelUrl !== null) {
            return redirect()->away($labelUrl);
        }

        return response()->json(['data' => $response['data'] ?? $response]);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function resolveLabelUrl(array $response): ?string
    {
        foreach (['data.path', 'data.url', 'data.label_url', 'path', 'url'] as $key) {
            $value = data_get($response, $key);

            if (is_string($value) && preg_match('#^https?://#i', $value) === 1) {
                return $value;
            }
        }

        return null;
    }
}
