# AGENTS.md

## Cursor Cloud specific instructions

OceanMall is a single Laravel 13 + Shopper e-commerce app (no monorepo). Two surfaces of the
same app: the Inertia/Vue 3 storefront + customer account, and the Shopper (Filament/Livewire)
admin panel at `/cpanel`. Standard commands live in `README.md`, `composer.json` (`scripts`),
and `package.json` (`scripts`) — use those; only the non-obvious notes below are documented here.

### Runtime / stack facts
- **PHP 8.4 is required** (not just 8.3). The committed `composer.lock` pins
  `roave/better-reflection`, which requires `~8.4`, so `composer install` fails platform checks
  on PHP 8.3. PHP 8.4 satisfies the app's `^8.3` constraint.
- DB is SQLite (`database/database.sqlite`); sessions/cache/queue are database-backed. No external
  datastore is needed for local dev.
- The dev env (`.env`, migrated `database/database.sqlite`, `vendor/`, `node_modules/`,
  `public/build`) is created during setup and persists in the VM snapshot; it is gitignored, so it
  is NOT recreated by pulling the repo. If a truly fresh env is ever needed, replay `README.md`
  setup: `cp .env.example .env`, `php artisan key:generate`, `touch database/database.sqlite`,
  `php artisan migrate`.

### Running the app
- `composer run dev` runs `php artisan serve` (:8000), `queue:listen`, `pail`, and `npm run dev`
  (Vite HMR, :5173) together. App: `http://localhost:8000` · Admin: `http://localhost:8000/cpanel`.
- Admin login created during setup: `admin@oceanmall.test` / `password123` (role `administrator`).
  The admin dashboard is `/cpanel/dashboard`. Roles/permissions and base domain data were seeded
  via `Shopper\Database\Seeders\AuthTableSeeder` and `Shopper\Core\Database\Seeders\ShopperSeeder`.

### Non-obvious gotchas
- **Case-sensitivity (repo was developed on macOS; Linux is case-sensitive).**
  - The Vite build imports must match on-disk file casing exactly. A fix was needed for
    `resources/js/components/shop/announcement-bar.vue` (imported `./Container.vue`; file is
    `container.vue`). Watch for similar casing mismatches when the build says "Module not found".
  - Laravel Wayfinder regenerates `Index.ts` (PascalCase) alongside the committed `index.ts`
    (lowercase) under `resources/js/actions/**` and `resources/js/routes/**` on Linux. These are
    generated build artifacts — do NOT commit the regenerated case-variant duplicates (or the
    `package-lock.json` churn) that appear after `npm install` / `npm run build`.
- **Do NOT run `php artisan shopper:link` when serving via `php artisan serve`.** It creates a real
  `public/cpanel` symlink that shadows the `/cpanel` admin route (PHP's built-in server serves the
  physical directory, so `/cpanel` 404s). The repo intentionally ships a dangling `public/cpanel`
  symlink so `/cpanel` routes to the panel; leave it dangling. The only side effect is that a few
  cosmetic `/cpanel/images/*` favicons 404.
- **Tests need built assets.** Feature tests render Inertia views and fail with "Vite manifest not
  found" unless `npm run build` has been run (or the Vite dev server is up). Run `npm run build`
  before `composer test`.
- **Known pre-existing test failures on this branch** (app-level, not environment issues): ~15 of
  93 fail — Inertia page-name casing mismatches in tests (e.g. expected `settings/Security` vs
  actual `settings/security`), checkout endpoints returning 409, and profile update field-name
  mismatches (`first_name`/`last_name`). The environment itself runs the full suite fine.
- **Payment/shipping (Komerce / RajaOngkir).** Full checkout needs sandbox creds
  (`KOMERCE_API_KEY`, `KOMERCE_WEBHOOK_SECRET`, RajaOngkir base URLs). Storefront browsing, cart,
  and the admin panel work without them. Stripe stays disabled (`PAYMENT_STRIPE_ENABLED=false`).
