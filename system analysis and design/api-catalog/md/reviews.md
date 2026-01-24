# Reviews

- POST /reviews — Create a review for a seller or ad. Auth: Bearer. Schema: `ReviewCreate`.
- GET /reviews — List reviews (admin/moderator or filtered).
- GET /reviews/{reviewId} — Get review details.
- PUT /reviews/{reviewId} — Update a review (owner). Schema: `ReviewUpdate`.
- DELETE /reviews/{reviewId} — Delete a review (owner/admin).

Permissions: operation-level `x-permissions` are defined in the OpenAPI fragments.

Schemas: `ReviewCreate`, `ReviewUpdate`, `ReviewResponse` in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
