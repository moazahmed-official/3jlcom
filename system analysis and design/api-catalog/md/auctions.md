# Auctions

- POST /auctions — Create an auction. Body: `AuctionCreate`.
- GET /auctions — List auctions. Query: `page`.
- GET /auctions/{auctionId} — Get auction details.
- PUT /auctions/{auctionId} — Update auction.
- DELETE /auctions/{auctionId} — Delete auction.
- POST /auctions/{auctionId}/bids — Place a bid. Body: `BidCreate`.
- GET /auctions/{auctionId}/bids — List bids for an auction.
- POST /auctions/{auctionId}/actions/close — Close auction (owner/admin).

Schemas: `AuctionCreate`, `BidCreate` in [api-catalog/openapi.bundle.yaml](api-catalog/openapi.bundle.yaml).
