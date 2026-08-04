# Storefront Eraspace Mobile-First — Implementation Plan

> **For agentic workers:** Steps use existing Vue/Inertia pages; no backend changes. Prefer visual/copy edits only.

**Goal:** Reskin OceanMall storefront (mobile-first, eraspace-like) with Indonesian copy; preserve Komerce checkout logic.

**Spec:** `docs/superpowers/specs/2026-08-04-storefront-eraspace-mobile-first-design.md`

**Approach:** A — in-place reskin on `.storefront` scope.

## File map

| Area | Files |
|------|--------|
| Tokens | `resources/css/app.css`, `vite.config.ts` (Plus Jakarta Sans) |
| Shell | `storefront-layout.vue`, `store-header.vue`, `store-footer.vue` |
| Components | announcement, trust-badges, product-card, price-display, category-card, collection-banner |
| Pages | home, index, search, categories, category, collection, product, cart, checkout, checkout-success |

## Tasks (status)

1. ✅ Design spec
2. ✅ Tokens + font
3. ✅ Header/footer + ID copy
4. ✅ Home + shared shop components
5. ✅ Browse path pages
6. ✅ Cart/checkout/success visual + ID copy
7. ⬜ Verify Vite build

## Verification

```bash
npm run build
# Manual: open / on mobile width — header search, category chips, blue CTAs
# Smoke: cart → checkout steps still submit
```

## Out of scope

Loyalty, O2O pickup, bottom tab bar, account pages, Komerce API changes.
