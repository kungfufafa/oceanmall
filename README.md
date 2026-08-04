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
| `KOMERCE_SHIPPING_COST_API_KEY` | Key **Shipping Cost** dari [collaborator settings](https://collaborator.komerce.id/settings) |
| `KOMERCE_SHIPPING_DELIVERY_API_KEY` | Key **Shipping Delivery** |
| `KOMERCE_PAYMENT_API_KEY` | Key **Payment API** (VA / bank transfer) |
| `KOMERCE_QRISLY_API_KEY` | Key **QRISLY API** — **opsional**. Kosong = QRIS lewat Payment API |
| `KOMERCE_QRISLY_QRIS_ID` | ID template QRIS dari upload di Collaborator (wajib kalau QRISLY aktif) |
| `KOMERCE_QRISLY_BASE_URL` | Default = `KOMERCE_PAYMENT_BASE_URL` |
| `KOMERCE_QRISLY_UNIQUE_AMOUNT` | Default `true` (generate-qris) |
| `KOMERCE_API_KEY` | Legacy fallback satu key (opsional; tests / setup lama) |
| `KOMERCE_PAYMENT_BASE_URL` | Default sandbox: `https://api-sandbox.collaborator.komerce.id/user` |
| `RAJAONGKIR_COST_BASE_URL` | Default: `https://rajaongkir.komerce.id` |
| `RAJAONGKIR_DELIVERY_BASE_URL` | Sandbox delivery / AWB base URL |
| `RAJAONGKIR_COURIERS` | Kurir aktif, comma-separated (default: `jne,jnt,sicepat`) |
| `KOMERCE_WEBHOOK_SECRET` | Secret buatan sendiri; dikirim sebagai `callback_API_KEY`. Callback Payment diverifikasi via **HMAC-SHA256** (`X-Callback-Api-Key`) |
| `KOMERCE_PICKUP_VEHICLE` | Kendaraan pickup (default: `Motor`) |
| `KOMERCE_PICKUP_TIME` | Jam pickup default (default: `10:00`) |
| `KOMERCE_TIMEOUT` | Timeout HTTP client (detik) |
| `PAYMENT_STRIPE_ENABLED` | **Harus `false`** untuk production OceanMall v1 |

Webhook URL (expose ke Komerce sandbox/production):

```
POST {APP_URL}/webhooks/komerce/payment
POST {APP_URL}/webhooks/komerce/delivery
POST {APP_URL}/webhooks/komerce/qrisly
```

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
6. Di storefront checkout: isi alamat + **cari district** (RajaOngkir destination) → pilih kurir → pilih VA/QRIS → Place order.
7. Salin instruksi bayar (nomor VA / QRIS). Simulasikan callback paid ke webhook, atau gunakan status sandbox Komerce.
8. Order harus beralih ke `payment_status=paid`; job membuat AWB per shipment.
9. Admin ops: buka `/admin/orders/{id}` untuk print label / override gudang (sebelum AWB).
10. Customer di `/account/orders/{id}`: Track package → status dinormalisasi; atau **Mark as received** untuk menutup order.

### Staging happy path (beli → diterima)

| Peran | Langkah | Endpoint / UI |
| --- | --- | --- |
| Customer | Cari district → checkout → bayar | `/checkout` |
| System | Webhook paid → create AWB | `POST /webhooks/komerce/payment` + queue |
| Admin | Print label / override | `GET /admin/orders/{id}` |
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
