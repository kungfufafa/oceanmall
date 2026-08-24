# Design: Shopper `/cpanel` is the only backoffice

## Decision

OceanMall has **one** backoffice: Shopper at `/cpanel` (prefix from `SHOPPER_PREFIX`).

There must be **no** parallel admin app under `/admin`, no Inertia admin pages, and no legacy `/admin` redirects.

## Why

- One place to manage catalog, orders, inventory, team, and warehouse fulfillment.
- Storefront (Inertia) stays customer-facing; ops stay in Shopper.
- Avoids duplicate ACL, menus, and “which admin URL?” confusion.

## Shape

| Concern | Where |
| --- | --- |
| Catalog, orders, settings, team | Shopper Livewire `/cpanel/*` |
| RajaOngkir print label / override | Livewire panel on `/cpanel/orders/{id}/detail` |
| Print / override HTTP endpoints | `/cpanel/orders/{id}/fulfillment/*` (`App\Http\Controllers\Cpanel\…`) |
| Customer storefront | Inertia routes (`/`, `/shop`, `/checkout`, `/account`, …) |

## Explicit non-goals

- `/admin/*` routes (404)
- `resources/js/pages/admin/*`
- Separate Filament/Inertia admin shell

## Note on `config('shopper.admin.*')`

Shopper’s config namespace (`admin.prefix`, `admin.roles`) is framework config, not a second backoffice URL.
