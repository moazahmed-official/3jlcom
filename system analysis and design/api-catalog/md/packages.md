# Packages

- GET /packages — List available packages (public).
- POST /packages — Create package (admin). Schema: `PackageCreate`.
- GET /packages/{packageId} — Get package details.
- PUT /packages/{packageId} — Update package (admin). Schema: `PackageUpdate`.
- DELETE /packages/{packageId} — Delete package (admin).
- GET /users/{userId}/packages — List packages owned by a user.
- POST /packages/{packageId}/assign — Assign package to user (admin).

Schemas: `PackageCreate`, `PackageUpdate`, `PackageResponse` in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
