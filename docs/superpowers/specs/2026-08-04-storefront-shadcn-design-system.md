# Storefront Design System — shadcn-vue (atomic → molecule)

## Problem

OceanMall already has **shadcn-vue** (`components.json`, `resources/js/components/ui/*`) and OceanMall tokens in `.storefront`, but almost every shop page hand-rolls Tailwind (`om-btn-*`, raw inputs, ad-hoc badges/cards/empty states). Result: visual drift (“slop”), hard to maintain, not production-polished.

## Decision

**Maximize existing shadcn-vue atoms.** Compose storefront molecules on top. Do not invent a second button/input/card system.

| Layer | Location | Rule |
| --- | --- | --- |
| Tokens | `resources/css/app.css` `.storefront` | Brand + semantic colors; no hardcoded `#E11D48` in Vue |
| Atoms | `@/components/ui/*` | shadcn Button, Input, Badge, Card, Alert, Select, Separator, … |
| Molecules | `@/components/shop/*` | ProductCard, QtyStepper, EmptyState, SectionHeader, PagePagination, SearchField, PriceDisplay |
| Pages | `@/pages/shop/*` | Layout + compose molecules only |

## Visual direction (keep OceanMall)

- Brand navy `--om-navy` as primary (already mapped to `--primary`)
- Sale / urgency: `--om-sale` (was `#E11D48` / `#FF3B5C`)
- Radius `--om-radius` (6px), control height 44px
- Font: Plus Jakarta Sans
- Cards: prefer flat bordered surfaces; **no card clutter in hero**
- Prefer semantic tokens (`bg-primary`, `text-muted-foreground`) over zinc one-offs where possible

## Out of scope

- Shopper `/cpanel` Livewire UI (separate design system)
- Full dark-mode storefront redesign
- Replacing Komerce/checkout domain logic

## Rollout order

1. Tokens
2. Missing shadcn atoms
3. Molecules
4. Listing pages → PDP → cart → checkout → chrome

## Success

- Shop pages import shadcn atoms / shop molecules; no new `om-btn-*` usage in pages
- `npm run build` + `npm run types:check` pass
- Visual consistency across home → checkout


## Status (2026-08-05)

Pass-2 complete on branch `cursor/storefront-ui-design-system-2aff`:

- shadcn Select (FilterSelect), Tabs (shop category), RadioGroup + SelectableCard (checkout)
- shop Card wraps ui Card
- storefront zinc purge → semantic tokens
- om-btn purged
- Browser smoke: home / shop / PDP / cart EmptyState PASS
