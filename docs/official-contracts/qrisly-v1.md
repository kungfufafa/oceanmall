# Official Contract Documentation: QRISLY API V1

> **Source**: https://www.rajaongkir.com/docs/qrisly  
> **Last Verified**: 12 Agustus 2026  
> **Production Base URL**: `https://api.collaborator.komerce.id/user`  
> **Sandbox Base URL**: `https://api-sandbox.collaborator.komerce.id/user`  
> **Authentication**: Header `X-API-Key: <QRISLY_KEY>`

---

## 1. Upload Master QRIS

- **Method**: `POST`
- **Path**: `/api/v1/qrisly/upload-qris`
- **Authentication**: Header `X-API-Key`
- **Content-Type**: `multipart/form-data`

### Request Parameters (`multipart/form-data`)

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | string | Yes | Merchant / QRIS identity name (max 100 chars) |
| `qris_image` | file | Yes | QRIS image file (PNG/JPG, max 5 MB) |

### Success Response Example (200 OK)

```json
{
  "success": true,
  "message": "QRIS successfully uploaded and validated",
  "data": {
    "qris_id": "9d6c9f9e-8c33-4f42-8b1f-0e6a3e2e7d10",
    "provider": "DANA",
    "name": "OceanMall Store",
    "merchant_name": "OceanMall Store",
    "created_at": "2026-08-12 10:00:00"
  }
}
```

---

## 2. Generate Dynamic QRIS

- **Method**: `POST`
- **Path**: `/api/v1/qrisly/generate-qris`
- **Authentication**: Header `X-API-Key`
- **Content-Type**: `application/json`

### Request Body Example

```json
{
  "qris_id": "9d6c9f9e-8c33-4f42-8b1f-0e6a3e2e7d10",
  "amount": 150000,
  "output_type": "string",
  "unique_amount": true
}
```

### Success Response Example (200 OK)

```json
{
  "success": true,
  "message": "QRIS successfully generated",
  "data": {
    "history_id": "QRIS-HIST-12345",
    "qris_string": "00020101021226640013ID.CO.QRIS.WWW...",
    "original_amount": 150000,
    "final_amount": 150123,
    "payment_status": "unpaid",
    "expiry_time": "2026-08-12T10:15:00.000Z"
  }
}
```

---

## 3. Get QRISLY Payment Status

- **Method**: `GET`
- **Path**: `/api/v1/qrisly/payment-status/{history_id}`
- **Authentication**: Header `X-API-Key`

### Success Response Example (200 OK)

```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Success fetch payment status"
  },
  "data": {
    "history_id": "QRIS-HIST-12345",
    "qris_id": "9d6c9f9e-8c33-4f42-8b1f-0e6a3e2e7d10",
    "original_amount": 150000,
    "final_amount": 150123,
    "status": "paid",
    "paid_at": "2026-08-12T10:05:00.000Z"
  }
}
```

---

## 4. QRISLY Webhook Event

- **Method**: `POST` (incoming event to merchant registered webhook URL)
- **Event Types**: `payment.success`, `payment.expired`

### Webhook Payload Example

```json
{
  "event": "payment.success",
  "data": {
    "history_id": "QRIS-HIST-12345",
    "qris_id": "9d6c9f9e-8c33-4f42-8b1f-0e6a3e2e7d10",
    "original_amount": 150000,
    "final_amount": 150123,
    "status": "paid",
    "paid_at": "2026-08-12T10:05:00.000Z"
  }
}
```

### Success Response Required (200 OK)

```json
{
  "success": true,
  "message": "Webhook received successfully"
}
```
