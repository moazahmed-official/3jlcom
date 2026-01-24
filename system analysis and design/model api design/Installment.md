# Installment

Fields:
- id (int)
- original_price (decimal)
- fees (decimal|null)
- deposit_amount (decimal|null)
- installment_amount (decimal|null)
- period_months (int)
- apr (decimal|null)
- created_at (datetime)

Relations:
- belongsTo: Ad

Example JSON:
```json
{
  "id": 77,
  "original_price": 20000.00,
  "deposit_amount": 2000.00,
  "installment_amount": 600.00,
  "period_months": 30,
  "apr": 6.5
}
```

API Notes:
- Included in ad creation payload (installment section).
- Treat financial disclosure fields as country-specific where regulated.