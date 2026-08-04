# Reusable checkout shipping address

## Problem

Setiap checkout customer harus isi ulang alamat + cari district RajaOngkir. Alamat tersimpan di UI ada, tapi checkout tidak menulis ke buku alamat, dan memilih alamat tersimpan menghapus district.

## Approach

1. Saat simpan alamat di checkout → upsert ke `sh_user_addresses` (match street+postal+city), simpan `rajaongkir_destination_id/label` di `metadata`, set `shipping_default`.
2. Saat buka checkout tanpa session address → auto-apply alamat utama (yang punya district) → langsung step ongkir.
3. Kartu “Alamat tersimpan”: tampilkan district; tap sekali (jika district ada) → submit & lanjut ongkir.

## Out of scope

- Multi-profile address editor redesign
- Migrating historical orders into address book automatically in production (UAT seeded manually)
