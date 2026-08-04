# Warehouse / admin ops — E2E UAT (paid → label → done)

## Result: PASS (2026-08-04)

Backoffice fulfillment verified after customer payment.

**Ops UI is Shopper `/cpanel` only** — no separate `/admin` backoffice. RajaOngkir/Komerce shipping panel is embedded on:

`/cpanel/orders/{id}/detail`

1. **Domain script** — `php scripts/live-warehouse-ops-e2e-uat.php`
2. **Feature test** — `WarehouseOpsE2ETest` + `CpanelOrderDetailTest`
3. **Browser** — cpanel order detail print label UI

### Domain warehouse checklist

| Step | Result |
| --- | --- |
| Customer QRIS order + mark paid | PASS |
| AWB job → order shipping shipped | PASS |
| Override locked after AWB | PASS |
| Print label via `/cpanel/.../fulfillment/label` | PASS |
| Delivery webhook → completed | PASS |

### Ops flow (gudang)

| Who | Action |
| --- | --- |
| System | Paid webhook → queue AWB + pickup |
| Admin | `/cpanel/orders/{id}/detail` → Cetak label |
| Admin (optional, pre-AWB) | Pindah stok di panel yang sama |
| System | Delivery webhook / tracking → completed |

### Scripts

| Script | Purpose |
| --- | --- |
| `scripts/live-warehouse-ops-e2e-uat.php` | Warehouse hulu→hilir after paid |
| `scripts/live-customer-qris-e2e-uat.php` | Customer QRIS journey |
