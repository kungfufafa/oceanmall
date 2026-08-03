<?php

declare(strict_types=1);

namespace App\Actions\Shipping;

use App\Models\OrderShipment;
use App\Services\Komerce\ShippingDeliveryClient;
use RuntimeException;
use Shopper\Core\Models\Order;

final readonly class PrintShipmentLabels
{
    public function __construct(private ShippingDeliveryClient $delivery) {}

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

        $orderNos = $shipments
            ->map(static fn (OrderShipment $shipment): ?string => self::deliveryOrderNo($shipment))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($orderNos === []) {
            throw new RuntimeException('No shipment with a RajaOngkir delivery order is ready for label printing.');
        }

        return $this->delivery->printLabel($orderNos, $page);
    }

    private static function deliveryOrderNo(OrderShipment $shipment): ?string
    {
        $orderNo = data_get($shipment->metadata, 'komerce.order_no');

        return is_scalar($orderNo) && trim((string) $orderNo) !== ''
            ? trim((string) $orderNo)
            : null;
    }
}
