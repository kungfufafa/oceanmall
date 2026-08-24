<?php

declare(strict_types=1);

namespace App\Actions\Shipping;

use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Models\OrderShipment;
use RuntimeException;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Throwable;

/**
 * Issue RajaOngkir AWB + official label, then record them on Shopper.
 * Shopper never invents a tracking number here.
 */
final class IssueRajaOngkirFulfillment
{
    /**
     * @return array{awbs: list<string>, print_route: string, errors: list<string>}
     */
    public function handle(Order $order): array
    {
        if (! komerce_shipping_delivery_enabled()) {
            throw new RuntimeException('Komerce Shipping Delivery belum dikonfigurasi.');
        }

        if ($order->payment_status !== PaymentStatus::Paid) {
            throw new RuntimeException('Terbitkan resi RajaOngkir setelah pembayaran lunas.');
        }

        $shipments = resolve(EnsureOrderShipments::class)->handle($order);

        if ($shipments->isEmpty()) {
            throw new RuntimeException('Pesanan ini belum punya alokasi gudang untuk delivery order RajaOngkir.');
        }

        $errors = [];

        foreach ($shipments as $shipment) {
            if (filled($shipment->awb) || filled($shipment->tracking_number)) {
                continue;
            }

            try {
                (new CreateRajaOngkirDeliveryForShipment((int) $shipment->id))->handle();
            } catch (Throwable $e) {
                report($e);
                $errors[] = $e->getMessage();
            }
        }

        resolve(SyncOrderShippingFromShipments::class)->handle($order->refresh());

        $awbs = OrderShipment::query()
            ->where('order_id', $order->id)
            ->get()
            ->map(static fn (OrderShipment $shipment): string => trim((string) ($shipment->awb ?: $shipment->tracking_number)))
            ->filter()
            ->values()
            ->all();

        if ($awbs === []) {
            throw new RuntimeException($errors[0] ?? 'RajaOngkir belum mengembalikan AWB.');
        }

        return [
            'awbs' => $awbs,
            'print_route' => route('shopper.orders.fulfillment.print-label', $order),
            'errors' => $errors,
        ];
    }
}
