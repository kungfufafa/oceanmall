# Indeks Dokumentasi Resmi Kontrak Komerce / RajaOngkir

Dokumen di folder `docs/official-contracts/` ini merupakan salinan dan ekstrak lengkap dari dokumentasi resmi provider **Komerce & RajaOngkir (V2 / Collaborator)** per **12 Agustus 2026**.

Semua spesifikasi ini dijadikan rujukan kontrak teknis (*Source of Truth*) tanpa mengarang endpoint, header, parameter, atau respons.

---

## Daftar Kontrak Layanan Resmi

1. **[Shipping Cost API V2](file:///Users/apriansyahrs/Documents/Code/oceanmall/docs/official-contracts/shipping-cost-v2.md)**
   - Base URL: `https://rajaongkir.komerce.id/api/v1/`
   - Key: Header `key: <SHIPPING_COST_KEY>`
   - Scope: Domestic Destination Search (`GET`), International Destination Search (`GET`), Calculate Domestic Cost (`POST`), Calculate International Cost (`POST`), Track Waybill (`POST /track/waybill`).

2. **[Shipping Delivery API V1](file:///Users/apriansyahrs/Documents/Code/oceanmall/docs/official-contracts/shipping-delivery-v1.md)**
   - Base URL: `https://api.collaborator.komerce.id/` (Production) / `https://api-sandbox.collaborator.komerce.id/` (Sandbox)
   - Key: Header `x-api-key: <SHIPPING_DELIVERY_KEY>`
   - Scope: Destination Search (`GET`), Calculate Delivery Tariff (`GET`), Store Order (`POST`), Detail Order (`GET`), Request Pickup (`POST`), Print Label (`POST`), History Airway Bill / Tracking (`GET`), Webhook (`POST`).

3. **[Payment API V1](file:///Users/apriansyahrs/Documents/Code/oceanmall/docs/official-contracts/payment-api-v1.md)**
   - Base URL: `https://api.collaborator.komerce.id/user` (Production) / `https://api-sandbox.collaborator.komerce.id/user` (Sandbox)
   - Key: Header `x-api-key: <PAYMENT_KEY>`
   - Scope: Payment Methods (`GET`), Create Payment VA & QRIS (`POST`), Payment Status (`GET`), Cancel Payment (`POST`), Webhook HMAC Callback (`X-Callback-Api-Key`).

4. **[QRISLY API V1](file:///Users/apriansyahrs/Documents/Code/oceanmall/docs/official-contracts/qrisly-v1.md)**
   - Base URL: `https://api.collaborator.komerce.id/user`
   - Key: Header `X-API-Key: <QRISLY_KEY>`
   - Scope: Master QRIS Upload (`POST`), Dynamic QRIS Generate (`POST`), Payment Status (`GET`), Webhook (`payment.success` / `payment.expired`).

---

## Alur Rujukan & Goal Loop

Setiap pengujian unit dan fitur di OceanMall mencakup kontrak endpoint ini. Dokumen utama kendali rujukan integrasi tersedia di:
👉 **[komerce-rajaongkir-goal-loop.md](file:///Users/apriansyahrs/Documents/Code/oceanmall/docs/chain-of-truth/komerce-rajaongkir-goal-loop.md)**
