# OceanMall

E-commerce storefront starter berbasis [Laravel](https://laravel.com) + [Shopper](https://laravel.shopper.io) Vue Starter Kit.

## Stack

- PHP 8.3+, Laravel 13
- Shopper 2.11
- Inertia.js 3 + Vue 3 + TypeScript
- Tailwind CSS 4 + Vite 8
- Laravel Fortify (auth + 2FA)
- SQLite by default (bisa diganti MySQL/PostgreSQL)

## Fitur bawaan

- Storefront: home, produk, kategori, koleksi, search
- Cart & zone/shipping
- Checkout (payment method dikonfigurasi di admin Shopper)
- Account: orders & addresses
- Admin panel Shopper di `/cpanel`

## Requirements

- PHP 8.3+ dengan ekstensi umum Laravel
- Composer 2
- Node.js 20+ / npm
- Lisensi Shopper (paket private) — siapkan `auth.json` Composer di mesin lokal

## Setup

```bash
git clone https://github.com/kungfufafa/oceanmall.git
cd oceanmall

composer install
cp .env.example .env
php artisan key:generate

# SQLite (default)
touch database/database.sqlite
php artisan migrate

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

## Environment

Salin dari `.env.example`. Prefix admin bisa diubah lewat `SHOPPER_PREFIX` (default: `cpanel`).

### Payment & shipping (Phase 1 — Komerce / RajaOngkir)

OceanMall checkout aktif memakai **Komerce Payment** (VA / QRIS) + **RajaOngkir Shipping Cost**. Stripe **tetap mati** by default.

| Variable | Keterangan |
| --- | --- |
| `KOMERCE_API_KEY` | API key dari dashboard Komerce / RajaOngkir (sandbox dulu) |
| `KOMERCE_PAYMENT_BASE_URL` | Default sandbox: `https://api-sandbox.collaborator.komerce.id/user` |
| `RAJAONGKIR_COST_BASE_URL` | Default: `https://rajaongkir.komerce.id` |
| `RAJAONGKIR_DELIVERY_BASE_URL` | Reserved untuk Phase 3 (AWB / pickup) |
| `RAJAONGKIR_COURIERS` | Kurir aktif, comma-separated (default: `jne,jnt,sicepat`) |
| `KOMERCE_WEBHOOK_SECRET` | Secret untuk verifikasi callback (`callback_API_KEY`) |
| `KOMERCE_TIMEOUT` | Timeout HTTP client (detik) |
| `PAYMENT_STRIPE_ENABLED` | **Harus `false`** untuk production OceanMall v1 |

Webhook URL (expose ke Komerce sandbox/production):

```
POST {APP_URL}/webhooks/komerce/payment
```

Jangan commit `.env` atau API key asli.

### Sandbox checklist (manual)

1. Set `KOMERCE_API_KEY`, `KOMERCE_WEBHOOK_SECRET`, biarkan `PAYMENT_STRIPE_ENABLED=false`.
2. `php artisan migrate` — pastikan kolom `rajaongkir_origin_id` ada di inventories.
3. Di admin Shopper (`/cpanel`): buat / set **Inventory default** (mis. Gudang Jakarta) dan isi `rajaongkir_origin_id` (ID origin dari RajaOngkir destination search).
4. Buat Payment Method dengan `driver=komerce` (metadata `payment_type` = `bank_transfer` + `channel_code` bank, atau `qris`), aktifkan di zone Indonesia.
5. Di storefront checkout: isi alamat + **Destination ID (RajaOngkir)** tujuan customer → pilih kurir → pilih VA/QRIS → Place order.
6. Salin instruksi bayar (nomor VA / QRIS). Simulasikan callback paid ke webhook, atau gunakan status sandbox Komerce.
7. Order harus beralih ke `payment_status=paid`.

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
