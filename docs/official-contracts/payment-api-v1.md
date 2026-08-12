# Official Contract Documentation: Komerce Payment API V1

> **Source**: https://rajaongkir.com/docs/payment-api  
> **Last Verified**: 12 Agustus 2026  
> **Production Base URL**: `https://api.collaborator.komerce.id/user`  
> **Sandbox Base URL**: `https://api-sandbox.collaborator.komerce.id/user`  
> **Payment Page URLs**: `https://pay.komerce.id/{token}` (Prod), `https://pay-sandbox.komerce.id/{token}` (Sandbox)  
> **Authentication**: Header `x-api-key: <PAYMENT_KEY>`

---

## 1. Get Available Payment Methods

- **Method**: `GET`
- **Path**: `/api/v1/user/methods`
- **Authentication**: Header `x-api-key`

### Success Response Example (200 OK)

```json
{
  "meta": {
    "message": "success get payment methods",
    "code": 200,
    "status": "success"
  },
  "data": [
    {
      "payment_type": "va",
      "display_name": "Bank Central Asia",
      "bank_code": "BCA",
      "logo_url": "https://storage.googleapis.com/komerce/assets/logo/bca.png",
      "min_amount": 10000,
      "max_amount": 999999999999,
      "currency": "IDR"
    },
    {
      "payment_type": "qris",
      "display_name": "QRIS",
      "bank_code": "",
      "logo_url": "https://storage.googleapis.com/komerce/assets/logo/qris.png",
      "min_amount": 10000,
      "max_amount": 10000000,
      "currency": "IDR"
    }
  ]
}
```

---

## 2. Create Payment (Virtual Account / QRIS)

- **Method**: `POST`
- **Path**: `/api/v1/user/payment/create`
- **Authentication**: Header `x-api-key`
- **Content-Type**: `application/json`

### Virtual Account Request Example

```json
{
  "order_id": "ORD-2026-001",
  "payment_type": "bank_transfer",
  "channel_code": "BCA",
  "amount": 150000,
  "customer": {
    "name": "Budi Santoso",
    "email": "budi@example.test",
    "phone": "081234567890"
  },
  "items": [
    {
      "name": "Kopi Cirebon 500g",
      "quantity": 1,
      "price": 150000
    }
  ],
  "expiry_duration": 86400,
  "callback_url": "https://oceanmall.test/checkout/callback",
  "callback_API_KEY": "your_webhook_secret_key"
}
```

### QRIS Payment Request Example

```json
{
  "order_id": "ORD-2026-002",
  "payment_type": "qris",
  "amount": 200000,
  "customer": {
    "name": "Sari Wulandari",
    "email": "sari@example.test",
    "phone": "081298765432"
  },
  "items": [
    {
      "name": "Batik Shirt",
      "quantity": 1,
      "price": 200000
    }
  ],
  "callback_API_KEY": "your_webhook_secret_key"
}
```

### Success Response Example (Virtual Account)

```json
{
  "meta": {
    "message": "success create payment",
    "code": 200,
    "status": "success"
  },
  "data": {
    "payment_id": "KPAY-5292/KM/2026",
    "payment_url": "https://pay-sandbox.komerce.id/token123",
    "va_number": "381659999574893",
    "virtual_account_number": "381659999574893",
    "qr_string": "",
    "bank_code": "BCA",
    "amount": 150000,
    "status": "PENDING",
    "expired_at": "2026-08-13T10:00:00.000Z"
  }
}
```

---

## 3. Get Payment Status

- **Method**: `GET`
- **Path**: `/api/v1/user/payment/status/{payment_id}`
- **Authentication**: Header `x-api-key`
- **Throttle Guidance**: Maximum 1 request per 3 seconds per payment ID.

### Success Response Example (200 OK)

```json
{
  "meta": {
    "message": "success get payment status",
    "code": 200,
    "status": "success"
  },
  "data": {
    "payment_id": "KPAY-5292/KM/2026",
    "order_id": "ORD-2026-001",
    "payment_type": "bank_transfer",
    "channel_code": "BCA",
    "amount": 150000,
    "status": "PAID",
    "paid_at": "2026-08-12T10:15:30.000Z"
  }
}
```

---

## 4. Cancel Payment

- **Method**: `POST`
- **Path**: `/api/v1/user/payment/cancel`
- **Authentication**: Header `x-api-key`
- **Content-Type**: `application/json`

### Request Body Example

```json
{
  "payment_id": "KPAY-5292/KM/2026",
  "reason": "Customer cancelled order"
}
```

---

## 5. Webhook Callback Handling

- **Method**: `POST` (incoming request from Komerce Payment API to merchant callback URL)
- **Signature Header**: `X-Callback-Api-Key`
- **Signature Algorithm**: Hex-encoded HMAC-SHA256 over raw request body using secret key `callback_API_KEY`:

```php
$signature = hash_hmac('sha256', $rawRequestBody, $callbackSecretKey);
```

### Callback Payload Example

```json
{
  "payment_id": "KPAY-5292/KM/2026",
  "order_id": "ORD-2026-001",
  "status": "PAID",
  "amount": 150000,
  "paid_at": "2026-08-12T10:15:30.000Z"
}
```

### Success Response Required (200 OK)

```json
{
  "status": "handled"
}
```
