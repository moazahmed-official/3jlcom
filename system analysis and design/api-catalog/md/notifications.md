# Notifications

- GET /notifications — List notifications for current user (auth required).
- POST /notifications — Send notification (admin). Schema: `NotificationSend`.
- GET /notifications/{id} — Get a notification.
- PATCH /notifications/{id} — Mark notification read/hide.

Schemas: `NotificationSend`, `NotificationResponse` in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
