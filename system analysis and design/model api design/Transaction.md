# Transaction

Fields:
- id (int)
- user_id (int)
- subscription_id (int|null)
- ad_id (int|null)
- amount (decimal)
- currency (string)
- gateway (string)
- status (enum: pending|completed|failed|refunded)
- transaction_ref (string|null)
- created_at (datetime)

Relations:
- belongsTo: User, Subscription, Ad

Example JSON:
```json
{
  "id": 900,
  "user_id": 45,
  "subscription_id": 3,
  "amount": 120.00,
  "currency": "JOD",
  "status": "completed",
  "transaction_ref": "pay_abc123"
}
```

API Notes:
- Endpoints: payment integration endpoints are implementation-specific; track receipts and reconcile with subscriptions.
- PCI compliance: avoid storing sensitive card data; use tokenization.