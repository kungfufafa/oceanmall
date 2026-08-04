# Customer storefront — full marketplace UAT

## Result: PASS (2026-08-04)

Full customer journey verified two ways:

1. **Browser UAT** (real UI @ `http://127.0.0.1:8000`)
2. **Domain live UAT** (`php scripts/live-customer-domain-uat.php` + real Komerce sandbox)

### Browser checklist

| Step | Result |
| --- | --- |
| Home | PASS |
| Shop listing | PASS |
| Product detail | PASS (use priced product; variant-only SKUs need option select) |
| Add to cart + toast | PASS |
| Cart | PASS |
| Login → checkout | PASS |
| Address + RajaOngkir district search | PASS |
| Courier rates select | PASS |
| BCA VA place order | PASS |
| Success shows VA number | PASS (order `#ORD-20260804-000054`) |
| Account orders + detail | PASS (pending payment) |

### Domain live UAT

`scripts/live-customer-domain-uat.php` → order `#ORD-20260804-000056`, VA created via Collaborator (`KPAY-…`), shipment row persisted.

### Hardening from UAT

- Map Collaborator `va_number` / `qr_string` / `expired_at` / `payment_url`
- Require `customer.phone` (fallback)
- Reject add-to-cart without variant when product has variants
- Reject Komerce payload items with zero unit price
- Isolate PHPUnit from local UAT `.env` keys

### Ops reminder

Keys stay in `.env` only. Queue + scheduler required for AWB/expire/tracking after paid webhook.
