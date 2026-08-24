# Storefront Deploy QA Plan

HTTP-level QA runner for the OceanMall customer storefront after deploy. Targets **QA Test Engineer** depth: structured test IDs, severity, suites, defect objects, and traceability — not just a smoke script.

**Runner:** `scripts/deploy-qa-storefront-e2e.php`  
**Related (unchanged):** `scripts/deploy-e2e.php` (smoke/customer/full), `scripts/deploy-storefront-deep-e2e.php` (audit/weakness report)

## Scope

| In scope | Out of scope |
|----------|--------------|
| Public catalog (home, shop, sorts, category, collection, search, PDP) | cPanel / Shopper Livewire admin deep ops |
| Guest + authenticated cart mutations | Visual snapshot SaaS (Percy) — screenshots on fail only |
| Checkout steps 1–2 (zone, address, rates, payment methods) | Safari/WebKit matrix (Chromium + mobile Chrome covered) |
| Account area (dashboard, orders, addresses, notifications, settings) | Stripe/Komerce live payment capture |
| Auth (login, logout, wrong password, guest redirects) | Load/stress testing |
| Security (CSRF, authz, unsigned webhook) | |
| Optional unpaid order placement (`DEPLOY_QA_PLACE_ORDER=YES`) | End-to-end paid webhook (`deploy-e2e.php` full mode) |
| Response-time flagging (>3s → S3 perf finding) | |
| **Playwright browser E2E** (critical journeys + mobile viewport) | |
| **axe-core a11y** (WCAG 2.1 A/AA critical + serious) | Moderate/minor a11y noise (tracked as polish) |

### Known gaps (honest)

- Visual snapshot regression service not wired; Playwright keeps failure screenshots/traces.
- **Shipping/rates** depend on Komerce/RajaOngkir config; empty rates on misconfigured env are reported as failures, not skipped silently.
- **Place-order** is optional and off by default to avoid side effects on shared staging.
- Paid settlement assertion remains sandbox-limited unless real pay / `DEPLOY_E2E_REQUIRE_PAID`.

## Environments & credentials

Configure via environment variables (never commit secrets). See `.env.example` (`DEPLOY_QA_*` block).

| Variable | Purpose | Default |
|----------|---------|---------|
| `DEPLOY_BASE_URL` | Storefront origin (required) | — |
| `DEPLOY_QA_EMAIL` | Customer login | `customer@oceanmall.test` |
| `DEPLOY_QA_PASSWORD` | Customer password | `password123` |
| `DEPLOY_QA_SUITE` | `smoke` \| `regression` \| `negative` \| `security` \| `all` | `regression` |
| `DEPLOY_QA_STRICT` | `YES` → exit 1 on any S3 fail | unset (S3-only may pass with warnings) |
| `DEPLOY_QA_PLACE_ORDER` | `YES` → run TC-CHK-008 | `NO` |
| `DEPLOY_QA_CONFIRM` | `YES` required for place-order on non-local hosts | — |
| `DEPLOY_QA_PRODUCT_SLUG` | Prefer this PDP slug | auto from `/shop` |
| `DEPLOY_QA_DESTINATION` | Destination search query | `Jakarta Selatan` |
| `DEPLOY_QA_COUPON` | Valid coupon code | `OCEAN10` |
| `DEPLOY_QA_SLOW_MS` | Perf threshold | `3000` |

Legacy aliases `DEPLOY_E2E_*` are **not** used by the QA runner; use `DEPLOY_QA_*` for clarity.

**Typical targets:** local `http://127.0.0.1:8000`, staging HTTPS shop URL, post-deploy CI job against staging.

## Severity definitions

| Level | Meaning | Exit impact |
|-------|---------|-------------|
| **S1** | Blocker — checkout/auth/cart broken, 5xx, data loss risk | Fail run (exit 1) |
| **S2** | Major — core journey impaired, wrong auth state, validation missing | Fail run (exit 1) |
| **S3** | Minor — polish, empty nav, slow page, optional content missing | Warn by default; exit 1 if `DEPLOY_QA_STRICT=YES` |
| **S4** | Polish / nice-to-have | Informational only (not used for exit) |

Perf cases (`TC-PERF-*`) flag responses slower than `DEPLOY_QA_SLOW_MS` as **S3**.

## Test suites

| Suite | Intent | Approx. cases |
|-------|--------|---------------|
| `smoke` | Post-deploy happy path (~15) | 15 |
| `regression` | Full positive + key negatives | ~45 |
| `negative` | Invalid inputs & auth boundaries | ~10 |
| `security` | CSRF, authz, webhook | 3 |
| `all` | Everything including perf + optional order | ~50 |

## Traceability matrix

| ID | Title | Severity | smoke | regression | negative | security |
|----|-------|----------|:-----:|:----------:|:--------:|:--------:|
| **TC-AUTH-001** | Login with valid credentials | S2 | ✓ | ✓ | | |
| **TC-AUTH-002** | Logout returns guest session | S3 | | ✓ | | |
| **TC-AUTH-003** | Wrong password stays guest (422/302, not 5xx) | S2 | | ✓ | ✓ | |
| **TC-AUTH-004** | GET `/checkout` unauthenticated → login redirect | S1 | ✓ | ✓ | ✓ | |
| **TC-CAT-001** | Home page Inertia 200 | S2 | ✓ | ✓ | | |
| **TC-CAT-002** | Shop listing 200 + products | S2 | ✓ | ✓ | | |
| **TC-CAT-003** | Shop sort `price_asc` | S3 | | ✓ | | |
| **TC-CAT-004** | Shop sort `price_desc` | S3 | | ✓ | | |
| **TC-CAT-005** | Shop sort `name` | S3 | | ✓ | | |
| **TC-CAT-006** | Categories index | S3 | | ✓ | | |
| **TC-CAT-007** | Category show (from seeded slug) | S3 | | ✓ | | |
| **TC-CAT-008** | Collection show (from home featured) | S3 | | ✓ | | |
| **TC-CAT-009** | Search hit (`q=realme`) | S3 | ✓ | ✓ | | |
| **TC-CAT-010** | Search empty / no-match | S3 | | ✓ | | |
| **TC-CAT-011** | PDP loads with product props | S2 | ✓ | ✓ | | |
| **TC-CAT-012** | PDP structured variants resolvable | S3 | | ✓ | | |
| **TC-CART-001** | Guest POST `/cart` add (with CSRF) OK | S1 | ✓ | ✓ | | |
| **TC-CART-002** | PATCH cart qty valid (e.g. 2) | S2 | ✓ | ✓ | | |
| **TC-CART-003** | PATCH cart qty min boundary 1 | S3 | | ✓ | | |
| **TC-CART-004** | PATCH cart qty max boundary 10 | S3 | | ✓ | | |
| **TC-CART-005** | PATCH qty=99 → validation not 5xx | S2 | | ✓ | ✓ | |
| **TC-CART-006** | PATCH qty=0 → validation not 5xx | S2 | | ✓ | ✓ | |
| **TC-CART-007** | Apply valid coupon | S3 | | ✓ | | |
| **TC-CART-008** | Bogus coupon → errors not 5xx | S2 | ✓ | ✓ | ✓ | |
| **TC-CART-009** | Remove cart line | S3 | | ✓ | | |
| **TC-CART-010** | Clear cart | S3 | | ✓ | | |
| **TC-CHK-001** | PATCH `/zone` country before checkout | S2 | ✓ | ✓ | | |
| **TC-CHK-002** | Destination search empty `q` → 422 not 5xx | S3 | | ✓ | ✓ | |
| **TC-CHK-003** | Shipping address missing required fields → validation | S2 | | ✓ | ✓ | |
| **TC-CHK-004** | Shipping address missing `rajaongkir_destination_id` → validation | S2 | | ✓ | ✓ | |
| **TC-CHK-005** | Checkout step 2 delivery rates present | S2 | ✓ | ✓ | | |
| **TC-CHK-006** | Checkout payment methods present | S2 | ✓ | ✓ | | |
| **TC-CHK-007** | Saved address appears after valid save | S3 | | ✓ | | |
| **TC-CHK-008** | Place unpaid order (optional) | S3 | | ✓* | | |
| **TC-ACCT-001** | Dashboard `recentOrders` prop | S3 | ✓ | ✓ | | |
| **TC-ACCT-002** | Account orders list | S3 | | ✓ | | |
| **TC-ACCT-003** | Account order show (latest order) | S3 | | ✓ | | |
| **TC-ACCT-004** | Account addresses | S3 | | ✓ | | |
| **TC-ACCT-005** | Account notifications | S3 | | ✓ | | |
| **TC-ACCT-006** | Settings profile | S3 | | ✓ | | |
| **TC-ACCT-007** | Settings security (password gate) | S3 | | ✓ | | |
| **TC-SEC-001** | POST without CSRF token → 419 | S1 | | ✓ | | ✓ |
| **TC-SEC-002** | Guest GET `/account/orders` → login redirect | S1 | ✓ | ✓ | ✓ | ✓ |
| **TC-SEC-003** | POST webhook without signature → 401 | S1 | ✓ | ✓ | | ✓ |
| **TC-PERF-001** | Home response time | S3 | | ✓ | | |
| **TC-PERF-002** | Shop response time | S3 | | ✓ | | |
| **TC-PERF-003** | PDP response time | S3 | | ✓ | | |
| **TC-PERF-004** | Checkout step 2 response time | S3 | | ✓ | | |

\* TC-CHK-008 runs only when `DEPLOY_QA_PLACE_ORDER=YES`.

## Entry criteria

- Deploy completed; app responds on `DEPLOY_BASE_URL`.
- Customer test user exists and is verified (`DEPLOY_QA_EMAIL` / `DEPLOY_QA_PASSWORD`).
- Catalog seeded (≥1 published product on `/shop`).
- For checkout rate tests: Komerce/RajaOngkir configured **or** accept S2 failures documenting misconfiguration.
- PHP 8.2+ with project `vendor/` installed (runner bootstraps Laravel for helpers only).

## Exit criteria

- **Pass:** No S1/S2 defects; suite completes.
- **Pass with warnings:** S3-only defects and `DEPLOY_QA_STRICT` unset → exit 0, `ok: true`, non-empty `defects`.
- **Fail:** Any S1/S2 defect, or any S3 with `DEPLOY_QA_STRICT=YES`.

Each case prints: `PASS|FAIL|SKIP [TC-…] detail`  
Final JSON:

```json
{
  "ok": true,
  "suite": "regression",
  "summary": { "pass": 40, "fail": 0, "skip": 2, "s1": 0, "s2": 0, "s3": 0 },
  "defects": [],
  "cases": []
}
```

Defect object on FAIL:

```json
{
  "id": "def-001",
  "tc": "TC-CART-005",
  "severity": "S2",
  "title": "Cart qty over max returned 500",
  "expected": "422 or redirect with validation errors",
  "actual": "HTTP 500",
  "evidence": { "status": 500, "body_snippet": "..." },
  "area": "cart"
}
```

## How to run

```bash
# Local (from repo root, server on :8000)
php artisan cache:clear
DEPLOY_BASE_URL=http://127.0.0.1:8000 \
  DEPLOY_QA_SUITE=smoke \
  php scripts/deploy-qa-storefront-e2e.php

# Full regression (default suite)
DEPLOY_BASE_URL=https://staging.example.com \
  DEPLOY_QA_EMAIL=customer@example.com \
  DEPLOY_QA_PASSWORD='***' \
  php scripts/deploy-qa-storefront-e2e.php

# All cases including perf
DEPLOY_BASE_URL=http://127.0.0.1:8000 \
  DEPLOY_QA_SUITE=all \
  php scripts/deploy-qa-storefront-e2e.php

# Strict: fail CI on S3 perf/content issues
DEPLOY_QA_STRICT=YES DEPLOY_QA_SUITE=regression php scripts/deploy-qa-storefront-e2e.php

# Optional unpaid order (local only unless DEPLOY_QA_CONFIRM=YES)
DEPLOY_QA_PLACE_ORDER=YES DEPLOY_QA_SUITE=regression php scripts/deploy-qa-storefront-e2e.php
```

Secrets are never printed. Use CI secret stores for passwords and webhook keys.

## Browser QA (Playwright) — QA Engineer UI layer

| Artifact | Path |
|----------|------|
| Config | `playwright.config.ts` |
| Specs | `e2e/*.spec.ts` |
| Helpers | `e2e/helpers/qa.ts` (login, a11y axe) |

### Browser suites

```bash
# Requires app on PLAYWRIGHT_BASE_URL (default http://127.0.0.1:8000) + built assets
npm run test:e2e:smoke          # @smoke tagged chromium
npm run test:e2e                # full chromium browser suite
npm run test:e2e:a11y           # axe WCAG critical/serious sweep
npm run test:e2e:mobile         # Pixel 7 viewport
npm run test:e2e:all            # chromium + mobile
npm run qa:full                 # Playwright chromium + HTTP regression
```

| ID | Title | Layer |
|----|-------|-------|
| TC-UI-CAT-001…005 | Guest home/shop/categories/search/PDP + a11y | Playwright |
| TC-UI-AUTH-001…004 | Login a11y, wrong password, valid login, guest checkout gate | Playwright |
| TC-UI-CART-001…002 | Empty cart, add-to-cart | Playwright |
| TC-UI-CHK-001 | Checkout address/rates UI path | Playwright |
| TC-UI-ACCT-001 | Account orders | Playwright |
| TC-UI-A11Y-001…008 | axe-core WCAG 2.1 A/AA critical+serious | Playwright |

Env: `PLAYWRIGHT_BASE_URL`, `PLAYWRIGHT_EMAIL`, `PLAYWRIGHT_PASSWORD` (fallback to `DEPLOY_QA_*`).

### Remaining gaps (honest)

- Visual snapshot regression (Percy/Chromatic) not wired yet — failure screenshots retained by Playwright.
- Full paid QRIS capture still HTTP/sandbox-dependent (`deploy-e2e.php` full).
- cPanel Livewire admin deep ops still out of storefront scope.
- Cross-browser Safari/Firefox optional (Chromium + mobile Chrome covered).
- Login throttle is **60/min on local/testing** (5/min in production) so browser E2E can exercise multiple auth journeys without 429 noise.

## How to run (combined QA Engineer gate)

```bash
# 1) Clear cache if a prior run hit Fortify login throttle
npm run qa:clear-throttle

# 2) Browser + HTTP regression (QA Engineer default)
PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000 \
  DEPLOY_BASE_URL=http://127.0.0.1:8000 \
  npm run qa:full
```

## Negative test expectations (must not 5xx)

| Case | Action | Expected |
|------|--------|----------|
| TC-AUTH-003 | POST `/login` wrong password | 422 or 302 to login; still guest |
| TC-AUTH-004 | GET `/checkout` as guest | 302 to `/login` |
| TC-CART-001 vs TC-AUTH-004 | POST `/cart` as guest with CSRF | 200/302 OK |
| TC-CHK-004 | POST shipping-address without destination id | 422/302 + field errors |
| TC-CART-008 | POST `/cart/coupon` bogus code | 422/302 + errors |
| TC-CART-005/006 | PATCH qty 99 or 0 | 422/302, not 500 |
| TC-SEC-003 | POST `/webhooks/komerce/payment` unsigned | 401 |
| TC-SEC-002 | GET `/account/orders` as guest | 302 to login |

## Roadmap

1. ~~**Playwright** layer for visual smoke + critical UI flows~~ ✅ (`e2e/`, `npm run test:e2e`)
2. ~~**axe-core** a11y scan on primary pages~~ ✅ (`npm run test:e2e:a11y`)
3. Wire `npm run qa:full` into deploy CI after HTTP `deploy-qa-storefront-e2e.php`.
4. Optional: visual snapshot service + Firefox/WebKit projects.
