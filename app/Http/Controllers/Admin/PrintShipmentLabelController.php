<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Shipping\PrintShipmentLabels;
use App\Http\Controllers\Controller;
use App\Services\Komerce\ShippingDeliveryClient;
use App\Support\KomerceLabelResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Shopper\Core\Models\Order;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class PrintShipmentLabelController extends Controller
{
    public function __invoke(
        Request $request,
        Order $order,
        PrintShipmentLabels $printLabels,
    ): RedirectResponse|JsonResponse|StreamedResponse|Response {
        Gate::authorize('print-shipment-label', $order);

        if (! komerce_enabled()) {
            return back()->withErrors([
                'label' => __('Shipping labels need Komerce delivery configured. Add your Shipping Delivery API key, then try again.'),
            ]);
        }

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

            return back()->withErrors([
                'label' => __('We could not generate this shipping label right now. Confirm the shipment has a delivery order and pickup, then try again.'),
            ]);
        }

        $labelUrl = KomerceLabelResponse::absoluteUrl($response);

        if ($labelUrl !== null) {
            return redirect()->away($labelUrl);
        }

        $pdf = KomerceLabelResponse::pdfBinary($response);

        if ($pdf !== null) {
            $filename = sprintf('label-order-%s.pdf', $order->number);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }

        return back()->withErrors([
            'label' => __('Komerce returned a label response we could not open. Check the delivery order in Collaborator, or try again in a moment.'),
        ]);
    }
}
