# Reviews and Reports API - Issues Fixed

## Problems Identified and Resolved

### 1. ❌ ISSUE: Unauthenticated Error

**Problem:**  
You were getting "Unauthenticated" errors when trying to create reviews/reports.

**Root Cause:**  
The endpoints require authentication via Bearer token, but you weren't including the `Authorization` header in your requests.

**Solution:**  
Always include the Bearer token in protected endpoints:

```bash
curl -X POST http://localhost:8000/api/v1/reviews \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{...}'
```

### 2. ❌ ISSUE: Missing Required Fields

**Problem:**  
The creation endpoints weren't accepting the proper parameters to specify what ad/user is being reviewed/reported.

**Root Cause:**  
The API documentation was showing incorrect field names. The actual required fields are:
- `target_type` (not just "type")
- `target_id` (not just "id")

**Correct Request Format:**

#### For Reviews:
```json
{
  "target_type": "ad",        // or "seller"
  "target_id": 15,            // ID of the ad or seller
  "stars": 5,                 // 1-5 rating
  "title": "Great!",          // Optional
  "body": "Details here"      // Optional
}
```

#### For Reports:
```json
{
  "target_type": "ad",        // or "user" or "dealer"
  "target_id": 15,            // ID of the target
  "reason": "Misleading",     // Required
  "title": "Issue",           // Optional
  "details": "More info"      // Optional
}
```

### 3. ✅ FIXED: Authorization in ReviewController

**Problem:**  
The `show()` method in ReviewController was calling `authorize('view')` even though reviews are public.

**Fix Applied:**  
Removed the unnecessary authorization check since the ReviewPolicy allows anyone to view reviews.

**File Changed:** `app/Http/Controllers/Api/V1/ReviewController.php`

---

## How to Use the APIs Correctly

### Step 1: Get Your Authentication Token

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "mobile_number": "+962791234567",
    "password": "password123"
  }'
```

**Save the token from the response:**
```json
{
  "success": true,
  "data": {
    "token": "1|abcd1234efgh5678..."  // ← COPY THIS
  }
}
```

### Step 2: Create a Review (Correct Way)

```bash
curl -X POST http://localhost:8000/api/v1/reviews \
  -H "Authorization: Bearer 1|abcd1234efgh5678..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "target_type": "ad",
    "target_id": 15,
    "stars": 5,
    "title": "Excellent car!",
    "body": "Car was exactly as described."
  }'
```

### Step 3: Create a Report (Correct Way)

```bash
curl -X POST http://localhost:8000/api/v1/reports \
  -H "Authorization: Bearer 1|abcd1234efgh5678..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "target_type": "ad",
    "target_id": 15,
    "reason": "Misleading price",
    "title": "Price does not match",
    "details": "Ad shows 2020 but car is 2018"
  }'
```

---

## What Was Wrong with the Documentation

### ❌ Old (Incorrect) Documentation Showed:

```json
{
  "title": "Great service",
  "body": "Very helpful",
  "stars": 5
}
```

**Problem:** Missing `target_type` and `target_id` fields!

### ✅ New (Correct) Format:

```json
{
  "target_type": "ad",
  "target_id": 15,
  "stars": 5,
  "title": "Great service",
  "body": "Very helpful"
}
```

---

## API Endpoints Summary

### Public Endpoints (No Auth Required)
- `GET /api/v1/reviews` - List all reviews
- `GET /api/v1/reviews/{id}` - Get specific review
- `GET /api/v1/ads/{id}/reviews` - Reviews for an ad
- `GET /api/v1/users/{id}/reviews` - Reviews for a seller

### Protected Endpoints (Auth Required)
- `POST /api/v1/reviews` - Create review ⚠️
- `PUT /api/v1/reviews/{id}` - Update review ⚠️
- `DELETE /api/v1/reviews/{id}` - Delete review ⚠️
- `GET /api/v1/reviews/my-reviews` - My reviews ⚠️
- `POST /api/v1/reports` - Create report ⚠️
- `GET /api/v1/reports/my-reports` - My reports ⚠️
- `GET /api/v1/reports/{id}` - View report ⚠️

### Admin Endpoints (Admin/Moderator Only)
- `GET /api/v1/reports/admin/index` - All reports 🔒
- `POST /api/v1/reports/{id}/assign` - Assign report 🔒
- `PUT /api/v1/reports/{id}/status` - Update status 🔒
- `POST /api/v1/reports/{id}/actions/resolve` - Resolve 🔒
- `POST /api/v1/reports/{id}/actions/close` - Close 🔒
- `DELETE /api/v1/reports/{id}` - Delete 🔒

---

## Testing the Fixed APIs

### Test 1: Create a Review for an Ad

```bash
# Replace with your actual token and ad ID
curl -X POST http://localhost:8000/api/v1/reviews \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "target_type": "ad",
    "target_id": 1,
    "stars": 5,
    "title": "Test review",
    "body": "This is a test review"
  }'
```

### Test 2: Create a Report for an Ad

```bash
# Replace with your actual token and ad ID
curl -X POST http://localhost:8000/api/v1/reports \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "target_type": "ad",
    "target_id": 1,
    "reason": "Testing report system",
    "title": "Test report",
    "details": "This is a test report"
  }'
```

### Test 3: Get All Reviews (No Auth)

```bash
curl -X GET http://localhost:8000/api/v1/reviews \
  -H "Accept: application/json"
```

---

## Common Errors and Solutions

### Error: "Unauthenticated"
**Solution:** Add the Authorization header with your Bearer token

### Error: "The target type field is required"
**Solution:** Include both `target_type` and `target_id` in your request body

### Error: "You have already reviewed this ad"
**Solution:** Each user can only review an ad/seller once. Update instead of creating new.

### Error: "You cannot review yourself"
**Solution:** Users cannot review their own ads or themselves as sellers

### Error: "Too Many Attempts"
**Solution:** Rate limit is 10 requests per hour. Wait before trying again.

---

## Files Changed

1. ✅ `app/Http/Controllers/Api/V1/ReviewController.php`
   - Removed unnecessary `authorize()` call from `show()` method

2. ✅ Created: `docs/api/reviews-reports-curl-correct.md`
   - Complete, corrected documentation with:
     - Proper authentication examples
     - Correct field names (`target_type`, `target_id`)
     - Full request/response examples
     - Error handling guide
     - Quick reference section

---

## Next Steps

1. ✅ Code is fixed
2. ✅ Documentation is corrected
3. ⏭️ Test with the examples above
4. ⏭️ If you find any more issues, let me know!

---

**The API logic is NOT broken!** It was working correctly all along. The issues were:
- Missing authentication token in requests
- Incorrect field names in documentation
- One small authorization check that was removed

Everything should work perfectly now! 🎉
