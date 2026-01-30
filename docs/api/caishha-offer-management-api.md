# Caishha Offer Management API - New Endpoints

## Overview
Three new endpoints for complete Caishha offer lifecycle management: view, update, and delete offers.

---

## 1. Get Specific Offer Details

**Endpoint:** `GET /api/v1/caishha-offers/{offer}`

**Description:** Retrieve detailed information about a specific Caishha offer.

**Authorization:** Bearer token required. Accessible by:
- Offer owner (dealer/seller who submitted the offer)
- Ad owner (who can view offers on their ad)
- Admin

### Request Example

```bash
curl -X GET 'http://localhost:8000/api/v1/caishha-offers/123' \
  --header 'User-Agent: yaak' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 19|USER_TOKEN_EXAMPLE'
```

### Response Example (Success - 200)

```json
{
  "status": "success",
  "data": {
    "id": 123,
    "ad_id": 456,
    "user_id": 789,
    "price": 25000,
    "comment": "I can pay cash immediately",
    "status": "pending",
    "is_visible_to_seller": false,
    "created_at": "2026-01-29T10:30:00Z",
    "updated_at": "2026-01-29T10:30:00Z",
    "user": {
      "id": 789,
      "name": "Ahmed Dealer",
      "phone": "+962791234567"
    },
    "ad": {
      "id": 456,
      "title": "Toyota Corolla 2018",
      "type": "caishha",
      "brand": {
        "id": 5,
        "name": "Toyota"
      },
      "model": {
        "id": 12,
        "name": "Corolla"
      }
    }
  }
}
```

### Error Responses

**404 - Offer Not Found**
```json
{
  "status": "error",
  "code": 404,
  "message": "Offer not found",
  "errors": {
    "offer": ["The specified offer does not exist"]
  }
}
```

**403 - Unauthorized**
```json
{
  "status": "error",
  "code": 403,
  "message": "Unauthorized",
  "errors": {
    "authorization": ["You do not have permission to view this offer"]
  }
}
```

---

## 2. Update Offer (Price/Comment)

**Endpoint:** `PUT /api/v1/caishha-offers/{offer}`

**Description:** Update the price and/or comment of a pending offer. Only the offer owner can update their offer, and only pending offers can be updated.

**Authorization:** Bearer token required. Only accessible by the offer owner.

**Request Body:**
- `price` (optional, numeric): New offer price (min: 1, max: 999,999,999)
- `comment` (optional, string): Updated comment (max: 500 characters)

### Request Example - Update Price Only

```bash
curl -X PUT 'http://localhost:8000/api/v1/caishha-offers/123' \
  --header 'User-Agent: yaak' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 19|DEALER_TOKEN_EXAMPLE' \
  --header 'Content-Type: application/json' \
  -d '{
    "price": 27000
  }'
```

### Request Example - Update Both Price and Comment

```bash
curl -X PUT 'http://localhost:8000/api/v1/caishha-offers/123' \
  --header 'User-Agent: yaak' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 19|DEALER_TOKEN_EXAMPLE' \
  --header 'Content-Type: application/json' \
  -d '{
    "price": 28500,
    "comment": "Increased offer - can pay immediately"
  }'
```

### Request Example - Update Comment Only

```bash
curl -X PUT 'http://localhost:8000/api/v1/caishha-offers/123' \
  --header 'User-Agent: yaak' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 19|DEALER_TOKEN_EXAMPLE' \
  --header 'Content-Type: application/json' \
  -d '{
    "comment": "Can provide trade-in option"
  }'
```

### Response Example (Success - 200)

```json
{
  "status": "success",
  "message": "Offer updated successfully",
  "data": {
    "id": 123,
    "ad_id": 456,
    "user_id": 789,
    "price": 28500,
    "comment": "Increased offer - can pay immediately",
    "status": "pending",
    "is_visible_to_seller": false,
    "created_at": "2026-01-29T10:30:00Z",
    "updated_at": "2026-01-29T14:22:00Z",
    "user": {
      "id": 789,
      "name": "Ahmed Dealer"
    },
    "ad": {
      "id": 456,
      "title": "Toyota Corolla 2018"
    }
  }
}
```

### Error Responses

**422 - Validation Error**
```json
{
  "status": "error",
  "code": 422,
  "message": "Cannot update offer",
  "errors": {
    "price": ["The offer price must be at least 1."],
    "comment": ["The comment cannot exceed 500 characters."]
  }
}
```

**422 - Cannot Update Non-Pending Offer**
```json
{
  "status": "error",
  "code": 422,
  "message": "Cannot update offer",
  "errors": {
    "offer": ["Only pending offers can be updated."]
  }
}
```

**403 - Unauthorized**
```json
{
  "status": "error",
  "code": 403,
  "message": "Unauthorized",
  "errors": {
    "authorization": ["You can only update your own offers"]
  }
}
```

**404 - Offer Not Found**
```json
{
  "status": "error",
  "code": 404,
  "message": "Offer not found",
  "errors": {
    "offer": ["The specified offer does not exist"]
  }
}
```

---

## 3. Delete/Withdraw Offer

**Endpoint:** `DELETE /api/v1/caishha-offers/{offer}`

**Description:** Delete or withdraw a pending offer. Only pending offers can be deleted. The offer owner or admin can delete an offer.

**Authorization:** Bearer token required. Accessible by:
- Offer owner (dealer/seller who submitted the offer)
- Admin

### Request Example

```bash
curl -X DELETE 'http://localhost:8000/api/v1/caishha-offers/123' \
  --header 'User-Agent: yaak' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer 19|DEALER_TOKEN_EXAMPLE'
```

### Response Example (Success - 200)

```json
{
  "status": "success",
  "message": "Offer withdrawn successfully"
}
```

### Error Responses

**422 - Cannot Delete Non-Pending Offer**
```json
{
  "status": "error",
  "code": 422,
  "message": "Cannot delete offer",
  "errors": {
    "offer": ["Only pending offers can be deleted"]
  }
}
```

**403 - Unauthorized**
```json
{
  "status": "error",
  "code": 403,
  "message": "Unauthorized",
  "errors": {
    "authorization": ["You can only delete your own offers"]
  }
}
```

**404 - Offer Not Found**
```json
{
  "status": "error",
  "code": 404,
  "message": "Offer not found",
  "errors": {
    "offer": ["The specified offer does not exist"]
  }
}
```

---

## Use Cases

### Use Case 1: Dealer Increases Their Offer
A dealer submits an offer of 25,000 but later decides to increase it to 28,000 to be more competitive.

```bash
# Step 1: View current offer
curl -X GET 'http://localhost:8000/api/v1/caishha-offers/123' \
  --header 'Authorization: Bearer DEALER_TOKEN'

# Step 2: Update with higher price
curl -X PUT 'http://localhost:8000/api/v1/caishha-offers/123' \
  --header 'Authorization: Bearer DEALER_TOKEN' \
  --header 'Content-Type: application/json' \
  -d '{"price": 28000}'
```

### Use Case 2: Seller Changes Their Mind
A seller decides to withdraw their offer before the ad owner accepts it.

```bash
curl -X DELETE 'http://localhost:8000/api/v1/caishha-offers/123' \
  --header 'Authorization: Bearer SELLER_TOKEN'
```

### Use Case 3: Add Additional Information
A dealer wants to add more details about their offer (e.g., payment method, trade-in option).

```bash
curl -X PUT 'http://localhost:8000/api/v1/caishha-offers/123' \
  --header 'Authorization: Bearer DEALER_TOKEN' \
  --header 'Content-Type: application/json' \
  -d '{"comment": "Cash payment available + trade-in option for old car"}'
```

---

## Business Rules

1. **Update Restrictions:**
   - Only pending offers can be updated
   - Only the offer owner can update their offer
   - Price must be between 1 and 999,999,999
   - Comment cannot exceed 500 characters

2. **Delete Restrictions:**
   - Only pending offers can be deleted
   - Once deleted, the offer cannot be recovered
   - Deleting an offer decrements the ad's offer count

3. **View Restrictions:**
   - Offer owner can always view their own offer
   - Ad owner can view all offers on their ad
   - Admin can view any offer
   - Other users cannot view offers

4. **Status Rules:**
   - `pending`: Can be updated or deleted
   - `accepted`: Cannot be updated or deleted
   - `rejected`: Cannot be updated or deleted

---

## Integration Notes

- All endpoints require authentication via Sanctum bearer token
- All endpoints support route model binding (automatic object resolution)
- All operations are logged for audit purposes
- Offer updates/deletes are wrapped in database transactions
- Automatic cache clearing happens on offer modifications where applicable
