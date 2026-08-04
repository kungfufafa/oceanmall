# Customer storefront production walkthrough

Manual sandbox checklist (also in README):

1. Set Komerce API keys + `KOMERCE_WEBHOOK_SECRET`; keep `PAYMENT_STRIPE_ENABLED=false`.
2. Ensure default Inventory has `rajaongkir_origin_id`.
3. Seed/enable payment methods: BCA VA, BRI VA, QRIS (COD off by default).
4. Run queue worker + scheduler.
5. Browse → cart → checkout destination → rates → VA/QRIS → webhook paid → account track.
6. Negative: unsigned delivery/qrisly webhook → 401; unpaid expire releases stock.

## Live UAT findings (sandbox Collaborator)

Verified against live sandbox with Shipping Cost + Payment API:

- Destination search (`Jakarta Selatan`) returns districts
- Domestic cost Cirebon origin `17248` → Jakarta returns courier rates
- Payment create returns Collaborator fields: `va_number`, `qr_string`, `expired_at`, `payment_url`, `payment_id` like `KPAY-xxxx/KM/2026`
- Storefront maps those fields into the customer VA/QRIS panel
- Payment API requires `customer.phone` (fallback + shipping-address phone)

QRISLY remains off until `KOMERCE_QRISLY_QRIS_ID` is set; QRIS still works via Payment API.

Automated gate (this branch):

```bash
npm run build
php artisan test --filter='Checkout|Komerce|Cart|Shop|Account|Warehouse'
npm run types:check
```

Never commit `.env` / API keys.
