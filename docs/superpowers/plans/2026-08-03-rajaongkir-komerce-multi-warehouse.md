# RajaOngkir / Komerce Multi-Warehouse Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace Stripe/US shipping paths with Komerce Payment + RajaOngkir shipping adapters on Shopper, then add multi-warehouse allocation with admin override and split shipments.

**Architecture:** Adapter layer under `app/Services/Komerce` and `app/Actions/Warehouse`. Map each warehouse to Shopper `Inventory`. Checkout creates one Order, N Shipments, one Komerce payment. Stripe stays disabled.

**Tech Stack:** Laravel 13, Shopper 2.11, Inertia/Vue 3, PHPUnit, RajaOngkir Enterprise / Komerce Payment APIs.

**Spec:** `docs/superpowers/specs/2026-08-03-rajaongkir-komerce-multi-warehouse-design.md`

## Global Constraints

- Keep `PAYMENT_STRIPE_ENABLED=false`; do not surface Stripe in storefront checkout.
- Prefer Shopper `Inventory` for warehouses; do not invent a parallel warehouse table unless Inventory cannot store RajaOngkir origin metadata (then add a thin `warehouse_profiles` table keyed by `inventory_id`).
- One payment per order covering merchandise + all shipment costs.
- Split shipment when stock spans multiple inventories.
- System suggests allocation; admin may override before AWB creation.
- TDD: failing test → implement → pass → commit per task.
- Do not commit `.env`, `auth.json`, or real API keys.
- PHP 8.3+, match existing `declare(strict_types=1);` and project style.

---

## File map (target)

| Path | Responsibility |
| --- | --- |
| `config/komerce.php` | Base URLs, timeouts, enabled flags |
| `app/Services/Komerce/PaymentClient.php` | VA/QRIS create, status, cancel |
| `app/Services/Komerce/ShippingCostClient.php` | Domestic cost lookup |
| `app/Services/Komerce/ShippingDeliveryClient.php` | AWB / pickup / track |
| `app/Http/Controllers/Webhooks/KomercePaymentWebhookController.php` | Payment callbacks |
| `app/Actions/Warehouse/SuggestAllocation.php` | Cart → shipment drafts |
| `app/Actions/Warehouse/OverrideAllocation.php` | Admin reassignment |
| `app/Actions/Checkout/FetchDeliveryRates.php` | Swap to RajaOngkir |
| `app/Actions/Checkout/FetchPaymentMethods.php` | Komerce channels |
| `app/Http/Controllers/Shop/CheckoutController.php` | Multi-shipment checkout |
| `resources/js/pages/shop/checkout.vue` | Show packages + Komerce pay UI |
| `database/migrations/*_create_order_shipments_table.php` | Shipments persistence |
| `tests/Feature/Komerce/*` | HTTP fakes + checkout flows |

---

## Phase 1 — Single warehouse + Cost API + Komerce Payment

### Task 1: Config + HTTP clients (scaffolding)

**Files:**
- Create: `config/komerce.php`
- Create: `app/Services/Komerce/Concerns/UsesKomerceHttp.php`
- Create: `app/Services/Komerce/PaymentClient.php`
- Create: `app/Services/Komerce/ShippingCostClient.php`
- Modify: `.env.example`
- Test: `tests/Unit/Services/Komerce/PaymentClientTest.php`
- Test: `tests/Unit/Services/Komerce/ShippingCostClientTest.php`

**Interfaces:**
- Produces:
  - `PaymentClient::createVirtualAccount(array $payload): array`
  - `PaymentClient::createQris(array $payload): array`
  - `PaymentClient::getStatus(string $reference): array`
  - `ShippingCostClient::calculate(array $origin, array $destination, array $weightGrams, array $couriers): array`

- [ ] **Step 1: Write failing unit tests** using `Http::fake()` for create VA and calculate cost.

- [ ] **Step 2: Run** `php artisan test --filter=PaymentClientTest` — expect FAIL (class missing).

- [ ] **Step 3: Add config + clients** reading `KOMERCE_API_KEY`, sandbox/production base URLs from `.env.example`. Document endpoints from https://rajaongkir.com/docs (Payment + Shipping Cost). Prefer Bearer auth as per Komerce docs.

- [ ] **Step 4: Re-run tests** — PASS.

- [ ] **Step 5: Commit** `feat(komerce): add payment and shipping cost clients`

### Task 2: Payment webhook + order payment status

**Files:**
- Create: `app/Http/Controllers/Webhooks/KomercePaymentWebhookController.php`
- Create: `app/Actions/Checkout/MarkOrderPaidFromKomerce.php`
- Modify: `routes/web.php` — `POST /webhooks/komerce/payment`
- Modify: `app/Actions/CreateOrder.php` (or adjacent) to store `komerce_payment_ref`
- Test: `tests/Feature/Komerce/PaymentWebhookTest.php`

**Interfaces:**
- Consumes: `PaymentClient::getStatus`
- Produces: order status transition `pending_payment → paid` on valid webhook; idempotent on replay

- [ ] **Step 1: Write webhook test** posting signed/fake payload twice; assert single paid transition.

- [ ] **Step 2: Implement controller + action**; reject invalid secret; never trust client-side alone.

- [ ] **Step 3: Tests PASS; commit** `feat(komerce): handle payment webhooks`

### Task 3: Checkout uses Komerce payment (no Stripe UI)

**Files:**
- Modify: `app/Actions/Checkout/FetchPaymentMethods.php`
- Modify: `app/Http/Controllers/Shop/CheckoutController.php` (`preparePayment`, `placeOrder`)
- Modify: `resources/js/pages/shop/checkout.vue` — hide Stripe branch; show VA number / QRIS
- Create: `resources/js/components/shop/komerce-payment-panel.vue`
- Test: `tests/Feature/Checkout/KomerceCheckoutTest.php`

**Interfaces:**
- Non-stripe preparePayment creates Komerce charge and returns payment instructions props
- `placeOrder` / pay flow creates order first (pending), then payment

- [ ] **Step 1: Feature test** checkout with fake Http → order pending → webhook → paid.

- [ ] **Step 2: Wire controller + Vue panel**; keep COD/manual only if still configured in Shopper; do not call Stripe.

- [ ] **Step 3: PASS; commit** `feat(checkout): pay with Komerce VA/QRIS`

### Task 4: Shipping cost via RajaOngkir (single default inventory)

**Files:**
- Modify: `app/Actions/Checkout/FetchDeliveryRates.php`
- Modify: `app/Actions/Checkout/BuildShippingPackages.php` if weight units need grams
- Ensure default Shopper Inventory has origin fields usable by Cost API (migration for `rajaongkir_origin_id` on inventories or side table)
- Test: `tests/Feature/Checkout/RajaOngkirRatesTest.php`

**Interfaces:**
- `FetchDeliveryRates::handle` returns normalized `{ service_code, service_name, amount, currency, carrier_code, estimated_days, carrier_name }`
- Origin from default Inventory, not `shopper_setting` alone when inventory exists

- [ ] **Step 1: Migration** add `rajaongkir_origin_id` (nullable string) to inventories table via Shopper-safe approach or `warehouse_profiles`.

- [ ] **Step 2: Test rates** with Http fake; assert options appear in checkout props.

- [ ] **Step 3: Implement; PASS; commit** `feat(shipping): fetch rates from RajaOngkir cost API`

### Task 5: Phase 1 hardening + docs

**Files:**
- Modify: `README.md` — Komerce env vars, webhook URL, Stripe off
- Modify: `.env.example` — complete Komerce keys
- Ensure `PAYMENT_STRIPE_ENABLED=false` documented

- [ ] **Step 1: Manual checklist** in README for sandbox test.
- [ ] **Step 2: Commit** `docs: document Komerce Phase 1 setup`

**Phase 1 done when:** single-warehouse checkout can quote Indonesian couriers and accept VA/QRIS payment end-to-end with fakes/tests green.

---

## Phase 2 — Multi-warehouse suggest + split checkout UI

### Task 6: SuggestAllocation action

**Files:**
- Create: `app/Actions/Warehouse/SuggestAllocation.php`
- Create: `app/DTO/AllocationPlan.php`, `app/DTO/ShipmentDraft.php`
- Test: `tests/Unit/Actions/Warehouse/SuggestAllocationTest.php`

**Interfaces:**
- `SuggestAllocation::handle(Cart $cart, array $destination): AllocationPlan`
- Plan contains `shipments: list<ShipmentDraft{ inventory_id, lines: list<{purchasable_type, purchasable_id, qty}>, }>`
- Split line qty across inventories when one warehouse lacks full stock
- Prefer inventories that can fully satisfy a line; then nearest/cheapest heuristic

- [ ] **Step 1: Unit tests** for single warehouse, split across two, insufficient stock.
- [ ] **Step 2: Implement; PASS; commit** `feat(warehouse): suggest multi-inventory allocation`

### Task 7: Persist shipments on order

**Files:**
- Create migration `order_shipments` + `order_shipment_lines`
- Create models `App\Models\OrderShipment`, `OrderShipmentLine`
- Modify `CreateOrder` / checkout place flow to write shipments from AllocationPlan
- Test: `tests/Feature/Checkout/SplitShipmentOrderTest.php`

**Interfaces:**
- Order hasMany shipments; each shipment has cost, carrier, service, inventory_id, status

- [ ] **Steps:** failing test → migration/models → wire create → PASS → commit `feat(orders): persist split shipments`

### Task 8: Checkout UI for multiple packages

**Files:**
- Modify: `resources/js/pages/shop/checkout.vue`
- Create: `resources/js/components/shop/shipment-rate-picker.vue`
- Modify: `CheckoutController@index` props: `allocation`, `deliveryOptionsByShipment`

- [ ] Customer sees “Paket 1 · Gudang Jakarta”, “Paket 2 · Gudang Cirebon”
- [ ] Selects rate per shipment; total ongkir sums
- [ ] Test + commit `feat(checkout): multi-package rate selection`

---

## Phase 3 — Admin override + Delivery API (AWB)

### Task 9: OverrideAllocation (admin)

**Files:**
- Create: `app/Actions/Warehouse/OverrideAllocation.php`
- Filament page or Livewire action on order resource (prefer extend Shopper order UI; if blocked, Inertia admin route behind auth+role)
- Audit log table or use activity log if already present
- Test: `tests/Feature/Admin/OverrideAllocationTest.php`

**Rules:** only before AWB (`status` in `pending`, `ready`); recalculate rates after move; validate stock.

- [ ] Commit `feat(admin): override warehouse allocation before AWB`

### Task 10: ShippingDeliveryClient + jobs

**Files:**
- Create: `app/Services/Komerce/ShippingDeliveryClient.php`
- Create: `app/Jobs/CreateRajaOngkirDeliveryForShipment.php`
- Dispatch from `MarkOrderPaidFromKomerce` per shipment
- Store AWB/tracking on `order_shipments`
- Test with Http fake

- [ ] Commit `feat(shipping): create RajaOngkir delivery orders after payment`

### Task 11: Account order tracking UI

**Files:**
- Modify: `resources/js/pages/account/order-show.vue`
- Show each shipment status + AWB + track link/status

- [ ] Commit `feat(account): show per-shipment tracking`

---

## Verification (every phase)

```bash
php artisan test --filter=Komerce
php artisan test --filter=Checkout
php artisan test --filter=Warehouse
npm run types:check
```

Manual sandbox: set Komerce sandbox keys, create Inventory “Gudang Jakarta” with origin id, place test order, simulate payment webhook.

---

## Long-running agent notes

- Execute **Phase 1 completely** before Phase 2.
- After each task: commit on a feature branch `feat/komerce-rajaongkir`.
- Open PR when Phase 1 is green; continue Phase 2/3 on same branch or stacked PRs.
- If RajaOngkir Enterprise docs require fields not in public snippets, keep client methods thin and cover with fakes; document assumed payload shapes in code comments + README.
- Do not remove Stripe files wholesale in P1; disable and bypass is enough.
