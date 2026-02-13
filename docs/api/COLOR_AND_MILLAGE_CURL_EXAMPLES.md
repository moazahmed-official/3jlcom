# Color and Millage Fields - Curl Examples

This document provides curl examples for creating and updating ads with the new `color` and `millage` fields.

## Normal Ads

### Create Normal Ad with Color and Millage

```bash
curl -X POST 'http://localhost:8000/api/v1/normal-ads' \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Toyota Camry 2020",
    "description": "Excellent condition, well maintained vehicle",
    "category_id": 1,
    "city_id": 1,
    "country_id": 1,
    "brand_id": 1,
    "model_id": 5,
    "year": 2020,
    "color": "Red",
    "millage": 25000.50,
    "price_cash": 75000,
    "contact_phone": "0500000000",
    "whatsapp_number": "0500000000",
    "media_ids": [1, 2, 3]
  }'
```

**Response (201 Created):**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "type": "normal",
    "title": "Toyota Camry 2020",
    "description": "Excellent condition, well maintained vehicle",
    "year": 2020,
    "color": "Red",
    "millage": 25000.50,
    "price_cash": 75000,
    "brand_id": 1,
    "model_id": 5,
    "category_id": 1,
    "created_at": "2026-02-13T10:00:00Z",
    "updated_at": "2026-02-13T10:00:00Z"
  }
}
```

### Update Normal Ad with Color and Millage

```bash
curl -X PUT 'http://localhost:8000/api/v1/normal-ads/1' \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "color": "Blue",
    "millage": 26000
  }'
```

### Get Normal Ad Details (includes Color and Millage)

```bash
curl -X GET 'http://localhost:8000/api/v1/normal-ads/1' \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "type": "normal",
    "title": "Toyota Camry 2020",
    "year": 2020,
    "color": "Blue",
    "millage": 26000.00,
    "price_cash": 75000,
    "status": "published",
    "created_at": "2026-02-13T10:00:00Z",
    "updated_at": "2026-02-13T14:00:00Z"
  }
}
```

### List Normal Ads (includes Color and Millage)

```bash
curl -X GET 'http://localhost:8000/api/v1/normal-ads?limit=10' \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## Unique Ads

### Create Unique Ad with Color and Millage

```bash
curl -X POST 'http://localhost:8000/api/v1/unique-ads' \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "BMW 7 Series 2022",
    "description": "Luxury sedan, pristine condition",
    "category_id": 1,
    "city_id": 1,
    "country_id": 1,
    "brand_id": 2,
    "model_id": 10,
    "year": 2022,
    "color": "Black",
    "millage": 12000.75,
    "banner_color": "#000000",
    "contact_phone": "0500000001",
    "media_ids": [4, 5, 6]
  }'
```

### Update Unique Ad with Color and Millage

```bash
curl -X PUT 'http://localhost:8000/api/v1/unique-ads/2' \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "color": "Silver",
    "millage": 13000
  }'
```

---

## Auction Ads

### Create Auction Ad with Color and Millage

```bash
curl -X POST 'http://localhost:8000/api/v1/auction-ads' \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Honda Civic 2019",
    "description": "Well-maintained, ready to bid",
    "category_id": 1,
    "city_id": 1,
    "country_id": 1,
    "brand_id": 3,
    "model_id": 15,
    "year": 2019,
    "color": "White",
    "millage": 45000.00,
    "start_price": 30000,
    "reserve_price": 35000,
    "start_time": "2026-02-15T10:00:00Z",
    "end_time": "2026-02-22T10:00:00Z",
    "minimum_bid_increment": 500,
    "auto_close": true,
    "is_last_price_visible": true,
    "media_ids": [7, 8, 9]
  }'
```

### Update Auction Ad with Color and Millage

```bash
curl -X PUT 'http://localhost:8000/api/v1/auction-ads/3' \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "color": "Gray",
    "millage": 46000,
    "start_price": 31000
  }'
```

---

## Caishha Ads

### Create Caishha Ad with Color and Millage

```bash
curl -X POST 'http://localhost:8000/api/v1/caishha-ads' \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Nissan Altima 2021",
    "description": "Excellent condition, dealer ready",
    "category_id": 1,
    "city_id": 1,
    "country_id": 1,
    "brand_id": 4,
    "model_id": 20,
    "year": 2021,
    "color": "Green",
    "millage": 18000.50,
    "offers_window_period": 86400,
    "sellers_visibility_period": 604800,
    "period_days": 30,
    "contact_phone": "0500000002",
    "media_ids": [10, 11, 12]
  }'
```

### Update Caishha Ad with Color and Millage

```bash
curl -X PUT 'http://localhost:8000/api/v1/caishha-ads/4' \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "color": "Yellow",
    "millage": 19000
  }'
```

---

## Field Specifications

### Color Field
- **Type:** String
- **Max Length:** 100 characters
- **Nullable:** Yes
- **Validation Examples:**
  - "Red"
  - "Black"
  - "Silver Metallic"
  - "Pearl White"
  - "Matte Black"

### Millage Field
- **Type:** Numeric (decimal)
- **Min Value:** 0
- **Max Value:** 9,999,999
- **Decimal Places:** 2
- **Unit:** Kilometers
- **Nullable:** Yes
- **Validation Examples:**
  - `25000.50` ✅
  - `150000` ✅
  - `0` ✅ (brand new vehicles)
  - `-1000` ❌ (negative not allowed)
  - `10000000` ❌ (exceeds max value)

---

## Response Format

All ad responses (listing, details, create, update) will include these fields:

```json
{
  "id": 1,
  "type": "normal",
  "title": "Vehicle Title",
  "year": 2020,
  "color": "Red",
  "millage": 25000.50,
  "status": "published",
  "created_at": "2026-02-13T10:00:00Z",
  "updated_at": "2026-02-13T10:00:00Z"
}
```

---

## Filtering and Search

The color and millage fields are also available for filtering in list endpoints:

### Filter by Color
```bash
curl -X GET 'http://localhost:8000/api/v1/normal-ads?color=Red' \
  -H "Accept: application/json"
```

### Filter by Millage Range
```bash
curl -X GET 'http://localhost:8000/api/v1/normal-ads?min_millage=10000&max_millage=50000' \
  -H "Accept: application/json"
```

---

## Error Handling

### Invalid Color (exceeds max length)
```bash
curl -X POST 'http://localhost:8000/api/v1/normal-ads' \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "...",
    "color": "A very very very very very long color name that exceeds 100 characters maximum length allowed for color field..."
  }'
```

**Response (422 Unprocessable Entity):**
```json
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "color": ["The color field must not be greater than 100 characters."]
  }
}
```

### Invalid Millage (negative value)
```bash
curl -X POST 'http://localhost:8000/api/v1/normal-ads' \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "...",
    "millage": -5000
  }'
```

**Response (422 Unprocessable Entity):**
```json
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "millage": ["The millage field must be at least 0."]
  }
}
```

---

## Notes

- Both fields are **optional** for all ad types
- Color and millage are **included in all listing and detail endpoints**
- Color and millage values are **returned in update operations**
- Color and millage can be **updated independently** from other fields
- Color and millage are **included in responses after creation** (201 Created)
