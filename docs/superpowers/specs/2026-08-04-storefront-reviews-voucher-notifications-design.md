# Storefront Reviews + Voucher + Notifications (2026-08-04)

## Decisions

- Reviews: verified buyers only (order completed/delivered containing product); submit → `approved=false` until cpanel approve.
- Voucher: Shopper cart `coupon_code` via `CartManager::applyCoupon` / `removeCoupon`; UI on cart + checkout.
- Notifications: Laravel database inbox + email (queued); no Web Push/FCM.

## Surfaces

| Feature | Routes / hooks |
|---------|----------------|
| Reviews list | `ProductController::show` props |
| Review submit | `POST /shop/{product}/reviews` |
| Coupon apply/remove | `POST|DELETE /cart/coupon` |
| Inbox | `/account/notifications` + header badge |
| Notify | place order, paid, shipped, delivered, unpaid cancel |

## Payload (database notification)

`title`, `body`, `order_id`, `order_number`, `url` → account order show.
