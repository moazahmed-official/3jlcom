# Auth API

- POST /auth/login — Authenticate user/admin and return JWT token. Auth: no
- POST /auth/register — Create account (OTP flow). Auth: no
- PUT /auth/verify — Verify OTP code. Auth: no
- POST /auth/logout — Invalidate current token. Auth: Bearer
- PUT /auth/password/reset — Reset password with code. Auth: no

See full schemas and examples in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
