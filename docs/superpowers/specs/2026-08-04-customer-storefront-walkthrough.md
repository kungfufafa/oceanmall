# Customer storefront — full marketplace UAT (hulu → hilir)

## Result: PASS (2026-08-04) — QRIS end-to-end

Full customer journey verified:

1. **Browser UI UAT** — storefront @ `http://127.0.0.1:8000` with **QRIS** (no script fallback for place-order)
2. **Domain live UAT** — `php scripts/live-customer-qris-e2e-uat.php` (QRIS → paid webhook → AWB → confirm → review → print label + edges)
3. Earlier VA path still covered by `scripts/live-customer-domain-uat.php`

### Browser QRIS checklist (`#ORD-20260804-000063`)

| Step | Result |
| --- | --- |
| Home → shop → product Buds T310 | PASS |
| Add to cart → qty → kupon `OCEAN10` | PASS |
| Checkout alamat + RajaOngkir district | PASS (auto city/zip sync) |
| Kurir rates → pilih | PASS |
| Pilih **QRIS** (bukan VA) | PASS |
| Success: QR panel + salin kode + URL | PASS (`KPAY-c0ac/KM/2026`) |
| Account order detail + instruksi QRIS | PASS |

Screenshots: `/opt/cursor/artifacts/screenshots/` (`01-homepage`, cart coupon, address district, QRIS selected, success QR, account order).

### Domain QRIS E2E (latest run also `#ORD-20260804-000060` / earlier `#ORD-20260804-000057`)

| Step | Result |
| --- | --- |
| Cart qty + coupon | PASS |
| Live QRIS create (`qr_string` + `payment_url`) | PASS |
| Signed payment webhook → paid | PASS |
| AWB / tracking | PASS |
| Confirm received → completed | PASS |
| Product review (pending) | PASS |
| Admin print label | PASS |
| Edge: stok habis | PASS |
| Edge: empty rates guard | PASS |
| Edge: retry payment route | PASS |
| Edge: expire unpaid | PASS |

### Hardening from this UAT

- Checkout district: clear validation errors on select, always sync city/zip from RajaOngkir, disable submit until destination set, coerce destination id to string (FE + BE)
- Accept numeric `rajaongkir_destination_id` from Inertia/JSON

### Scripts

| Script | Purpose |
| --- | --- |
| `scripts/live-customer-qris-e2e-uat.php` | Full hulu→hilir with QRIS (preferred) |
| `scripts/live-customer-domain-uat.php` | VA create smoke |
| `scripts/live-customer-uat.php` | HTTP/Inertia smoke (CSRF-sensitive) |

### Ops reminder

- Keys stay in `.env` only (never commit).
- QRISLY off without `KOMERCE_QRISLY_QRIS_ID` — checkout QRIS uses Payment API.
- Paid webhook re-checks remote status; domain UAT simulates PAID via HMAC webhook + faked `getStatus`.
- Live AWB/print need Shipping Delivery API; UAT may seed AWB / fake print-label after paid.
- Queue + scheduler required in real ops for AWB create / expire / tracking.
