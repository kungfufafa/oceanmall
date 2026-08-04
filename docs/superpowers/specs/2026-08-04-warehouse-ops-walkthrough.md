# Warehouse / admin ops — E2E UAT (paid → label → done)

## Result: PASS (2026-08-04)

Backoffice fulfillment verified after customer payment:

1. **Domain script** — `php scripts/live-warehouse-ops-e2e-uat.php` → `#ORD-20260804-000067`
2. **Feature test** — `WarehouseOpsE2ETest` (AWB → admin show → print → delivery webhook → completed)
3. **Browser** — admin `/admin/orders/{id}` print label UI

### Domain warehouse checklist (`#ORD-20260804-000067`)

| Step | Result |
| --- | --- |
| Customer QRIS order + mark paid | PASS |
| `CreateRajaOngkirDeliveryForShipment` → order_no + AWB | PASS |
| Order `shipping_status` → **shipped** (synced from job) | PASS |
| Override locked after AWB | PASS |
| Admin gates (print + override) | PASS |
| Print label PDF URL | PASS |
| Admin order ops HTTP 200 | PASS |
| Delivery webhook `ON_PROCESS` → in_transit | PASS |
| Delivery webhook `DELIVERED` → order **completed** | PASS |

### Hardening from this UAT

- AWB job now calls `SyncOrderShippingFromShipments` so order shipping badge updates immediately after label/pickup (not only after webhook)
- Delivery webhook shipment lookup prefers latest match (`orderByDesc('id')`) when duplicates exist

### Ops flow (gudang)

| Who | Action |
| --- | --- |
| System | Paid webhook → queue AWB + pickup |
| Admin | `/admin/orders/{id}` → Print label |
| Admin (optional, pre-AWB) | Override warehouse allocation |
| System | Delivery webhook / hourly tracking → shipped → delivered → completed |
| Customer | Confirm received (alt path) |

Physical pack + stick label stays offline. No separate “mark packed” UI — courier pickup is auto-requested after AWB.

### Scripts

| Script | Purpose |
| --- | --- |
| `scripts/live-warehouse-ops-e2e-uat.php` | Warehouse hulu→hilir after paid |
| `scripts/live-customer-qris-e2e-uat.php` | Customer QRIS journey |
