# Bid

Fields:
- id (int)
- auction_id (int)
- user_id (int)
- amount (decimal)
- created_at (datetime)

Relations:
- belongsTo: Auction, User

Example JSON:
```json
{
  "id": 7001,
  "auction_id": 401,
  "user_id": 150,
  "amount": 16000,
  "created_at": "2026-02-03T12:34:00Z"
}
```

API Notes:
- Endpoints: `POST /api/user/auction/{id}/offers`, `DELETE /api/user/auction/offers/{id}`.
- Bids must be validated against current highest bid and anti-sniping rules.