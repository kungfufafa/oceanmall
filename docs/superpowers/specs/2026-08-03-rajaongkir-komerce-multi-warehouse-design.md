# OceanMall — RajaOngkir / Komerce + Multi-Warehouse Design

**Date:** 2026-08-03  
**Status:** Approved — execute via Long-running Agent (phased P1→P3)  
**Approach:** A — Adapter di atas Shopper (Stripe/UPS diganti Komerce/RajaOngkir)

## Goal

Ganti payment/shipping bawaan starter (Stripe + carrier US) dengan ekosistem **RajaOngkir Enterprise / Komerce**, mendukung **beberapa gudang** (Cirebon, Jakarta, …) dengan:

- sistem **mengusulkan** alokasi gudang
- **admin bisa override** sebelum AWB dibuat
- **split shipment** jika stok pecah antar gudang
- **satu pembayaran** per order (total barang + total ongkir semua paket)

## Non-goals (v1)

- Integrasi Midtrans/Xendit terpisah
- WMS eksternal penuh
- Instant courier routing algorithm yang kompleks (cukup nearest + stock heuristic)
- Menghapus total kode Stripe dari kit (cukup dimatikan; driver Komerce jadi path aktif)

## Decisions locked

| Topik | Keputusan |
| --- | --- |
| Payment | Komerce Payment API (VA, QRIS, e-wallet) |
| Shipping | RajaOngkir API Shipping Cost + Shipping Delivery |
| Warehouse selection | Hybrid: auto-suggest + admin override |
| Multi-stock | Split shipment (1 order → N shipments) |
| Architecture | Adapter on Shopper |

## Current baseline

- Laravel 13 + Shopper Vue Starter Kit
- Checkout sudah punya path non-Stripe (`placeOrder`) dan session checkout
- Shopper punya model **Inventory** (lokasi stok) — map gudang ke sini
- `PAYMENT_STRIPE_ENABLED` default `false`
- Shipping driver UPS/FedEx/dll default `false`

## Domain model

### Warehouse ↔ Shopper Inventory

Tiap gudang OceanMall = 1 `shopper` **Inventory** (+ metadata origin RajaOngkir).

| Field | Keterangan |
| --- | --- |
| `name` | mis. Gudang Cirebon, Gudang Jakarta |
| `is_default` | fallback kalau skor sama |
| `street / city / postal / province` | alamat pickup |
| `rajaongkir_origin_id` | ID origin/district untuk cek ongkir |
| `is_enabled` | gudang aktif |

Stok per variant tetap lewat mekanisme inventory Shopper (`InventoryHistory` / stock per inventory).

### Order structure

```
Order
├── OrderLines (product/variant, qty, allocated_inventory_id?)
├── Payment (Komerce: VA/QRIS ref, status, webhook payload)
└── Shipments[]  (1 per gudang yang kirim)
    ├── ShipmentLines
    ├── carrier + service + cost
    ├── AWB / tracking
    └── status (pending → labeled → picked_up → in_transit → delivered)
```

Satu order, banyak shipment. Grand total = subtotal + sum(shipment.cost) + tax (jika ada).

## Allocation algorithm (suggest)

Saat checkout (setelah alamat tujuan valid):

1. Untuk tiap cart line, cari inventory yang:
   - enabled
   - punya stok cukup untuk qty line (atau sebagian → split line)
2. Skor kandidat gudang:
   - prioritas stok cukup di **satu** gudang untuk line tersebut
   - lalu **jarak/ongkir** ke destination (cek ongkir origin→dest untuk service default, atau heuristik kota/provinsi dulu untuk latency)
3. Hasil: `AllocationPlan` = list shipment draft `{ inventory_id, lines[], suggested_rates[] }`
4. Customer pilih **service per shipment** (atau “paket termurah/tercepat” global)
5. Total ongkir = jumlah biaya tiap shipment

### Override admin

Sebelum AWB dibuat:

- Admin bisa pindahkan line ke gudang lain (validasi stok)
- Boleh merge/split ulang shipment
- Recalculate ongkir wajib setelah override
- Audit log: siapa override, dari→ke, kapan

## Checkout flow

```
Cart → Alamat → Allocation suggest
     → Pilih kurir per shipment (RajaOngkir Cost API)
     → Pilih metode bayar (Komerce Payment)
     → Create Order (pending payment) + reserve stock
     → Create Komerce payment (VA/QRIS)
     → Webhook paid → mark order paid → enqueue CreateDeliveryJobs per shipment
     → AWB / pickup per gudang
```

### Payment rules

- Bayar **sekali** untuk seluruh order (termasuk semua ongkir split)
- Timeout pembayaran → cancel order + release reservation
- Webhook = source of truth status bayar
- Stripe tetap off; jangan expose di storefront

### Shipping rules

- Origin per shipment = alamat inventory/gudang
- Destination = alamat customer
- Setelah paid: panggil RajaOngkir **Shipping Delivery** per shipment (create order/AWB/pickup)
- Tracking disimpan per shipment; storefront tampilkan list paket

## Adapter layer

```
app/Services/Komerce/
  PaymentClient.php          # VA, QRIS, status, cancel
  ShippingCostClient.php     # cek ongkir
  ShippingDeliveryClient.php # AWB, pickup, tracking
  Webhooks/PaymentWebhookController.php
  Webhooks/DeliveryWebhookController.php (jika ada)

app/Actions/Warehouse/
  SuggestAllocation.php
  RecalculateShipmentRates.php
  OverrideAllocation.php

app/Actions/Checkout/
  FetchDeliveryRates.php     # diganti / dibungkus ke RajaOngkir
  FetchPaymentMethods.php    # Komerce channels
```

Config (`.env`):

```env
KOMERCE_API_KEY=
KOMERCE_PAYMENT_BASE_URL=https://api-sandbox.collaborator.komerce.id
RAJAONGKIR_COST_BASE_URL=
RAJAONGKIR_DELIVERY_BASE_URL=
KOMERCE_WEBHOOK_SECRET=
PAYMENT_STRIPE_ENABLED=false
```

## Admin UX

- CRUD gudang (map ke Inventory + origin ID)
- Stok per gudang (pakai UI Shopper inventory sedapat mungkin)
- Order detail: list shipments, AWB, tombol override allocation (sebelum labeled)
- Retry create AWB kalau API gagal

## Storefront UX

- Checkout tampilkan **beberapa paket** jika split (“Dikirim dari Jakarta”, “Dikirim dari Cirebon”)
- Ongkir per paket + total
- Halaman sukses / account order: status tiap paket

## Phased delivery (masih dalam arsitektur A)

Disarankan pecah implementasi, tanpa mengubah target model:

| Phase | Scope | Outcome |
| --- | --- | --- |
| **P1** | 1 gudang default + Cost API + Komerce Payment + webhook | Checkout live ID |
| **P2** | Multi inventory + SuggestAllocation + UI split di checkout | Multi-gudang auto |
| **P3** | Admin override + Delivery API (AWB/pickup) + tracking | Operasional penuh |

## Risks & mitigations

| Risk | Mitigation |
| --- | --- |
| Latency cek ongkir N gudang × M kurir | Cache rates singkat; batasi kurir aktif; parallel HTTP |
| Stok race | Reserve on order create; release on expire/cancel |
| Partial fulfill gagal AWB | Retry job; admin manual AWB; order tetap paid |
| Mapping wilayah RajaOngkir ≠ Shopper Country/Zone | Tabel mapping district/city; wizard setup gudang |
| Enterprise API key / doc gated | Simpan credential lokal; sandbox dulu |

## Success criteria

- [ ] Stripe tidak dipakai di flow produksi
- [ ] Customer bisa bayar VA/QRIS via Komerce
- [ ] Ongkir dihitung dari gudang yang benar (origin)
- [ ] Order bisa split ≥2 shipment otomatis saat stok pecah
- [ ] Admin bisa override gudang sebelum AWB
- [ ] Tracking per shipment terlihat di account order

## Open questions (boleh dijawab saat review)

1. Kurir aktif awal: mana saja (JNE, J&T, SiCepat, …)?
2. COD lewat RajaOngkir Delivery — perlu di v1, atau prepaid only dulu?
3. Override UI: Shopper `/cpanel` order detail Livewire panel only (no separate `/admin`).

## References

- [RajaOngkir docs](https://rajaongkir.com/docs)
- [Komerce Payment API](https://rajaongkir.com/docs/payment-api/getting_started/getting_started)
- Shopper `Inventory` model (`vendor/shopper/core/src/Models/Inventory.php`)
- Current checkout: `app/Http/Controllers/Shop/CheckoutController.php`
