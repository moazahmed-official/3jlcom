# Users API

- GET /users — List users (admin/country_manager). Query: `page`, `limit`.
- POST /users — Create user (admin). Body: `UserCreate`.
- GET /users/{userId} — Get user details.
- PUT /users/{userId} — Update user. Body: `UserUpdate`.
- DELETE /users/{userId} — Delete user.
- POST /users/{userId}/roles — Assign/change roles. Body: `{ roles: string[] }`.
- POST /users/{userId}/verify — Verify seller/showroom (admin).

Schemas: `User`, `UserCreate`, `UserUpdate` in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
