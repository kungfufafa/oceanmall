# Official Contract Documentation: Shipping Cost API V2

> **Source**: https://rajaongkir.komerce.id/docs/shipping-cost  
> **Last Verified**: 12 Agustus 2026  
> **Base URL**: `https://rajaongkir.komerce.id/api/v1/`  
> **Authentication**: Header `key: <SHIPPING_COST_KEY>`  
> **Environment**: Always live production (no sandbox endpoint available).

---

## 1. Search Domestic Destination

- **Method**: `GET`
- **Path**: `/destination/domestic-destination`
- **Authentication**: Header `key: <SHIPPING_COST_KEY>`

### Query Parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `search` | string | Yes | Address search keyword (min. subdistrict level) |
| `limit` | int | No | Maximum number of results to return |
| `offset` | int | No | Offset for pagination |

### Success Response Example (200 OK)

```json
{
  "meta": {
    "message": "Success Search Domestic Destination",
    "code": 200,
    "status": "success"
  },
  "data": [
    {
      "id": 152,
      "label": "Gambir, Jakarta Pusat, DKI Jakarta",
      "province_name": "DKI Jakarta",
      "city_name": "Jakarta Pusat",
      "district_name": "Gambir",
      "subdistrict_name": "Gambir",
      "zip_code": "10110"
    }
  ]
}
```

---

## 2. Calculate Domestic Shipping Cost

- **Method**: `POST`
- **Path**: `/calculate/domestic-cost`
- **Authentication**: Header `key: <SHIPPING_COST_KEY>`
- **Content-Type**: `application/x-www-form-urlencoded`

### Request Body (`application/x-www-form-urlencoded`)

| Parameter | Type | Required | Description |
|---|---|---|---|
| `origin` | int | Yes | Origin location ID from Search Destination |
| `destination` | int | Yes | Destination location ID from Search Destination |
| `weight` | int | Yes | Package weight in **grams** |
| `courier` | string | Yes | Colon-separated courier codes (e.g. `jne:sicepat:jnt`) |
| `price` | string | No | Optional sorting filter (`lowest` or `highest`) |

### cURL Request Example

```bash
curl --location 'https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost' \
--header 'key: YOUR_SHIPPING_COST_KEY' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--data-urlencode 'origin=501' \
--data-urlencode 'destination=152' \
--data-urlencode 'weight=1000' \
--data-urlencode 'courier=jne:sicepat:jnt'
```

### Success Response Example (200 OK)

```json
{
  "meta": {
    "message": "Success Calculate Domestic Shipping cost",
    "code": 200,
    "status": "success"
  },
  "data": [
    {
      "name": "JNE Express",
      "code": "jne",
      "service": "REG",
      "description": "Layanan Reguler",
      "cost": 18000,
      "etd": "2-3"
    },
    {
      "name": "SiCepat Ekspres",
      "code": "sicepat",
      "service": "SIUNT",
      "description": "SiUntung",
      "cost": 17000,
      "etd": "1-2"
    }
  ]
}
```

---

## 3. Track Waybill

- **Method**: `POST`
- **Path**: `/track/waybill`
- **Authentication**: Header `key: <SHIPPING_COST_KEY>`

### Query Parameters / Form Fields

| Parameter | Type | Required | Description |
|---|---|---|---|
| `awb` | string | Yes | Airway Bill / Resi tracking number |
| `courier` | string | Yes | Courier code (e.g. `jne`, `sicepat`) |
| `last_phone_number` | string | Conditional | Last 4 digits of recipient phone (for couriers like Shopee Xpress) |

### Success Response Example (200 OK)

```json
{
  "meta": {
    "message": "Success Track Waybill",
    "code": 200,
    "status": "success"
  },
  "data": {
    "delivered": true,
    "summary": {
      "courier_code": "jne",
      "courier_name": "JNE Express",
      "waybill_number": "JNE123456789",
      "service_code": "REG",
      "waybill_date": "2026-08-01",
      "shipper_name": "OceanMall Store",
      "receiver_name": "Budi Santoso",
      "origin": "Cirebon",
      "destination": "Jakarta"
    },
    "details": {
      "waybill_number": "JNE123456789",
      "waybill_date": "2026-08-01",
      "status": "DELIVERED"
    },
    "delivery_status": {
      "status": "DELIVERED",
      "pod_date": "2026-08-03",
      "pod_time": "14:30",
      "pod_receiver": "Budi Santoso"
    },
    "manifest": [
      {
        "manifest_code": "100",
        "manifest_description": "Shipment received at origin",
        "manifest_date": "2026-08-01",
        "manifest_time": "10:00",
        "city_name": "Cirebon"
      },
      {
        "manifest_code": "200",
        "manifest_description": "Package delivered to recipient",
        "manifest_date": "2026-08-03",
        "manifest_time": "14:30",
        "city_name": "Jakarta"
      }
    ]
  }
}
```
