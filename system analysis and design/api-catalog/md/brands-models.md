# Brands & Models

- GET /brands — List brands.
- POST /brands — Create brand (admin). Body: `BrandCreate`.
- GET /brands/{brandId}/models — List models for a brand.
- POST /brands/{brandId}/models — Create model for brand (admin). Body: `ModelCreate`.

Schemas: `BrandCreate`, `ModelCreate` in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
