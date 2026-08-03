# OceanMall

E-commerce storefront starter berbasis [Laravel](https://laravel.com) + [Shopper](https://laravel.shopper.io) Vue Starter Kit.

## Stack

- PHP 8.3+, Laravel 13
- Shopper 2.11 (+ Stripe addon)
- Inertia.js 3 + Vue 3 + TypeScript
- Tailwind CSS 4 + Vite 8
- Laravel Fortify (auth + 2FA)
- SQLite by default (bisa diganti MySQL/PostgreSQL)

## Fitur bawaan

- Storefront: home, produk, kategori, koleksi, search
- Cart & zone/shipping
- Checkout + Stripe payment + webhook
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

Salin dari `.env.example`. Untuk pembayaran Stripe, tambahkan di `.env`:

```env
PAYMENT_STRIPE_ENABLED=true
PAYMENT_SANDBOX=true
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Prefix admin bisa diubah lewat `SHOPPER_PREFIX` (default: `cpanel`).

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
