# OceanMall

E-commerce storefront starter berbasis [Laravel](https://laravel.com) + [Shopper](https://laravel.shopper.io) Vue Starter Kit.

## Stack

- PHP 8.3+, Laravel 13
- Shopper 2.11
- Inertia.js 3 + Vue 3 + TypeScript
- Tailwind CSS 4 + Vite 8
- Laravel Fortify (auth + 2FA)
- PostgreSQL (Production) / SQLite (Dev)

## Fitur bawaan

- Storefront: home, produk, kategori, koleksi, search
- Cart & zone/shipping
- Checkout (payment method dikonfigurasi di admin Shopper)
- Account: orders & addresses
- Admin panel Shopper di `/cpanel`
- Customer API `/api/v1` (Sanctum Bearer) + aplikasi mobile Expo di `mobile/`

## Requirements

- PHP 8.3+ dengan ekstensi umum Laravel
- Composer 2
- Node.js 20+ / npm
- Lisensi Shopper (paket private) — siapkan `auth.json` Composer di mesin lokal

## Deployment (Production - 500 CCU Ready)

Untuk menampung trafik tinggi (500 CCU+), aplikasi ini dikonfigurasi menggunakan **Laravel Octane (FrankenPHP)**, **PostgreSQL**, dan **Redis**.

Pastikan server (Ubuntu/Debian) sudah terinstall:
- PostgreSQL (`postgresql postgresql-contrib php8.4-pgsql`)
- Redis Server (`redis-server`)
- FrankenPHP / Octane (dari library `laravel/octane` + `predis/predis` yang sudah tersedia di codebase)

### Konfigurasi `.env`
Pastikan environment database, cache, session, dan queue menggunakan PostgreSQL dan Redis:

```env
OCTANE_SERVER=frankenphp
OCTANE_PORT=8087

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=oceanmall
DB_USERNAME=oceanmall
DB_PASSWORD=oceanmall123

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Menjalankan Server
Build frontend assets, kemudian jalankan Laravel Octane:

```bash
# Clear configs
php artisan config:clear
php artisan route:cache

# Build frontend
npm run build

# Start server dengan Octane di port 8087 (di-reverse proxy oleh Nginx)
npm run start
```

Octane akan berjalan di port `8087`. Pastikan Nginx server Anda diatur sebagai _reverse proxy_ yang meneruskan trafik ke `127.0.0.1:8087`.

## Setup Development (Lokal)

```bash
git clone https://github.com/kungfufafa/oceanmall.git
cd oceanmall

composer install
cp .env.example .env
php artisan key:generate

# SQLite (default)
touch database/database.sqlite
php artisan migrate

# Demo catalog storefront (brand, kategori, koleksi, produk + harga/promo)
php artisan db:seed --class=StorefrontDemoSeeder

npm install
npm run build
```

Atau lewat script Composer:

```bash
composer run setup
```

## Development

```bash
composer run dev
```

Menjalankan `artisan serve`, queue listener, log viewer, dan Vite sekaligus.

App: [http://localhost:8000](http://localhost:8000)  
Admin: [http://localhost:8000/cpanel](http://localhost:8000/cpanel)

### Mobile (Expo)

Aplikasi customer iOS/Android ada di `mobile/` (Expo Router + NativeWind + React Native Reusables), memakai `/api/v1`.

```bash
cd mobile
cp .env.example .env
npm install
npm run dev
```

Detail: [`mobile/README.md`](mobile/README.md).

## Environment

Salin dari `.env.example`. Prefix admin bisa diubah lewat `SHOPPER_PREFIX` (default: `cpanel`).

### Payment & shipping (Phase 1 — Komerce / RajaOngkir)

OceanMall checkout aktif memakai **Komerce Payment** (VA / QRIS) + **RajaOngkir Shipping Cost**.

- **Driver** Shopper: `Payment::extend('komerce')`, `Shipping::extend('rajaongkir'|'komerce')` — pola yang sama dengan `shopper/stripe` / UPS. Checkout, webhook HMAC, retry/expire, AWB (`createShipment`), tracking, dan cetak label memakai facade Shopper.
- **Addon** Shopper: `app/Addons/KomerceRajaOngkir` (`shopper.addons.komerce-rajaongkir`) — panel order, form origin gudang, dan override Livewire admin. Matikan lewat `config/shopper/addons.php` tanpa menyentuh driver checkout.

Stripe **tetap mati** by default.

| Variable | Keterangan |
| --- | --- |
| `KOMERCE_SHIPPING_COST_API_KEY` | Key **Shipping Cost** dari [collaborator settings](https://collaborator.komerce.id/settings) |
| `KOMERCE_SHIPPING_DELIVERY_API_KEY` | Key **Shipping Delivery** |
| `KOMERCE_PAYMENT_API_KEY` | Key **Payment API** (VA / bank transfer) |
| `KOMERCE_QRISLY_API_KEY` | Key **QRISLY API** — **opsional**. Kosong = QRIS lewat Payment API |
| `KOMERCE_QRISLY_QRIS_ID` | ID template QRIS dari upload di Collaborator (wajib kalau QRISLY aktif) |
| `KOMERCE_QRISLY_BASE_URL` | Default = `KOMERCE_PAYMENT_BASE_URL` |
| `KOMERCE_QRISLY_UNIQUE_AMOUNT` | Default `true` (generate-qris) |
| `KOMERCE_PAYMENT_BASE_URL` | Default sandbox: `https://api-sandbox.collaborator.komerce.id/user` |
| `RAJAONGKIR_COST_BASE_URL` | Default: `https://rajaongkir.komerce.id` |
| `RAJAONGKIR_DELIVERY_BASE_URL` | Sandbox delivery / AWB base URL |
| `RAJAONGKIR_COURIERS` | Kurir aktif, comma-separated (default: `jne,jnt,sicepat`) |
| `KOMERCE_WEBHOOK_SECRET` | Secret buatan sendiri untuk **Payment callback**; dikirim sebagai `callback_API_KEY` dan diverifikasi melalui HMAC-SHA256 raw body |
| `KOMERCE_PICKUP_VEHICLE` | Kendaraan pickup (default: `Motor`) |
| `KOMERCE_PICKUP_TIME` | Jam pickup format `HH:mm:ss` (default: `10:00:00`) |
| `KOMERCE_TIMEOUT` | Timeout HTTP client (detik) |
| `PAYMENT_STRIPE_ENABLED` | **Harus `false`** untuk production OceanMall v1 |

Webhook URL (expose ke Komerce sandbox/production):

```
POST {APP_URL}/webhooks/komerce/payment
POST {APP_URL}/webhooks/komerce/delivery
POST {APP_URL}/webhooks/komerce/qrisly
```

Hanya callback **Payment API** yang secara resmi mendefinisikan header `X-Callback-Api-Key` berisi HMAC-SHA256 raw body dengan `KOMERCE_WEBHOOK_SECRET`. Dokumentasi Delivery dan QRISLY tidak mendefinisikan signature; handler memperlakukan payload mereka sebagai sinyal lalu memeriksa status melalui API provider yang memakai dedicated key sebelum mengubah order.

### Production ops (wajib jalan)

Checkout Komerce bergantung pada background jobs. Tanpa ini AWB/tracking/expire unpaid tidak jalan:

| Proses | Cara |
| --- | --- |
| Queue worker | `php artisan queue:work` (atau `composer run dev`) |
| Scheduler | Cron tiap menit: `* * * * * php artisan schedule:run` (dev: `schedule:work`) |
| Expire unpaid | `komerce:expire-unpaid-orders` (jadwal di `routes/console.php`) |
| Tracking poll | `komerce:refresh-shipment-tracking` (hourly) |

Inventory gudang **harus** punya `rajaongkir_origin_id` — tanpa itu checkout step 2 kosong.

Di Collaborator → Developer → Webhook:

- **Webhook Payment** — log callback; URL payment dikirim per-order via `callback_url` (tidak perlu set di form itu).
- **Webhook Shipping Delivery** → **Add Webhook URL** → isi `{APP_URL}/webhooks/komerce/delivery`.
- **Webhook QRISLY** → register `{APP_URL}/webhooks/komerce/qrisly` hanya jika QRISLY aktif (`KOMERCE_QRISLY_API_KEY` + `KOMERCE_QRISLY_QRIS_ID`). Kalau kosong, biarkan — checkout QRIS tetap lewat Payment API.

Jangan commit `.env` atau API key asli.

### Sandbox checklist (manual)

1. Set keempat API key dari collaborator settings + `KOMERCE_WEBHOOK_SECRET`, biarkan `PAYMENT_STRIPE_ENABLED=false`.
2. `php artisan migrate` — pastikan kolom `rajaongkir_origin_id` ada di inventories.
3. Di admin Shopper (`/cpanel`): buat / set **Inventory default** (mis. Gudang Jakarta) dan isi `rajaongkir_origin_id` (ID origin dari RajaOngkir destination search).
4. Buat Payment Method dengan `driver=komerce` (metadata `payment_type` = `bank_transfer` + `channel_code` bank, atau `qris`), aktifkan di zone Indonesia.
5. Jalankan queue worker (`composer run dev` sudah include) + scheduler (`php artisan schedule:work`) untuk AWB create, tracking poll, dan expire unpaid.
6. Di storefront checkout: isi alamat + **cari district** (RajaOngkir destination) → pilih kurir → pilih **QRIS** (atau VA) → Place order.
7. Salin instruksi bayar (nomor VA / QRIS). Response Payment API memakai field `va_number` / `qr_string` / `expired_at` / `payment_url` — storefront sudah memetakan ke panel VA/QRIS.
8. Order harus beralih ke `payment_status=paid`; job membuat AWB per shipment.
9. Admin ops: buka `/cpanel/orders/{id}/detail` — panel **RajaOngkir / Komerce shipping** (cetak label / pindah gudang sebelum AWB).
10. Customer di `/account/orders/{id}`: Track package → status dinormalisasi; atau **Mark as received** untuk menutup order.
11. UAT otomatis hulu→hilir (QRIS): `php scripts/live-customer-qris-e2e-uat.php` — lihat juga `docs/superpowers/specs/2026-08-04-customer-storefront-walkthrough.md`.
12. UAT gudang (paid → AWB → cetak label → webhook delivered): `php scripts/live-warehouse-ops-e2e-uat.php` — lihat `docs/superpowers/specs/2026-08-04-warehouse-ops-walkthrough.md`. Ops UI: `/cpanel/orders/{id}/detail` (Shopper only).

Catatan QRISLY: tanpa `KOMERCE_QRISLY_QRIS_ID`, QRISLY dimatikan otomatis dan QRIS tetap lewat Payment API.

### Staging happy path (beli → diterima)

| Peran | Langkah | Endpoint / UI |
| --- | --- | --- |
| Customer | Cari district → checkout → bayar | `/checkout` |
| System | Webhook paid → create AWB | `POST /webhooks/komerce/payment` + queue |
| Admin | Print label / override | `/cpanel/orders/{id}/detail` (panel RajaOngkir) |
| Customer | Track / mark received | `/account/orders/{id}` |
| System | Expire unpaid + release stock | `komerce:expire-unpaid-orders` (setiap 15 mnt) |
| System | Poll tracking | `komerce:refresh-shipment-tracking` (hourly) |

Scheduler entries ada di `routes/console.php`. Di staging/production pastikan cron memanggil `php artisan schedule:run` tiap menit (atau `schedule:work` di dev).

### Stripe

Stripe **dimatikan by default** (`PAYMENT_STRIPE_ENABLED=false`). Jangan set key Stripe kecuali memang mau dipakai untuk eksperimen; path storefront aktif adalah Komerce.

## Scripts berguna

| Command | Keterangan |
| --- | --- |
| `composer run setup` | Install deps, key, migrate, build assets |
| `composer run dev` | Dev server full stack |
| `composer test` | Jalankan PHPUnit |
| `npm run lint` | ESLint (auto-fix) |
| `npm run format` | Prettier di `resources/` |
| `npm run types:check` | Cek tipe Vue/TS |

## Struktur singkat

```
app/Http/Controllers/Shop/   # Storefront controllers
app/Actions/                 # Cart, checkout, product actions
resources/js/pages/shop/     # Vue pages (Inertia)
config/shopper/              # Konfigurasi Shopper
routes/web.php               # Storefront + checkout routes
```

## Catatan

- Jangan commit `.env` atau `auth.json`.
- Kit sumber: `shopperlabs/vue-starter-kit` (lihat `.shopper-kit`).
- Dokumentasi Shopper: https://laravel.shopper.io

## License

MIT (kode aplikasi). Paket Shopper mengikuti lisensi masing-masing dari Shopper Labs.
