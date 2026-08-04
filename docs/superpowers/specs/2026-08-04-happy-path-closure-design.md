# Happy Path Closure Design (2026-08-04)

Closes the staging gaps from purchase → received for Customer, Admin, and System roles on the Komerce + RajaOngkir multi-warehouse flow.

## Goals

- Customer can pick a RajaOngkir district, pay, track packages, and confirm receipt.
- Admin can print labels and override warehouse allocation from an Inertia ops page.
- System cancels unpaid expired payments (releasing stock) and polls tracking until delivered.

## Status model

Shipment statuses (stable): `pending | labeled | picked_up | in_transit | delivered`.

Raw carrier status stays in `metadata.komerce.tracking_status`.

Order `shipping_status` is aggregated from shipment rows (not Shopper item fulfillment):

- all delivered → `delivered` (+ `order.status = completed` when previously new/processing)
- some delivered → `partially_delivered`
- all active (labeled/picked_up/in_transit) → `shipped`
- some active → `partially_shipped`
- else → `unfulfilled`

## Flows

### Tracking refresh

`RefreshShipmentTracking` normalizes status via `NormalizeShipmentStatus`, then `SyncOrderShippingFromShipments`.

Scheduled: `komerce:refresh-shipment-tracking` hourly.

### Confirm received

`POST /account/orders/{order}/confirm-received` — owner only, paid, has AWB.

Marks all shipments delivered, order completed, shipping_status delivered. Idempotent if already completed.

### Unpaid expiry

`CreateKomercePayment` persists `metadata.komerce.expiry_date` (+ payment transaction metadata).

`komerce:expire-unpaid-orders` every 15 minutes:

- pending payment + status new + komerce transaction
- expiry past (fallback: created_at + 24h)
- best-effort `PaymentClient::cancel`
- void payment, cancel order, `ReleaseOrderShipmentStock`

### Admin ops

`GET /admin/orders/{order}` (admin gate) — print label + override form.

Override also restores stock at source and debits destination via `mutateStock` / `decreaseStock`.

### Destination picker

`ShippingCostClient::searchDomestic` → `GET /checkout/destinations?q=`.

Checkout requires `rajaongkir_destination_id` when `komerce_enabled()`.

## Out of scope

- Soft reservation before place-order (stock still commits at order create; expiry releases it).
- Delivery webhook controller (polling replaces it for now).
- Shopper Livewire cpanel extension (Inertia `/admin` used instead).
