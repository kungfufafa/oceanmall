<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Shipping\SyncOrderShippingFromShipments;
use App\Models\OrderShipment;
use App\Models\OrderShipmentLine;
use App\Services\Komerce\ShippingDeliveryClient;
use App\Shipping\RajaOngkirCourier;
use App\Support\KomerceFulfillmentContext;
use App\Support\KomerceLabelResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Shopper\Core\Enum\Dimension\Length;
use Shopper\Core\Enum\Dimension\Weight;
use Shopper\Core\Models\Order;
use Shopper\Shipping\DataTransferObjects\Address as ShippingAddress;
use Shopper\Shipping\Exceptions\ShippingException;
use Shopper\Shipping\Facades\Shipping;
use Throwable;

final class CreateRajaOngkirDeliveryForShipment implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $backoff = 60;

    public int $uniqueFor = 300;

    public function __construct(public readonly int $orderShipmentId) {}

    public function uniqueId(): string
    {
        return (string) $this->orderShipmentId;
    }

    public function handle(): void
    {
        if (! komerce_shipping_delivery_enabled()) {
            return;
        }

        $shipment = OrderShipment::query()
            ->with(['order.shippingAddress', 'order.items', 'order.customer', 'inventory', 'lines.purchasable'])
            ->findOrFail($this->orderShipmentId);

        $existingAwb = trim((string) ($shipment->awb ?: $shipment->tracking_number));
        if ($existingAwb !== '') {
            return;
        }

        $context = resolve(KomerceFulfillmentContext::class);
        $context->set($shipment);

        try {
            Shipping::driver('komerce')->createShipment(
                from: $this->originAddress($shipment),
                to: $this->destinationAddress($shipment),
                packages: [],
                serviceCode: trim((string) $shipment->carrier_code).':'.trim((string) $shipment->service_code),
            );
            $this->clearFulfillmentError($shipment->refresh());
        } catch (ShippingException $e) {
            $this->persistFulfillmentError($shipment->refresh(), $e->getMessage());

            throw new RuntimeException($e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            $this->persistFulfillmentError($shipment->refresh(), $e->getMessage());

            throw $e;
        } finally {
            $context->clear();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $shipment = OrderShipment::query()->find($this->orderShipmentId);

        if ($shipment instanceof OrderShipment) {
            $this->persistFulfillmentError(
                $shipment,
                $exception?->getMessage() ?? 'RajaOngkir delivery job failed.',
            );
        }
    }

    public function execute(ShippingDeliveryClient $delivery): void
    {
        $shipment = OrderShipment::query()
            ->with(['order.shippingAddress', 'order.items', 'order.customer', 'inventory', 'lines.purchasable'])
            ->findOrFail($this->orderShipmentId);

        if (is_scalar($shipment->awb) && trim((string) $shipment->awb) !== '') {
            return;
        }

        $metadata = $this->decodeMetadata($shipment->metadata);
        $deliveryOrderId = $this->firstScalar($metadata, [
            'komerce.order_id',
            'komerce.store_order_response.data.order_id',
        ]);
        $deliveryOrderNo = $this->firstScalar($metadata, ['komerce.order_no']);
        $storeResponse = $this->arrayAt($metadata, 'komerce.store_order_response');

        if ($deliveryOrderNo === null) {
            if ($deliveryOrderId !== null) {
                throw new RuntimeException('Stored Komerce delivery metadata has an order_id without an order_no.');
            }

            $payload = $this->storeOrderPayload($shipment);
            $storeResponse = $delivery->storeOrder($payload);
            $this->assertSuccessfulEnvelope($storeResponse, 'store-order');

            $deliveryOrderId = $this->firstScalar($storeResponse, ['data.order_id']);
            $deliveryOrderNo = $this->firstScalar($storeResponse, ['data.order_no']);

            if ($deliveryOrderId === null || $deliveryOrderNo === null) {
                throw new RuntimeException('Komerce store-order response must include data.order_id and data.order_no.');
            }

            $shipment->forceFill([
                'metadata' => $this->metadataAfterStore(
                    $shipment,
                    $deliveryOrderId,
                    $deliveryOrderNo,
                    $storeResponse,
                ),
            ])->save();
        } elseif ($deliveryOrderId === null) {
            throw new RuntimeException('Stored Komerce delivery metadata is missing its order_id.');
        }

        $pickupVehicle = $this->requiredString(config('komerce.pickup_vehicle'), 'Komerce pickup vehicle');
        if (! in_array($pickupVehicle, ['Motor', 'Mobil', 'Truk'], true)) {
            throw new RuntimeException('Komerce pickup vehicle must be Motor, Mobil, or Truk.');
        }

        $pickupTime = $this->requiredString(config('komerce.pickup_time'), 'Komerce pickup time');
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $pickupTime) !== 1) {
            throw new RuntimeException('Komerce pickup time must use the HH:mm:ss format.');
        }

        $pickupResponse = $delivery->requestPickup([
            'pickup_date' => $this->pickupDate($pickupTime),
            'pickup_time' => $pickupTime,
            'pickup_vehicle' => $pickupVehicle,
            'orders' => [
                ['order_no' => $deliveryOrderNo],
            ],
        ]);

        $pickupItem = $this->successfulPickupItem($pickupResponse, $deliveryOrderNo);
        $awb = trim((string) ($pickupItem['awb'] ?? ''));
        if ($awb === '') {
            $detail = $delivery->detailOrder($deliveryOrderNo);
            $awb = trim((string) (data_get($detail, 'data.awb') ?? ''));
        }
        if ($awb === '') {
            throw new RuntimeException(sprintf(
                'RajaOngkir pickup succeeded for [%s] but AWB is not issued yet (order still packing).',
                $deliveryOrderNo,
            ));
        }
        $trackingNumber = $awb;
        $labelResponse = $this->printOfficialLabel($delivery, $deliveryOrderNo);

        $shipment->forceFill([
            'awb' => $awb,
            'tracking_number' => $trackingNumber,
            'status' => 'labeled',
            'metadata' => $this->metadataAfterPickup(
                $shipment,
                $deliveryOrderId,
                $deliveryOrderNo,
                $awb,
                $storeResponse,
                $pickupResponse,
                $labelResponse,
            ),
        ])->save();

        // Keep order-level shipping badges/notifications in sync as soon as
        // the warehouse delivery order is labeled (don't wait for webhook).
        $order = $shipment->order()->first();
        if ($order instanceof Order) {
            resolve(SyncOrderShippingFromShipments::class)->handle($order);
        }
    }

    private function originAddress(OrderShipment $shipment): ShippingAddress
    {
        $inventory = $shipment->inventory;

        return new ShippingAddress(
            firstName: (string) ($inventory?->getAttribute('name') ?? shopper_setting('name') ?? 'Warehouse'),
            lastName: '',
            street: (string) ($inventory?->getAttribute('street_address') ?? ''),
            city: (string) ($inventory?->getAttribute('city') ?? ''),
            postalCode: (string) ($inventory?->getAttribute('postal_code') ?? ''),
            state: (string) ($inventory?->getAttribute('state') ?? ''),
            country: 'ID',
            street2: $inventory?->getAttribute('street_address_plus') ? (string) $inventory->getAttribute('street_address_plus') : null,
            phone: $inventory?->getAttribute('phone_number') ? (string) $inventory->getAttribute('phone_number') : null,
            email: $inventory?->getAttribute('email') ? (string) $inventory->getAttribute('email') : null,
        );
    }

    private function destinationAddress(OrderShipment $shipment): ShippingAddress
    {
        $order = $shipment->order;
        $fields = $order instanceof Order ? $this->shippingAddress($order) : [];

        return new ShippingAddress(
            firstName: (string) ($fields['first_name'] ?? ''),
            lastName: (string) ($fields['last_name'] ?? ''),
            street: (string) ($fields['street_address'] ?? ''),
            city: (string) ($fields['city'] ?? ''),
            postalCode: (string) ($fields['postal_code'] ?? ''),
            state: (string) ($fields['state'] ?? ''),
            country: 'ID',
            street2: isset($fields['street_address_plus']) ? (string) $fields['street_address_plus'] : null,
            phone: isset($fields['phone_number']) ? (string) $fields['phone_number'] : null,
        );
    }

    /**
     * Build store-order payload matching
     * https://rajaongkir.com/docs/delivery-order-api/Store_order/store_order
     *
     * @return array<string, mixed>
     */
    public function storeOrderPayload(OrderShipment $shipment): array
    {
        /** @var Order|null $order */
        $order = $shipment->order;

        if (! $order instanceof Order) {
            throw new RuntimeException('Shipment is missing an order.');
        }

        $inventory = $shipment->inventory;
        if (! $inventory instanceof Model) {
            throw new RuntimeException('Shipment is missing its inventory pickup location.');
        }

        $originId = $this->requiredPositiveInteger(
            $inventory->getAttribute('rajaongkir_origin_id'),
            'Shipment inventory RajaOngkir origin id',
        );

        $shippingAddress = $this->shippingAddress($order);
        $destinationId = $this->requiredPositiveInteger(
            data_get($shippingAddress, 'rajaongkir_destination_id')
                ?? data_get($shippingAddress, 'destination_id'),
            'Order RajaOngkir destination id',
        );

        $deliveryRate = $this->deliveryRate($shipment);
        $orderDetails = $this->orderDetails($shipment, $order);
        if ($orderDetails === []) {
            throw new RuntimeException('Shipment must contain at least one order detail.');
        }

        $carrierName = $this->requiredString($deliveryRate['shipping_name'] ?? null, 'Delivery rate shipping_name');
        $shippingType = $this->requiredString($deliveryRate['service_name'] ?? null, 'Delivery rate service_name');
        $shippingCost = $this->requiredNonNegativeInteger(
            $deliveryRate['shipping_cost'] ?? null,
            'Delivery rate shipping_cost',
        );
        $shippingCashback = $this->requiredNonNegativeInteger(
            $deliveryRate['shipping_cashback'] ?? null,
            'Delivery rate shipping_cashback',
        );
        $serviceFee = $this->requiredNonNegativeInteger(
            $deliveryRate['service_fee'] ?? null,
            'Delivery rate service_fee',
        );

        if ($shippingCost < 1) {
            throw new RuntimeException('Delivery rate shipping_cost must be greater than zero.');
        }

        if ($shippingCashback > $shippingCost) {
            throw new RuntimeException('Delivery rate shipping_cashback cannot exceed shipping_cost.');
        }

        if ((int) $shipment->cost !== $shippingCost) {
            throw new RuntimeException('Shipment cost does not match the persisted Shipping Delivery rate.');
        }

        $shipperAddress = $this->formattedAddress([
            $inventory->getAttribute('street_address'),
            $inventory->getAttribute('street_address_plus'),
            $inventory->getAttribute('city'),
            $inventory->getAttribute('postal_code'),
        ], 'Shipment inventory address');
        $receiverAddress = $this->formattedAddress([
            data_get($shippingAddress, 'street_address'),
            data_get($shippingAddress, 'street_address_plus'),
            data_get($shippingAddress, 'city'),
            data_get($shippingAddress, 'postal_code'),
        ], 'Order receiver address');

        return [
            'order_date' => now()->format('Y-m-d'),
            'brand_name' => $this->requiredString(
                shopper_setting('name') ?: shopper_setting('legal_name') ?: config('app.name'),
                'Store brand name',
            ),
            'shipper_name' => $this->requiredString($inventory->getAttribute('name'), 'Shipment inventory name'),
            'shipper_phone' => $this->requiredPhone(
                $inventory->getAttribute('phone_number'),
                'Shipment inventory phone',
            ),
            'shipper_destination_id' => $originId,
            'shipper_address' => $shipperAddress,
            'shipper_email' => $this->requiredEmail($inventory->getAttribute('email'), 'Shipment inventory email'),
            'receiver_name' => $this->receiverName($shippingAddress),
            'receiver_phone' => $this->requiredPhone(
                data_get($shippingAddress, 'phone_number'),
                'Order receiver phone',
            ),
            'receiver_destination_id' => $destinationId,
            'receiver_address' => $receiverAddress,
            'receiver_email' => (string) ($order->customer?->email ?? ''),
            'shipping' => $carrierName,
            'shipping_type' => $shippingType,
            'shipping_cost' => $shippingCost,
            'shipping_cashback' => $shippingCashback,
            'payment_method' => $this->paymentMethod($order),
            'service_fee' => $serviceFee,
            'additional_cost' => $this->requiredNonNegativeInteger(
                $deliveryRate['additional_cost'] ?? 0,
                'Delivery rate additional_cost',
            ),
            'grand_total' => $this->requiredNonNegativeInteger(
                $deliveryRate['grandtotal']
                    ?? $deliveryRate['grand_total']
                    ?? (array_sum(array_map(
                        static fn (array $detail): int => (int) ($detail['subtotal'] ?? 0),
                        $orderDetails,
                    )) + $shippingCost),
                'Delivery rate grandtotal',
            ),
            'cod_value' => $this->requiredNonNegativeInteger(
                $deliveryRate['cod_value'] ?? 0,
                'Delivery rate cod_value',
            ),
            'insurance_value' => $this->requiredNonNegativeInteger(
                $deliveryRate['insurance_value'] ?? 0,
                'Delivery rate insurance_value',
            ),
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
            'first_name' => $address?->first_name ?? data_get($metadataAddress, 'first_name'),
            'last_name' => $address?->last_name ?? data_get($metadataAddress, 'last_name'),
            'street_address' => $address?->street_address ?? data_get($metadataAddress, 'street_address'),
            'street_address_plus' => $address?->street_address_plus ?? data_get($metadataAddress, 'street_address_plus'),
            'postal_code' => $address?->postal_code ?? data_get($metadataAddress, 'postal_code'),
            'city' => $address?->city ?? data_get($metadataAddress, 'city'),
            'state' => $address?->getAttribute('state') ?? data_get($metadataAddress, 'state'),
            'phone_number' => $address?->phone_number
                ?? $address?->phone
                ?? data_get($metadataAddress, 'phone_number')
                ?? data_get($metadata, 'phone_number'),
            'country_name' => $address?->country_name,
        ];

        foreach ([
            'country_id',
            'rajaongkir_destination_id',
            'destination_id',
            'rajaongkir_pin_point',
            'pin_point',
            'latitude',
            'longitude',
        ] as $key) {
            $value = $metadataAddress[$key]
                ?? $metadata[$key]
                ?? $address?->getAttribute($key);

            if (is_scalar($value) && trim((string) $value) !== '') {
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
                if (! $purchasable instanceof Model) {
                    throw new RuntimeException(sprintf('Shipment line [%d] has no purchasable model.', $line->getKey()));
                }

                $qty = (int) $line->qty;
                if ($qty < 1) {
                    throw new RuntimeException(sprintf('Shipment line [%d] quantity must be greater than zero.', $line->getKey()));
                }

                $weight = $this->weightGrams($purchasable);
                $name = $this->requiredString(
                    $purchasable->getAttribute('name'),
                    sprintf('Shipment line [%d] product name', $line->getKey()),
                );
                $variantName = $this->requiredString(
                    $purchasable->getAttribute('sku'),
                    sprintf('Shipment line [%d] product variant/SKU', $line->getKey()),
                );

                $unitPrice = $this->unitPriceForLine($order, $line, $purchasable);

                return [
                    'product_name' => $name,
                    'product_variant_name' => $variantName,
                    'product_price' => $unitPrice,
                    'product_weight' => $weight,
                    'product_width' => $this->dimensionCentimeters($purchasable, 'width'),
                    'product_height' => $this->dimensionCentimeters($purchasable, 'height'),
                    'product_length' => $this->dimensionCentimeters($purchasable, 'depth'),
                    'qty' => $qty,
                    'subtotal' => $unitPrice * $qty,
                ];
            })
            ->values()
            ->all();
    }

    private function unitPriceForLine(Order $order, OrderShipmentLine $line, mixed $purchasable): int
    {
        if (! $purchasable instanceof Model) {
            throw new RuntimeException(sprintf('Shipment line [%d] has no purchasable model.', $line->getKey()));
        }

        foreach ($order->items as $item) {
            $sameMorph = (string) $item->getAttribute('product_type') === $purchasable->getMorphClass()
                && (int) $item->getAttribute('product_id') === (int) $purchasable->getKey();

            if ($sameMorph) {
                return $this->requiredNonNegativeInteger(
                    $item->getAttribute('unit_price_amount'),
                    sprintf('Shipment line [%d] order item unit price', $line->getKey()),
                );
            }
        }

        throw new RuntimeException(sprintf(
            'Shipment line [%d] has no matching immutable order item price.',
            $line->getKey(),
        ));
    }

    private function weightGrams(Model $model): int
    {
        $raw = $model->getAttribute('weight_value');
        if (! is_numeric($raw) || (float) $raw <= 0) {
            throw new RuntimeException(sprintf('Product [%s] must have a positive weight.', $model->getKey()));
        }

        $value = (float) $raw;
        $unit = $model->getAttribute('weight_unit');

        if ($unit instanceof Weight) {
            $unit = $unit->value;
        }

        return match (strtolower(trim((string) $unit))) {
            Weight::G->value => (int) ceil($value),
            Weight::KG->value => (int) ceil($value * 1000),
            Weight::LBS->value => (int) ceil($value * 453.59237),
            default => throw new RuntimeException(sprintf('Product [%s] has an unsupported weight unit.', $model->getKey())),
        };
    }

    private function dimensionCentimeters(Model $model, string $dimension): int
    {
        $raw = $model->getAttribute($dimension.'_value');
        if (! is_numeric($raw) || (float) $raw <= 0) {
            throw new RuntimeException(sprintf(
                'Product [%s] must have a positive %s dimension.',
                $model->getKey(),
                $dimension,
            ));
        }

        $unit = $model->getAttribute($dimension.'_unit');
        if ($unit instanceof Length) {
            $unit = $unit->value;
        }

        $centimeters = match (strtolower(trim((string) $unit))) {
            Length::CM->value => (float) $raw,
            Length::M->value => (float) $raw * 100,
            Length::MM->value => (float) $raw / 10,
            Length::FT->value => (float) $raw * 30.48,
            Length::IN->value => (float) $raw * 2.54,
            default => throw new RuntimeException(sprintf(
                'Product [%s] has an unsupported %s unit.',
                $model->getKey(),
                $dimension,
            )),
        };

        return (int) ceil($centimeters);
    }

    private function receiverName(array $shippingAddress): string
    {
        $name = trim(implode(' ', array_filter([
            data_get($shippingAddress, 'first_name'),
            data_get($shippingAddress, 'last_name'),
        ], static fn (mixed $part): bool => is_scalar($part) && trim((string) $part) !== '')));

        return $this->requiredString($name, 'Order receiver name');
    }

    private function paymentMethod(Order $order): string
    {
        $metadata = $this->decodeMetadata($order->getAttribute('metadata'));
        $paymentType = strtolower(trim((string) data_get($metadata, 'komerce.payment_type', '')));

        if ($paymentType === '') {
            $order->loadMissing('paymentMethod');
            $methodMeta = $this->decodeMetadata($order->paymentMethod?->metadata);
            $paymentType = strtolower(trim((string) ($methodMeta['payment_type'] ?? '')));
        }

        if ($paymentType === '' && str_contains(strtolower((string) ($order->paymentMethod?->slug ?? '')), 'qris')) {
            $paymentType = 'qris';
        }

        if ($paymentType === '') {
            $paymentType = 'bank_transfer';
        }

        // Shipping Delivery store-order `payment_method` is how the shipment is
        // billed to the courier (official example: "BANK TRANSFER"), not the
        // customer's checkout channel. Live store rejects "QRIS" with
        // "Please fill correct payment method". Prepaid VA/QRIS → BANK TRANSFER.
        return match ($paymentType) {
            'cod' => 'COD',
            'bank_transfer', 'qris', 'va' => 'BANK TRANSFER',
            default => throw new RuntimeException('Order is missing its persisted Komerce payment type.'),
        };
    }

    private function pickupDate(string $pickupTime): string
    {
        $todayPickup = now()->setTimeFromTimeString($pickupTime);

        if (now()->lessThan($todayPickup)) {
            return now()->format('Y-m-d');
        }

        return now()->addDay()->format('Y-m-d');
    }

    private function persistFulfillmentError(OrderShipment $shipment, string $message): void
    {
        $fresh = OrderShipment::query()->find($shipment->id) ?? $shipment;
        $metadata = $this->decodeMetadata($fresh->metadata);
        $komerce = is_array($metadata['komerce'] ?? null) ? $metadata['komerce'] : [];
        $komerce['fulfillment_error'] = $message;
        $komerce['fulfillment_failed_at'] = now()->toIso8601String();
        $metadata['komerce'] = $komerce;

        $fresh->forceFill(['metadata' => $metadata])->save();
    }

    private function clearFulfillmentError(OrderShipment $shipment): void
    {
        $metadata = $this->decodeMetadata($shipment->metadata);
        $komerce = is_array($metadata['komerce'] ?? null) ? $metadata['komerce'] : [];

        if (! isset($komerce['fulfillment_error']) && ! isset($komerce['fulfillment_failed_at'])) {
            return;
        }

        unset($komerce['fulfillment_error'], $komerce['fulfillment_failed_at']);
        $metadata['komerce'] = $komerce;
        $shipment->forceFill(['metadata' => $metadata])->save();
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
     * @param  array<string, mixed>  $response
     */
    private function assertSuccessfulEnvelope(array $response, string $operation): void
    {
        $status = data_get($response, 'meta.status');

        if (! is_scalar($status) || strtolower(trim((string) $status)) !== 'success') {
            $message = $this->firstScalar($response, ['meta.message']) ?? 'unknown provider error';

            throw new RuntimeException(sprintf('Komerce %s failed: %s.', $operation, $message));
        }
    }

    /**
     * @param  array<string, mixed>  $pickupResponse
     * @return array<string, mixed>
     */
    private function successfulPickupItem(array $pickupResponse, string $deliveryOrderNo): array
    {
        $this->assertSuccessfulEnvelope($pickupResponse, 'pickup');

        $items = data_get($pickupResponse, 'data');
        if (! is_array($items) || ! array_is_list($items)) {
            throw new RuntimeException('Komerce pickup response data must be a list of order results.');
        }

        foreach ($items as $item) {
            if (! is_array($item) || (string) ($item['order_no'] ?? '') !== $deliveryOrderNo) {
                continue;
            }

            $status = strtolower(trim((string) ($item['status'] ?? '')));
            if ($status !== 'success') {
                throw new RuntimeException(sprintf(
                    'Komerce pickup failed for order [%s] with item status [%s].',
                    $deliveryOrderNo,
                    $status !== '' ? $status : 'missing',
                ));
            }

            return $item;
        }

        throw new RuntimeException(sprintf(
            'Komerce pickup response did not include order [%s].',
            $deliveryOrderNo,
        ));
    }

    /**
     * @param  array<string, mixed>  $storeResponse
     * @return array<string, mixed>
     */
    private function metadataAfterStore(
        OrderShipment $shipment,
        string $deliveryOrderId,
        string $deliveryOrderNo,
        array $storeResponse,
    ): array {
        $metadata = $this->decodeMetadata($shipment->metadata);
        $komerce = data_get($metadata, 'komerce', []);

        if (! is_array($komerce)) {
            $komerce = [];
        }

        $metadata['komerce'] = array_filter(array_merge($komerce, [
            'order_id' => $deliveryOrderId,
            'order_no' => $deliveryOrderNo,
            'store_order_response' => $storeResponse,
        ]), static fn (mixed $value): bool => $value !== null);

        return $metadata;
    }

    /**
     * @param  array<string, mixed>  $storeResponse
     * @param  array<string, mixed>  $pickupResponse
     * @param  array<string, mixed>|null  $labelResponse
     * @return array<string, mixed>
     */
    private function metadataAfterPickup(
        OrderShipment $shipment,
        string $deliveryOrderId,
        string $deliveryOrderNo,
        string $awb,
        array $storeResponse,
        array $pickupResponse,
        ?array $labelResponse = null,
    ): array {
        $metadata = $this->metadataAfterStore(
            $shipment,
            $deliveryOrderId,
            $deliveryOrderNo,
            $storeResponse,
        );

        $labelUrl = is_array($labelResponse) ? KomerceLabelResponse::absoluteUrl($labelResponse) : null;

        $metadata['komerce'] = array_filter(array_merge($metadata['komerce'], [
            'awb' => $awb,
            'tracking_number' => $awb,
            'pickup_response' => $pickupResponse,
            'label_response' => $labelResponse,
            'label_url' => $labelUrl,
        ]), static fn (mixed $value): bool => $value !== null);

        return $metadata;
    }

    /**
     * Official print-label after pickup. Failure must not drop the AWB.
     *
     * @return array<string, mixed>|null
     */
    private function printOfficialLabel(ShippingDeliveryClient $delivery, string $deliveryOrderNo): ?array
    {
        try {
            $response = $delivery->printLabel([$deliveryOrderNo]);
        } catch (Throwable $e) {
            Log::warning('RajaOngkir print-label failed after pickup.', [
                'order_no' => $deliveryOrderNo,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        return $response !== [] ? $response : null;
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

    /**
     * @return array<string, mixed>
     */
    private function deliveryRate(OrderShipment $shipment): array
    {
        $rate = data_get($this->decodeMetadata($shipment->metadata), 'rate');

        if (is_array($rate) && $this->isOfficialDeliveryTariff($rate)) {
            return $rate;
        }

        $fromCalculate = $this->rateFromDeliveryCalculate($shipment, is_array($rate) ? $rate : []);

        if ($fromCalculate !== null) {
            return $fromCalculate;
        }

        throw new RuntimeException(
            'RajaOngkir store-order membutuhkan tarif Delivery calculate (termasuk shipping_cashback). Isi pinpoint gudang dan tujuan. Angka Cost dengan cashback 0 ditolak API.',
        );
    }

    /**
     * Official Delivery GET /tariff/api/v1/calculate. Live store rejects Cost cashback 0.
     *
     * @param  array<string, mixed>  $costRate
     * @return array<string, mixed>|null
     */
    private function rateFromDeliveryCalculate(OrderShipment $shipment, array $costRate): ?array
    {
        $originPin = $this->originPinPoint($shipment);
        $destinationPin = $this->destinationPinPoint($shipment);

        if ($originPin === null || $destinationPin === null) {
            return null;
        }

        $inventory = $shipment->inventory;
        $originId = $this->requiredPositiveInteger(
            $inventory?->getAttribute('rajaongkir_origin_id'),
            'Shipment inventory RajaOngkir origin id',
        );
        $destinationId = $this->requiredPositiveInteger(
            data_get($this->shippingAddress($shipment->order), 'rajaongkir_destination_id')
                ?? data_get($this->shippingAddress($shipment->order), 'destination_id'),
            'Order RajaOngkir destination id',
        );

        $weightKilograms = $this->shipmentWeightKilograms($shipment);
        $itemValue = $this->shipmentItemValue($shipment);
        $wantedName = RajaOngkirCourier::deliveryName(
            (string) ($costRate['carrier_code'] ?? $shipment->carrier_code ?? ''),
            is_string($costRate['shipping_name'] ?? null) ? (string) $costRate['shipping_name'] : (string) ($shipment->carrier_name ?? ''),
        );
        $wantedService = RajaOngkirCourier::deliveryService(
            (string) ($costRate['service_name'] ?? $costRate['service'] ?? $shipment->service_name ?? $shipment->service_code ?? ''),
        );

        $response = resolve(ShippingDeliveryClient::class)->calculate(
            $originId,
            $destinationId,
            $originPin,
            $destinationPin,
            $weightKilograms,
            $itemValue,
            false,
        );

        foreach (['calculate_reguler', 'calculate_cargo', 'calculate_instant'] as $bucket) {
            $rows = data_get($response, 'data.'.$bucket, []);
            if (! is_array($rows)) {
                continue;
            }
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (
                    strtoupper((string) ($row['shipping_name'] ?? '')) === strtoupper($wantedName)
                    && strtoupper((string) ($row['service_name'] ?? '')) === strtoupper($wantedService)
                ) {
                    return [
                        'provider' => 'shipping_delivery',
                        'shipping_name' => (string) $row['shipping_name'],
                        'service_name' => (string) $row['service_name'],
                        'shipping_cost' => (int) $row['shipping_cost'],
                        'shipping_cashback' => (int) ($row['shipping_cashback'] ?? 0),
                        'service_fee' => (int) ($row['service_fee'] ?? 0),
                        'additional_cost' => 0,
                        'grandtotal' => (int) ($row['grandtotal'] ?? 0),
                        'cod_value' => 0,
                        'insurance_value' => 0,
                    ];
                }
            }
        }

        return null;
    }

    private function originPinPoint(OrderShipment $shipment): ?string
    {
        $inventory = $shipment->inventory;
        $lat = $inventory?->getAttribute('latitude');
        $lng = $inventory?->getAttribute('longitude');

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return $lat.','.$lng;
    }

    private function destinationPinPoint(OrderShipment $shipment): ?string
    {
        $address = $this->shippingAddress($shipment->order);
        $pin = $address['rajaongkir_pin_point'] ?? $address['pin_point'] ?? null;
        if (is_string($pin) && str_contains($pin, ',')) {
            return $pin;
        }

        $lat = $address['latitude'] ?? null;
        $lng = $address['longitude'] ?? null;
        if (is_numeric($lat) && is_numeric($lng)) {
            return $lat.','.$lng;
        }

        return null;
    }

    private function shipmentWeightKilograms(OrderShipment $shipment): float
    {
        $grams = 0;
        foreach ($shipment->lines as $line) {
            $purchasable = $line->purchasable;
            if ($purchasable instanceof Model) {
                $grams += $this->weightGrams($purchasable) * max(1, (int) $line->qty);
            }
        }

        return max(0.01, $grams / 1000);
    }

    private function shipmentItemValue(OrderShipment $shipment): int
    {
        $order = $shipment->order;
        $itemsTotal = 0;
        if ($order instanceof Order) {
            $order->loadMissing('items');
            foreach ($order->items as $item) {
                $itemsTotal += (int) $item->getAttribute('unit_price_amount') * (int) $item->getAttribute('quantity');
            }
        }

        return max(0, $itemsTotal);
    }

    /**
     * @param  array<string, mixed>  $rate
     */
    private function isOfficialDeliveryTariff(array $rate): bool
    {
        $source = strtolower(trim((string) ($rate['provider'] ?? $rate['source'] ?? '')));

        if (! in_array($source, ['shipping_delivery', 'komship'], true)) {
            return false;
        }

        return isset($rate['shipping_name'], $rate['service_name'], $rate['shipping_cost']);
    }

    private function requiredPositiveInteger(mixed $value, string $field): int
    {
        $parsed = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($parsed === false) {
            throw new RuntimeException("{$field} must be a positive integer.");
        }

        return $parsed;
    }

    private function requiredNonNegativeInteger(mixed $value, string $field): int
    {
        $parsed = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($parsed === false) {
            throw new RuntimeException("{$field} must be a non-negative integer.");
        }

        return $parsed;
    }

    private function requiredString(mixed $value, string $field): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        if ($value === '') {
            throw new RuntimeException("{$field} is required.");
        }

        return $value;
    }

    private function requiredEmail(mixed $value, string $field): string
    {
        $value = $this->requiredString($value, $field);

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException("{$field} must be a valid email address.");
        }

        return $value;
    }

    private function requiredPhone(mixed $value, string $field): string
    {
        $phone = preg_replace('/[\s().-]+/', '', $this->requiredString($value, $field));

        if (! is_string($phone) || preg_match('/^(?:0|62)[0-9]{7,14}$/', $phone) !== 1) {
            throw new RuntimeException("{$field} must start with 0 or 62 and contain a real Indonesian phone number.");
        }

        return $phone;
    }

    /**
     * @param  list<mixed>  $parts
     */
    private function formattedAddress(array $parts, string $field): string
    {
        $present = array_values(array_filter(
            $parts,
            static fn (mixed $part): bool => is_scalar($part) && trim((string) $part) !== '',
        ));
        $value = implode(', ', array_map(static fn (mixed $part): string => trim((string) $part), $present));

        return $this->requiredString($value, $field);
    }
}
