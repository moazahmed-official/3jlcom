# API Examples

## POST /api/v1/auth/register

Request:

```bash
curl -X POST 'http://localhost:8000/api/v1/auth/register' \
  -H 'Content-Type: application/json' \
  -d '{"name":"Jane Seller","email":"jane@example.com","phone":"+15551234567","country_id":1,"password":"Secret123!","account_type":"seller"}'
```

Response (200):

```json
{
  "status": "success",
  "message": "Registration successful. Please verify your account with the OTP sent to your phone.",
  "data": {
    "user_id": 42,
    "phone": "+15551234567",
    "expires_in_minutes": 10
  }
}
```

## PUT /api/v1/auth/verify

Request:

```bash
curl -X PUT 'http://localhost:8000/api/v1/auth/verify' \
  -H 'Content-Type: application/json' \
  -d '{"phone":"+15551234567","code":"123456"}'
```

Response (200):

```json
{
  "status": "success",
  "message": "Account verified successfully.",
  "data": {
    "token": "plain-text-sanctum-token",
    "token_type": "Bearer",
    "expires_in": null,
    "user": {
      "id": 42,
      "name": "Jane Seller",
      "email": "jane@example.com",
      "phone": "+15551234567",
      "account_type": "seller",
      "is_verified": true,
      "seller_verified": false,
      "seller_verified_at": null
    }
  }
}
```

## POST /api/v1/seller-verification (authenticated as seller)

Request:

```bash
curl -X POST 'http://localhost:8000/api/v1/seller-verification' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer seller-token' \
  -d '{"documents":[{"type":"business_license","url":"https://example.com/license.pdf"}]}'
```

Response (200):

```json
{
  "status": "success",
  "message": "Seller verification request submitted successfully.",
  "data": {
    "request_id": 7,
    "status": "pending",
    "submitted_at": "2026-01-27T12:34:56.000000Z"
  }
}
```

## PUT /api/v1/seller-verification/{requestId} (authenticated as admin)

Request:

```bash
curl -X PUT 'http://localhost:8000/api/v1/seller-verification/7' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer admin-token' \
  -d '{"status":"approved","admin_comments":"Docs OK."}'
```

Response (200):

```json
{
  "status": "success",
  "message": "Verification request processed successfully.",
  "data": {
    "request_id": 7,
    "status": "approved",
    "admin_comments": "Docs OK.",
    "verified_at": "2026-01-27T13:00:00.000000Z",
    "verified_by": "Admin Name"
  }
}
```

## POST /api/v1/users/{userId}/verify (admin-by-id)

Request:

```bash
curl -X POST 'http://localhost:8000/api/v1/users/42/verify' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer admin-token' \
  -d '{"status":"approved","admin_comments":"Verified directly by admin."}'
```

Response (200):

```json
{
  "status": "success",
  "message": "User verification processed successfully",
  "data": {
    "user_id": 42,
    "verification_status": "approved",
    "admin_comments": "Verified directly by admin.",
    "verified_at": "2026-01-27T13:05:00.000000Z"
  }
}
```
