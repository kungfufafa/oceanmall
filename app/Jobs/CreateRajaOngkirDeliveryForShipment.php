<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Shipping\SyncOrderShippingFromShipments;
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
        if (! komerce_enabled()) {
            return;
        }

        $shipment = OrderShipment::query()
            ->with(['order.shippingAddress', 'order.items', 'order.customer', 'inventory', 'lines.purchasable'])
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
            ]);

            if ($deliveryOrderNo === null) {
                throw new RuntimeException('Komerce store-order response did not include an order_no.');
            }

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

        $pickupResponse = $delivery->requestPickup([
            'pickup_date' => now()->addDay()->format('Y-m-d'),
            'pickup_time' => (string) config('komerce.pickup_time', '10:00'),
            'pickup_vehicle' => (string) config('komerce.pickup_vehicle', 'Motor'),
            'orders' => [
                ['order_no' => $deliveryOrderNo],
            ],
        ]);

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

        // Keep order-level shipping badges/notifications in sync as soon as
        // the warehouse delivery order is labeled (don't wait for webhook).
        $order = $shipment->order()->first();
        if ($order instanceof Order) {
            resolve(SyncOrderShippingFromShipments::class)->handle($order);
        }
    }

    /**
     * Build store-order payload matching
     * https://rajaongkir.com/docs/delivery-order-api/Store_order/store_order
     *
     * @return array<string, mixed>
     */
    private function storeOrderPayload(OrderShipment $shipment): array
    {
        /** @var Order|null $order */
        $order = $shipment->order;

        if (! $order instanceof Order) {
            throw new RuntimeException('Shipment is missing an order.');
        }

        $inventory = $shipment->inventory;
        $originId = $inventory?->getAttribute('rajaongkir_origin_id');
        if (! is_scalar($originId) || trim((string) $originId) === '') {
            throw new RuntimeException('Shipment inventory is missing a RajaOngkir origin id.');
        }

        $shippingAddress = $this->shippingAddress($order);
        $destinationId = data_get($shippingAddress, 'rajaongkir_destination_id')
            ?? data_get($shippingAddress, 'destination_id');

        if (! is_scalar($destinationId) || trim((string) $destinationId) === '') {
            throw new RuntimeException('Order is missing a RajaOngkir destination id.');
        }

        $orderDetails = $this->orderDetails($shipment, $order);
        $shippingCost = (int) $shipment->cost;
        $itemsSubtotal = array_sum(array_map(
            static fn (array $detail): int => (int) $detail['subtotal'],
            $orderDetails,
        ));

        $shipperAddress = trim(implode(', ', array_filter([
            $inventory?->street_address,
            $inventory?->street_address_plus,
            $inventory?->city,
            $inventory?->postal_code,
        ], static fn (mixed $part): bool => is_scalar($part) && trim((string) $part) !== '')));

        if ($shipperAddress === '') {
            $shipperAddress = trim(implode(', ', array_filter([
                shopper_setting('street_address'),
                shopper_setting('city'),
                shopper_setting('postal_code'),
            ], static fn (mixed $part): bool => is_scalar($part) && trim((string) $part) !== '')));
        }

        $carrierName = strtoupper(trim((string) ($shipment->carrier_name ?: $shipment->carrier_code)));
        $shippingType = trim((string) ($shipment->service_code ?: $shipment->service_name));

        return [
            'order_date' => now()->format('Y-m-d'),
            'brand_name' => (string) (shopper_setting('name') ?: shopper_setting('legal_name') ?: config('app.name')),
            'shipper_name' => (string) ($inventory?->name ?: shopper_setting('name') ?: config('app.name')),
            'shipper_phone' => (string) ($inventory?->phone_number ?: shopper_setting('phone_number') ?: ''),
            'shipper_destination_id' => (int) $originId,
            'shipper_address' => $shipperAddress,
            'shipper_email' => (string) ($inventory?->email ?: shopper_setting('email') ?: ''),
            'receiver_name' => $this->receiverName($shippingAddress),
            'receiver_phone' => (string) data_get($shippingAddress, 'phone_number', ''),
            'receiver_destination_id' => (int) $destinationId,
            'receiver_address' => (string) data_get($shippingAddress, 'street_address', ''),
            'receiver_email' => (string) ($order->customer?->email ?? ''),
            'shipping' => $carrierName,
            'shipping_type' => $shippingType,
            'shipping_cost' => $shippingCost,
            'shipping_cashback' => 0,
            'payment_method' => 'BANK TRANSFER',
            'service_fee' => 0,
            'additional_cost' => 0,
            'grand_total' => $itemsSubtotal + $shippingCost,
            'cod_value' => 0,
            'insurance_value' => 0,
            'notes' => null,
            'order_details' => $orderDetails,
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
    private function orderDetails(OrderShipment $shipment, Order $order): array
    {
        $order->loadMissing('items');

        return $shipment->lines
            ->map(function (OrderShipmentLine $line) use ($order): array {
                $purchasable = $line->purchasable;
                $qty = max(1, (int) $line->qty);
                $weight = $purchasable instanceof Model
                    ? $this->weightGrams($purchasable)
                    : 1;

                $name = $purchasable instanceof Model
                    ? (string) ($purchasable->getAttribute('name') ?? class_basename($purchasable))
                    : 'Item';

                $unitPrice = $this->unitPriceForLine($order, $line, $purchasable);

                return [
                    'product_name' => $name,
                    'product_variant_name' => $purchasable instanceof Model
                        ? (string) ($purchasable->getAttribute('sku') ?? '')
                        : '',
                    'product_price' => $unitPrice,
                    'product_weight' => $weight,
                    'product_width' => $purchasable instanceof Model
                        ? max(1, (int) ceil((float) ($purchasable->getAttribute('width_value') ?? 10)))
                        : 10,
                    'product_height' => $purchasable instanceof Model
                        ? max(1, (int) ceil((float) ($purchasable->getAttribute('height_value') ?? 10)))
                        : 10,
                    'product_length' => $purchasable instanceof Model
                        ? max(1, (int) ceil((float) ($purchasable->getAttribute('depth_value') ?? 10)))
                        : 10,
                    'qty' => $qty,
                    'subtotal' => $unitPrice * $qty,
                ];
            })
            ->values()
            ->all();
    }

    private function unitPriceForLine(Order $order, OrderShipmentLine $line, mixed $purchasable): int
    {
        foreach ($order->items as $item) {
            $sameMorph = $purchasable instanceof Model
                && (string) $item->getAttribute('product_type') === $purchasable->getMorphClass()
                && (int) $item->getAttribute('product_id') === (int) $purchasable->getKey();

            $sameName = $purchasable instanceof Model
                && (string) $item->name === (string) ($purchasable->getAttribute('name') ?? '');

            if ($sameMorph || $sameName) {
                return max(0, (int) $item->unit_price_amount);
            }
        }

        if ($purchasable instanceof Model) {
            foreach (['price_amount', 'amount'] as $attr) {
                $value = $purchasable->getAttribute($attr);
                if (is_numeric($value)) {
                    return max(0, (int) $value);
                }
            }
        }

        return 0;
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

        return in_array($status, ['PICKED_UP', 'PICKED UP', 'PICKEDUP', 'DIJEMPUT'], true)
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
            'shipping' => $shipment->carrier_name ?: $shipment->carrier_code,
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
