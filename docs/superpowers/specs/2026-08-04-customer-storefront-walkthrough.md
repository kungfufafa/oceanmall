# Customer storefront production walkthrough

Manual sandbox checklist (also in README):

1. Set Komerce API keys + `KOMERCE_WEBHOOK_SECRET`; keep `PAYMENT_STRIPE_ENABLED=false`.
2. Ensure default Inventory has `rajaongkir_origin_id`.
3. Seed/enable payment methods: BCA VA, BRI VA, QRIS (COD off by default).
4. Run queue worker + scheduler.
5. Browse → cart → checkout destination → rates → VA/QRIS → webhook paid → account track.
6. Negative: unsigned delivery/qrisly webhook → 401; unpaid expire releases stock.

Automated gate (this branch):

```bash
npm run build
php artisan test --filter='Checkout|Komerce|Cart|Shop|Account|Warehouse'
npm run types:check
```
