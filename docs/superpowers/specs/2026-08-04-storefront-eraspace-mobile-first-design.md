# Storefront Eraspace Mobile-First — Design

**Branch:** `feat/storefront-eraspace-mobile-first`  
**Date:** 2026-08-04  
**Approach:** A — reskin in-place (routes/data/Komerce logic unchanged)

## Goals

Rebuild OceanMall storefront look & feel inspired by eraspace.com, mobile-first:

- Retail-tech visual system (white + blue/navy accents)
- Indonesian UI copy
- Full browse + cart + checkout **visual** refresh
- App-like mobile shell matching eraspace homepage structure:
  - Navy sticky header with search-first + cart
  - Location strip
  - Guest Masuk/Daftar card
  - Circular category icon row
  - Horizontal promo/collection carousel
  - Quick shortcut grid
  - Sticky bottom nav (Home / Kategori / Belanja / Keranjang / Akun)
- No loyalty, click & pickup backend, multi-brand O2O, or checkout logic changes

## Visual foundation

| Token | Value |
|-------|--------|
| Surface | white / zinc-50 strips |
| Accent / CTA | blue ≈ `#0B5FFF` (primary in `.storefront`) |
| Heading | navy ≈ `#0B1F44` |
| Radius | 8–12px |
| Type | Plus Jakarta Sans (heading + body in storefront) |
| Dark mode | light-first; dark classes kept, not focus |

Scoped via `.storefront` on `storefront-layout` so admin/account shells stay unchanged.

## Surfaces in scope

1. Shell: announcement, sticky header + search, footer
2. Home: promo hero, category chips, collections, featured grid, trust badges
3. Browse: shop index, search, categories, category, collection, product
4. Cart + checkout + success: copy ID + retail styling; Komerce panels/logic untouched

## Out of scope

- Bottom tab bar (optional later)
- Loyalty / points / vouchers
- Click & pickup / store locator
- Backend/API changes
- Account pages (except shared shop components)

## Success criteria

- Mobile (375px) homepage reads as retail gadget store, not generic SaaS template
- Primary CTAs use blue accent; prices/promo badges clear
- All user-facing storefront strings in Indonesian
- Checkout still creates Komerce payments / rates as before
