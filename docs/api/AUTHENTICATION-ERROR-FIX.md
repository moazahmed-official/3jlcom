# Authentication Error Fix - "Route [login] not defined"

## Problem

When accessing protected API endpoints without proper headers, you got this error:
```
Symfony\Component\Routing\Exception\RouteNotFoundException
Route [login] not defined.
```

## Root Cause

When authentication fails on API routes without the `Accept: application/json` header, Laravel tries to redirect to a login page instead of returning a JSON error response.

## Fixes Applied

### 1. Updated Exception Handlers

**Files Changed:**
- `bootstrap/app.php`
- `app/Exceptions/Handler.php`

**Changes:**
- Added `|| $request->is('api/*')` check to force JSON responses for all API routes
- Now returns proper 401 JSON response instead of trying to redirect

### 2. Added Login Route

**File:** `routes/web.php`

Added a named 'login' route to prevent the error:
```php
Route::get('/login', function () {
    return response()->json([
        'message' => 'This is an API-only application. Please use the API endpoints with proper authentication.',
        'login_endpoint' => '/api/v1/auth/login'
    ], 401);
})->name('login');
```

## How to Test Now

### ✅ Correct Request (With Accept Header)

```bash
curl -X POST http://localhost:8000/api/v1/reviews \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "target_type": "ad",
    "target_id": 1,
    "stars": 5
  }'
```

**Expected Response (401 - Unauthenticated):**
```json
{
  "status": "error",
  "code": 401,
  "message": "Unauthenticated",
  "errors": {}
}
```

### ✅ With Authentication Token

```bash
# 1. First, login to get token
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "mobile_number": "+962791234567",
    "password": "password123"
  }'

# 2. Use the token in subsequent requests
curl -X POST http://localhost:8000/api/v1/reviews \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "target_type": "ad",
    "target_id": 1,
    "stars": 5,
    "title": "Great car!",
    "body": "Excellent condition"
  }'
```

## Important Headers for All API Requests

### Required Headers

1. **Accept: application/json**
   - Tells Laravel to return JSON responses instead of HTML
   - **CRITICAL:** Without this, you'll get redirect errors

2. **Content-Type: application/json**
   - Required for POST/PUT requests with JSON body
   - Tells Laravel how to parse the request body

3. **Authorization: Bearer TOKEN** (for protected endpoints)
   - Required for authenticated routes
   - Replace TOKEN with your actual access token

### Example with All Headers

```bash
curl -X POST http://localhost:8000/api/v1/reviews \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer 1|abcd1234..." \
  -d '{
    "target_type": "ad",
    "target_id": 15,
    "stars": 5,
    "title": "Excellent!",
    "body": "Great experience"
  }'
```

## Testing Without cURL (Using Browser/Postman)

### Postman Setup

1. **Headers Tab:**
   - Key: `Accept`, Value: `application/json`
   - Key: `Content-Type`, Value: `application/json`
   - Key: `Authorization`, Value: `Bearer YOUR_TOKEN`

2. **Body Tab:**
   - Select "raw"
   - Select "JSON" from dropdown
   - Enter JSON payload

### Browser Console (Fetch API)

```javascript
// Login first
fetch('http://localhost:8000/api/v1/auth/login', {
  method: 'POST',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    mobile_number: '+962791234567',
    password: 'password123'
  })
})
.then(res => res.json())
.then(data => {
  console.log('Token:', data.data.token);
  // Save token for next request
});

// Create review with token
fetch('http://localhost:8000/api/v1/reviews', {
  method: 'POST',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': 'Bearer YOUR_TOKEN_HERE'
  },
  body: JSON.stringify({
    target_type: 'ad',
    target_id: 15,
    stars: 5,
    title: 'Great!',
    body: 'Excellent'
  })
})
.then(res => res.json())
.then(data => console.log('Review created:', data));
```

## Common Errors and Solutions

### Error: "Route [login] not defined"
**Solution:** ✅ FIXED - Added login route and updated exception handlers

### Error: "Unauthenticated"
**Solution:** Include valid Bearer token in Authorization header

### Error: Getting HTML instead of JSON
**Solution:** Add `Accept: application/json` header to ALL requests

### Error: "The target type field is required"
**Solution:** Include proper request body with all required fields:
```json
{
  "target_type": "ad",
  "target_id": 15,
  "stars": 5
}
```

## Summary

✅ **All fixes applied!** The API will now:
- Always return JSON responses for `/api/*` routes
- Never try to redirect to login page
- Return proper 401 errors for unauthenticated requests
- Work correctly even without `Accept` header (for API routes)

**Key Takeaway:** Always include these headers in API requests:
- `Accept: application/json`
- `Content-Type: application/json` (for POST/PUT)
- `Authorization: Bearer TOKEN` (for protected routes)
