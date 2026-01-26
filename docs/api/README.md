# API Overview

This folder contains a short developer-facing API README.

Base URL
- Local: `http://localhost/v1`

Auth (login)
- `POST /v1/auth/login` — phone + password -> returns Bearer token

Examples
- See `system analysis and design/api-catalog/openapi/auth.md` for detailed curl examples and responses.

Run tests
```bash
php artisan test --filter=AuthLoginTest
```
