# Official Contract Documentation: Komerce Shipping Delivery API V1

> **Source**: https://rajaongkir.com/docs/delivery-order-api  
> **Last Verified**: 12 Agustus 2026  
> **Production Base URL**: `https://api.collaborator.komerce.id/`  
> **Sandbox Base URL**: `https://api-sandbox.collaborator.komerce.id/`  
> **Authentication**: Header `x-api-key: <SHIPPING_DELIVERY_KEY>`

---

## 1. Search Destination

- **Method**: `GET`
- **Path**: `/tariff/api/v1/destination/search`
- **Authentication**: Header `x-api-key`

### Query Parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `keyword` | string | Yes | Search keyword (subdistrict/city name or zip code) |

### Success Response Example (200 OK)

```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Success Search Destination"
  },
  "data": [
    {
      "id": 31597,
      "label": "Gambir, Jakarta Pusat, DKI Jakarta",
      "subdistrict_name": "Gambir",
      "district_name": "Gambir",
      "city_name": "Jakarta Pusat",
      "zip_code": "10110"
    }
  ]
}
```

---

## 2. Calculate Delivery Tariff

- **Method**: `GET`
- **Path**: `/tariff/api/v1/calculate`
- **Authentication**: Header `x-api-key`

### Query Parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `shipper_destination_id` | int | Yes | Origin location ID |
| `receiver_destination_id` | int | Yes | Receiver location ID |
| `origin_pin_point` | string | Yes | Latitude,longitude string (e.g. `-7.2798,109.3511`) |
| `destination_pin_point` | string | Yes | Latitude,longitude string (e.g. `-7.3058,109.3681`) |
| `weight` | float | Yes | Package weight in **kilograms** (kg, e.g. `1.25`) |
| `item_value` | int | Yes | Package goods total value in IDR |
| `cod` | string | No | `yes` or `no` boolean flag |

### Success Response Example (200 OK)

```json
{
  "meta": {
    "message": "Success Calculate Shipping",
    "code": 200,
    "status": "success"
  },
  "data": {
    "calculate_reguler": [
      {
        "shipping_name": "JNE",
        "service_name": "REG",
        "weight": 1.25,
        "is_cod": 0,
        "shipping_cost": 18000,
        "shipping_cashback": 4500,
        "shipping_cost_net": 13500,
        "service_fee": 0,
        "grandtotal": 118000,
        "net_income": 104500,
        "etd": "2-3 Hari"
      }
    ],
    "calculate_cargo": [],
    "calculate_instant": []
  }
}
```

---

## 3. Store Order

- **Method**: `POST`
- **Path**: `/order/api/v1/orders/store`
- **Authentication**: Header `x-api-key`
- **Content-Type**: `application/json`

### Request Body Example

```json
{
  "order_date": "2026-08-12",
  "brand_name": "OceanMall Store",
  "shipper_name": "Gudang Utama Cirebon",
  "shipper_phone": "081234567890",
  "shipper_destination_id": 501,
  "shipper_address": "Jl. Gudang No. 10, Cirebon, 45111",
  "shipper_email": "gudang@oceanmall.test",
  "receiver_name": "Budi Santoso",
  "receiver_phone": "081234567890",
  "receiver_destination_id": 152,
  "receiver_address": "Jl. Merdeka No. 1, Gambir, Jakarta Pusat, 10110",
  "receiver_email": "budi@example.test",
  "shipping": "JNE",
  "shipping_type": "REG",
  "shipping_cost": 18000,
  "shipping_cashback": 4500,
  "payment_method": "BANK TRANSFER",
  "service_fee": 0,
  "additional_cost": 0,
  "grand_total": 118000,
  "cod_value": 0,
  "insurance_value": 0,
  "order_details": [
    {
      "product_name": "Kopi Cirebon",
      "product_variant_name": "KOPI-CIREBON-500",
      "product_price": 50000,
      "product_weight": 500,
      "product_width": 12,
      "product_height": 8,
      "product_length": 20,
      "qty": 2,
      "subtotal": 100000
    }
  ]
}
```

### Success Response Example (201 Created)

```json
{
  "meta": {
    "message": "Success Create New Order",
    "code": 201,
    "status": "success"
  },
  "data": {
    "order_id": 9999,
    "order_no": "RO-ORDER-001"
  }
}
```

---

## 4. Detail Order

- **Method**: `GET`
- **Path**: `/order/api/v1/orders/detail`
- **Authentication**: Header `x-api-key`

### Query Parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `order_no` | string | Yes | Order number returned from Store Order |

---

## 5. Cancel Order

- **Method**: `PUT`
- **Path**: `/order/api/v1/orders/cancel`
- **Authentication**: Header `x-api-key`
- **Content-Type**: `application/json`

### Request Body Example

```json
{
  "order_no": "RO-ORDER-001"
}
```

---

## 6. Request Pickup

- **Method**: `POST`
- **Path**: `/order/api/v1/pickup/request`
- **Authentication**: Header `x-api-key`
- **Content-Type**: `application/json`

### Request Body Example

```json
{
  "pickup_date": "2026-08-13",
  "pickup_time": "10:00:00",
  "pickup_vehicle": "Motor",
  "orders": [
    { "order_no": "RO-ORDER-001" }
  ]
}
```

### Success Response Example (201 Created)

```json
{
  "meta": {
    "message": "Success Request Pickup",
    "code": 201,
    "status": "success"
  },
  "data": [
    {
      "status": "success",
      "order_no": "RO-ORDER-001",
      "awb": "JNE123456789"
    }
  ]
}
```

---

## 7. Print Shipping Label (Resi)

- **Method**: `POST`
- **Path**: `/order/api/v1/orders/print-label`
- **Authentication**: Header `x-api-key`

### Query Parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `order_no` | string | Yes | Comma-separated list of order numbers |
| `page` | string | Yes | Label page format (`page_1`, `page_2`, `page_4`, `page_5`, `page_6`) |

### Success Response Example (200 OK)

```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Generate Print Label Success"
  },
  "data": {
    "path": "https://api-sandbox.collaborator.komerce.id/storage/label/RO-ORDER-001.pdf"
  }
}
```

---

## 8. Track Airway Bill (History Airway Bill)

- **Method**: `GET`
- **Path**: `/order/api/v1/orders/history-airway-bill`
- **Authentication**: Header `x-api-key`

### Query Parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `shipping` | string | Yes | Courier code (e.g. `JNE`) |
| `airway_bill` | string | Yes | AWB / Resi tracking number |

### Success Response Example (200 OK)

```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Success Fetch Airway Bill History"
  },
  "data": {
    "airway_bill": "JNE123456789",
    "last_status": "DELIVERED",
    "history": [
      {
        "desc": "Package received at origin office",
        "date": "2026-08-01 09:00:00",
        "code": "100",
        "status": "ON_PROCESS"
      },
      {
        "desc": "Package delivered to recipient Budi Santoso",
        "date": "2026-08-03 14:30:00",
        "code": "200",
        "status": "DELIVERED"
      }
    ]
  }
}
```

---

## 9. Delivery Status Webhook

- **Method**: `PUT` on the endpoint table at https://www.rajaongkir.com/docs/delivery-order-api/getting_started/base-url; OceanMall accepts `POST` and `PUT` on the merchant URL.
- **Payload Schema**:

```json
{
  "order_no": "RO-ORDER-001",
  "cnote": "JNE123456789",
  "status": "DELIVERED"
}
```

- **Response Required**: Merchant HTTP `200 OK` JSON acknowledgement:

```json
{
  "status": "handled"
}
```
