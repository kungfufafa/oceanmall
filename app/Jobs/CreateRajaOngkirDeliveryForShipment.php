<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\OrderShipment;
use App\Models\OrderShipmentLine;
use App\Services\Komerce\ShippingDeliveryClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Shopper\Core\Enum\Dimension\Weight;
use Shopper\Core\Models\Order;

final class CreateRajaOngkirDeliveryForShipment implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $orderShipmentId) {}

    public function handle(ShippingDeliveryClient $delivery): void
    {
        $shipment = OrderShipment::query()
            ->with(['order.shippingAddress', 'inventory', 'lines.purchasable'])
            ->findOrFail($this->orderShipmentId);

        if ($shipment->awb || $shipment->tracking_number) {
            return;
        }

        $metadata = $this->decodeMetadata($shipment->metadata);
        $deliveryOrderNo = $this->firstScalar($metadata, ['komerce.order_no']);
        $storeResponse = $this->arrayAt($metadata, 'komerce.store_order_response');
        $awb = $this->firstScalar($metadata, [
            'komerce.awb',
            'komerce.store_order_response.data.awb',
            'komerce.store_order_response.data.airway_bill',
            'komerce.store_order_response.data.tracking_number',
            'komerce.store_order_response.data.resi',
            'komerce.store_order_response.awb',
            'komerce.store_order_response.airway_bill',
            'komerce.store_order_response.tracking_number',
            'komerce.store_order_response.resi',
        ]);
        $trackingNumber = $this->firstScalar($metadata, [
            'komerce.tracking_number',
            'komerce.awb',
            'komerce.store_order_response.data.tracking_number',
            'komerce.store_order_response.data.awb',
            'komerce.store_order_response.data.airway_bill',
            'komerce.store_order_response.data.resi',
            'komerce.store_order_response.tracking_number',
            'komerce.store_order_response.awb',
            'komerce.store_order_response.airway_bill',
            'komerce.store_order_response.resi',
        ]);

        if ($deliveryOrderNo === null) {
            $payload = $this->storeOrderPayload($shipment);
            $storeResponse = $delivery->storeOrder($payload);
            $deliveryOrderNo = $this->firstScalar($storeResponse, [
                'data.order_no',
                'data.order_number',
                'order_no',
                'order_number',
            ]) ?? $payload['order_no'];

            $awb = $this->firstScalar($storeResponse, [
                'data.awb',
                'data.airway_bill',
                'data.tracking_number',
                'data.resi',
                'awb',
                'airway_bill',
                'tracking_number',
                'resi',
            ]);
            $trackingNumber = $this->firstScalar($storeResponse, [
                'data.tracking_number',
                'data.awb',
                'data.airway_bill',
                'data.resi',
                'tracking_number',
                'awb',
                'airway_bill',
                'resi',
            ]);

            $shipment->forceFill([
                'metadata' => $this->metadataAfterStore($shipment, $deliveryOrderNo, $awb, $trackingNumber, $storeResponse),
            ])->save();
        }

        $pickupResponse = $delivery->requestPickup(array_filter([
            'order_no' => $deliveryOrderNo,
            'awb' => $awb,
        ], static fn (mixed $value): bool => $value !== null));

        $shipment->forceFill([
            'awb' => $awb,
            'tracking_number' => $trackingNumber,
            'status' => $this->statusAfterPickup($pickupResponse),
            'metadata' => $this->metadataAfterPickup(
                $shipment,
                $deliveryOrderNo,
                $awb,
                $trackingNumber,
                $storeResponse,
                $pickupResponse,
            ),
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function storeOrderPayload(OrderShipment $shipment): array
    {
        /** @var Order|null $order */
        $order = $shipment->order;

        if (! $order instanceof Order) {
            throw new RuntimeException('Shipment is missing an order.');
        }

        $originId = $shipment->inventory?->getAttribute('rajaongkir_origin_id');
        if (! is_scalar($originId) || trim((string) $originId) === '') {
            throw new RuntimeException('Shipment inventory is missing a RajaOngkir origin id.');
        }

        $shippingAddress = $this->shippingAddress($order);
        $destinationId = data_get($shippingAddress, 'rajaongkir_destination_id')
            ?? data_get($shippingAddress, 'destination_id');

        if (! is_scalar($destinationId) || trim((string) $destinationId) === '') {
            throw new RuntimeException('Order is missing a RajaOngkir destination id.');
        }

        $items = $this->items($shipment);

        return [
            'order_no' => $this->orderNo($order, $shipment),
            'origin_id' => trim((string) $originId),
            'destination_id' => trim((string) $destinationId),
            'payment_method' => 'BANK TRANSFER',
            'service_fee' => 0,
            'receiver' => [
                'name' => $this->receiverName($shippingAddress),
                'phone' => (string) data_get($shippingAddress, 'phone_number', ''),
                'address' => (string) data_get($shippingAddress, 'street_address', ''),
                'address_detail' => data_get($shippingAddress, 'street_address_plus'),
                'postal_code' => (string) data_get($shippingAddress, 'postal_code', ''),
                'city' => (string) data_get($shippingAddress, 'city', ''),
                'state' => data_get($shippingAddress, 'state'),
            ],
            'shipping' => [
                'carrier_code' => (string) $shipment->carrier_code,
                'carrier_name' => $shipment->carrier_name,
                'service_code' => (string) $shipment->service_code,
                'service_name' => $shipment->service_name,
                'shipping_cost' => (int) $shipment->cost,
            ],
            'items' => $items,
            'total_weight' => array_sum(array_map(
                static fn (array $item): int => (int) $item['weight'] * (int) $item['quantity'],
                $items,
            )),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function shippingAddress(Order $order): array
    {
        $address = $order->shippingAddress;
        $metadata = $this->decodeMetadata($order->getAttribute('metadata'));
        $metadataAddress = data_get($metadata, 'shipping_address', []);

        if (! is_array($metadataAddress)) {
            $metadataAddress = [];
        }

        $shippingAddress = [
            'first_name' => $address?->first_name ?? '',
            'last_name' => $address?->last_name ?? '',
            'street_address' => $address?->street_address ?? '',
            'street_address_plus' => $address?->street_address_plus,
            'postal_code' => $address?->postal_code ?? '',
            'city' => $address?->city ?? '',
            'state' => $address?->getAttribute('state'),
            'phone_number' => $address?->phone ?? '',
            'country_name' => $address?->country_name,
        ];

        foreach (['country_id', 'rajaongkir_destination_id', 'destination_id'] as $key) {
            $value = $metadataAddress[$key]
                ?? $metadata[$key]
                ?? $address?->getAttribute($key);

            if ($value !== null && (! is_scalar($value) || trim((string) $value) !== '')) {
                $shippingAddress[$key] = $value;
            }
        }

        return $shippingAddress;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function items(OrderShipment $shipment): array
    {
        return $shipment->lines
            ->map(function (OrderShipmentLine $line): array {
                $purchasable = $line->purchasable;
                $weight = $purchasable instanceof Model
                    ? $this->weightGrams($purchasable)
                    : 1;

                return [
                    'name' => $purchasable instanceof Model
                        ? (string) ($purchasable->getAttribute('name') ?? class_basename($purchasable))
                        : 'Item',
                    'sku' => $purchasable instanceof Model ? $purchasable->getAttribute('sku') : null,
                    'quantity' => (int) $line->qty,
                    'weight' => $weight,
                ];
            })
            ->values()
            ->all();
    }

    private function weightGrams(Model $model): int
    {
        $value = max(0.0, (float) ($model->getAttribute('weight_value') ?? 1.0));
        $unit = $model->getAttribute('weight_unit');

        if ($unit instanceof Weight) {
            $unit = $unit->value;
        }

        return match (strtolower((string) ($unit ?: Weight::KG->value))) {
            Weight::G->value => max(1, (int) ceil($value)),
            Weight::LBS->value => max(1, (int) ceil($value * 453.59237)),
            default => max(1, (int) ceil($value * 1000)),
        };
    }

    private function receiverName(array $shippingAddress): string
    {
        return trim(implode(' ', array_filter([
            data_get($shippingAddress, 'first_name'),
            data_get($shippingAddress, 'last_name'),
        ], static fn (mixed $part): bool => is_scalar($part) && trim((string) $part) !== '')));
    }

    private function orderNo(Order $order, OrderShipment $shipment): string
    {
        return "{$order->number}-SHIP-{$shipment->id}";
    }

    /**
     * @param  array<string, mixed>  $shipment
     * @param  array<int, string>  $paths
     */
    private function firstScalar(array $shipment, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($shipment, $path);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $pickupResponse
     */
    private function statusAfterPickup(array $pickupResponse): string
    {
        $status = strtoupper((string) (data_get($pickupResponse, 'data.status') ?? data_get($pickupResponse, 'status', '')));

        return in_array($status, ['PICKED_UP', 'PICKED UP', 'PICKEDUP'], true)
            ? 'picked_up'
            : 'labeled';
    }

    /**
     * @param  array<string, mixed>  $storeResponse
     * @return array<string, mixed>
     */
    private function metadataAfterStore(
        OrderShipment $shipment,
        string $deliveryOrderNo,
        ?string $awb,
        ?string $trackingNumber,
        array $storeResponse,
    ): array {
        $metadata = $this->decodeMetadata($shipment->metadata);
        $komerce = data_get($metadata, 'komerce', []);

        if (! is_array($komerce)) {
            $komerce = [];
        }

        $metadata['komerce'] = array_filter(array_merge($komerce, [
            'order_no' => $deliveryOrderNo,
            'awb' => $awb,
            'tracking_number' => $trackingNumber,
            'store_order_response' => $storeResponse,
        ]), static fn (mixed $value): bool => $value !== null);

        return $metadata;
    }

    /**
     * @param  array<string, mixed>  $storeResponse
     * @param  array<string, mixed>  $pickupResponse
     * @return array<string, mixed>
     */
    private function metadataAfterPickup(
        OrderShipment $shipment,
        string $deliveryOrderNo,
        ?string $awb,
        ?string $trackingNumber,
        array $storeResponse,
        array $pickupResponse,
    ): array {
        $metadata = $this->metadataAfterStore($shipment, $deliveryOrderNo, $awb, $trackingNumber, $storeResponse);

        $metadata['komerce'] = array_filter(array_merge($metadata['komerce'], [
            'pickup_response' => $pickupResponse,
        ]), static fn (mixed $value): bool => $value !== null);

        return $metadata;
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayAt(array $metadata, string $path): array
    {
        $value = data_get($metadata, $path);

        return is_array($value) ? $value : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMetadata(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (! is_string($metadata) || trim($metadata) === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : [];
    }
}
