## Auth Endpoint Guide

Path: `/v1/auth/login`
Method: `POST`

Request
- Headers: `Content-Type: application/json`
- Body schema:

```json
{
  "phone": "+201001234567",
  "password": "secret-password",
  "remember_me": true
}
```

Example curl (success)
```bash
curl -X POST 'http://localhost/v1/auth/login' \
  -H 'Content-Type: application/json' \
  -d '{"phone":"+201001234567","password":"secret-password"}'
```

Example successful response (200)
```json
{
  "success": true,
  "message": "Authenticated",
  "data": {
    "token": "<token>",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "name": "John Doe",
      "phone": "+201001234567",
      "account_type": null,
      "is_verified": false,
      "created_at": "2026-01-26 12:00:00"
    }
  }
}
```

Example error (invalid credentials — 401)
```json
{
  "status": "error",
  "code": 401,
  "message": "Invalid credentials",
  "errors": {}
}
```

Logout (curl)
```bash
curl -X POST 'http://localhost/v1/auth/logout' \
  -H 'Authorization: Bearer <token>'
```

Notes
- The implementation uses Laravel Sanctum (`createToken`) and returns a personal access token in `data.token`.
- Tests: `tests/Feature/Api/AuthLoginTest.php`.
