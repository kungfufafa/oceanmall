<?php

declare(strict_types=1);

namespace App\Actions\Shipping;

use App\Models\OrderShipment;
use App\Services\Komerce\ShippingDeliveryClient;
use App\Shipping\Drivers\KomerceShippingDriver;
use App\Support\OrderShipmentOpsPresenter;
use RuntimeException;
use Shopper\Core\Models\Order;
use Shopper\Shipping\Exceptions\ShippingException;
use Shopper\Shipping\Facades\Shipping;

final readonly class PrintShipmentLabels
{
    /**
     * Generate RajaOngkir shipping labels for an order's shipments.
     *
     * Only shipments that already have a Komerce delivery order number
     * (i.e. the delivery order has been stored) are eligible. When
     * $shipmentId is provided, only that shipment is printed.
     *
     * @return array<string, mixed> The raw Komerce print-label response.
     *
     * @throws RuntimeException When no eligible shipment/label is available.
     */
    public function handle(Order $order, string $page = ShippingDeliveryClient::DEFAULT_LABEL_PAGE, ?int $shipmentId = null): array
    {
        $shipments = OrderShipment::query()
            ->where('order_id', $order->id)
            ->when($shipmentId !== null, fn ($query) => $query->whereKey($shipmentId))
            ->orderBy('id')
            ->get();

        $presenter = resolve(OrderShipmentOpsPresenter::class);
        $orderNos = $shipments
            ->filter(static fn (OrderShipment $shipment): bool => $presenter->canPrintLabel($shipment))
            ->map(static fn (OrderShipment $shipment): ?string => $presenter->deliveryOrderNo($shipment))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($orderNos === []) {
            $registered = $shipments->contains(
                static fn (OrderShipment $shipment): bool => $presenter->deliveryOrderNo($shipment) !== null,
            );

            throw new RuntimeException(
                $registered
                    ? 'Request pickup Komerce dulu. Stiker resi baru bisa dicetak setelah pickup berhasil.'
                    : 'Daftarkan paket ke Komerce dan request pickup dulu sebelum mencetak stiker resi.',
            );
        }

        if (! komerce_shipping_delivery_enabled()) {
            throw new RuntimeException(
                'Shipping labels need Komerce delivery configured. Add your Shipping Delivery API key, then try again.',
            );
        }

        $driver = Shipping::driver('komerce');

        if (! $driver instanceof KomerceShippingDriver) {
            throw new RuntimeException('Komerce shipping driver is not registered.');
        }

        try {
            return $driver->printLabels($orderNos, $page);
        } catch (ShippingException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }
}
