# Media

- POST /media — Upload media (multipart/form-data). Fields: `file` (binary), `purpose`, `related_resource`, `related_id`.
- GET /media/{mediaId} — Get media metadata / signed URL.
- DELETE /media/{mediaId} — Delete media.

Response schema: `MediaResponse` in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
