# OceanMall Mobile

Aplikasi marketplace customer (iOS / Android / web) dengan **Expo Router**, **NativeWind**, dan **React Native Reusables**.

Alur yang didukung: daftar/masuk → browse/cari produk → keranjang → checkout (alamat RajaOngkir + ongkir + pembayaran Komerce) → cek bayar → lacak resi → konfirmasi diterima.

Backend: Laravel `/api/v1` (Sanctum Bearer). Bukan admin `/cpanel`.

## Prasyarat

- Node.js 20+
- Laravel jalan di `http://127.0.0.1:8000` (`composer run dev` atau `php artisan serve`)
- Expo Go di HP, atau simulator iOS / emulator Android

## Jalanin

```bash
cd mobile
cp .env.example .env   # sesuaikan EXPO_PUBLIC_API_URL
npm install
npm run dev
```

- **iOS simulator**: tekan `i` (API default `http://127.0.0.1:8000/api/v1`)
- **Android emulator**: tekan `a` (API default `http://10.0.2.2:8000/api/v1`)
- **HP fisik**: set `EXPO_PUBLIC_API_URL=http://<IP-LAN-laptop>:8000/api/v1` dan serve Laravel dengan `--host=0.0.0.0`
- **Web**: tekan `w`

Request API selalu kirim `Accept: application/json` + `Authorization: Bearer` setelah login.

## UI

Komponen dari [React Native Reusables](https://reactnativereusables.com) (`Button`, `Input`, `Card`, `Badge`, `Label`, `Separator`). Tambah komponen:

```bash
npx @react-native-reusables/cli@latest add
```
