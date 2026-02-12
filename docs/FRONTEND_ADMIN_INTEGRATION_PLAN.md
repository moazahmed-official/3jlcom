# Frontend Admin Integration Plan (Updated)

## Purpose
This document updates the admin-panel frontend integration plan to match Phase 2 & 3 backend changes (dual-track ads, feature credits, package visibility, enhanced conversions). It replaces mock-data usage, wires real API hooks, and adds new admin pages.

### High-level changes
- Replace mock hooks with real API hooks for Reports, Ads, Caishha, Auctions
- Add new admin pages: Upgrade Requests, Package Visibility, Feature Credits
- Add ad-conversion UI and feature-credits UI
- Add bulk ops, exports, improved filters, and real-time auction polling

## Key corrections and additions (delta vs original plan)
- Use exact backend endpoints listed in `API_COMPLETE_DOCUMENTATION.md` (e.g. `/admin/upgrade-requests`, `/admin/packages/{id}/visibility`, `/ads/{ad}/convert`, `/features/{feature}/use`).
- Pre-fetch dropdown lists: unique ad types, packages, roles, users for grant-autocomplete.
- Client-side pre-checks before calling convert/use-feature: active package existence and permission checks.
- Display credit source (package | unique_ad_type) and remaining credits in confirm dialogs.
- RBAC: hide/disable admin-only controls for non-admin users.
- Exports: confirm backend export endpoints exist or implement client-side CSV fallback.
- Add audit links in dialogs where applicable.

## Phased Work

PHASE 1 — Replace Mock Data with Real APIs (Core Admin)

1. Reports.tsx
  - Replace `useMockAdmin('reports')` → `useAdminReports({ status, search, page, per_page })`
  - Tabs: All/Pending/Reviewing/Resolved map to `status` param
  - Mutations: `useUpdateReport(id, payload)`, `useDeleteReport(id)`
  - Add pagination and search

2. Ads.tsx
  - Replace `useMockAdmin('ads')` → `useNormalAds({ status, search, page, per_page })`
  - Approve: `usePublishAd(adId)`; Reject: `useUnpublishAd(adId)`; Delete: `useDeleteAd(adId)`
  - Bulk: `useBulkAdAction()`
  - Drawer: `useNormalAd(adId)` for details
  - Add debounced search and pagination

3. Caishha.tsx
  - Replace mock → `useAdminCaishhaAds({ status, search, page, per_page })`
  - Offers: `useCaishhaOffers(adId, { page, per_page })`
  - Offer actions: `useAcceptOffer`, `useRejectOffer`, `useDeleteOffer`
  - Publish/unpublish: `usePublishCaishha`, `useUnpublishCaishha`

4. Auctions.tsx
  - Replace mock → `useAdminAuctions({ status, search, page, per_page })`
  - Bids: `useAuctionBids(auctionId, { page, per_page })` and `useDeleteBid(bidId)`
  - Close/Cancel: `useCloseAuction`, `useCancelAuction`
  - Poll live auctions: refetchInterval=10000 for `status=live`

PHASE 2 — New Phase 2/3 Features (New Pages & UI)

1. UpgradeRequests.tsx (NEW)
  - Hooks: `useUpgradeRequests(params)`, `useApproveUpgrade(id, payload)`, `useRejectUpgrade(id, payload)`
  - Endpoint: `/admin/upgrade-requests` and `/admin/upgrade-requests/{id}/approve|reject`
  - UI: Tabs (Pending/Approved/Rejected), approve dialog with `unique_ad_type` dropdown
  - Sidebar: add navigation item + pending badge count

2. PackageVisibility.tsx (NEW)
  - Hooks: `usePackageVisibility(packageId)`, `useUpdateVisibility()`, `useGrantAccess()`, `useRevokeAccess()`, `useUsersWithAccess(packageId)`
  - UI: package selector, visibility radio, role multi-select, user-autocomplete for grants
  - Use `GET /packages` to populate selector and `GET /roles` for role options

3. FeatureCredits.tsx (NEW)
  - Hooks: `useFeatureUsageLogs(params)`, `useFeatureCreditStats()`, `useUserFeatureCredits(userId)`
  - UI: stats cards, charts, filters, usage logs table, export
  - Include ability to view per-user credits and grant via admin endpoint

4. Ads: Convert UI
  - Extend ad drawer: `useConvertAdType(adId)` and `useAdConversionHistory(adId)`
  - Pre-checks: call `/packages/check-capability` or derive from `/features/my-credits` before converting
  - If converting to `unique` show `unique_ad_type` selector

PHASE 3 — Enhanced Admin Experience (UX)

1. Bulk Operations (Ads/Auctions/Caishha)
  - Add multi-select + bulk toolbar; use `useBulkAdAction()`

2. Real-time Auctions
  - Polling for live auctions; countdowns and live-bid refresh

3. Export & Filters
  - Export CSV button (validate backend export endpoints)
  - Enhanced filters: DateRange, multi-select brand/model/category, save presets in localStorage

## Client-side Pre-check patterns
- `getActivePackage(userId)` or call `/packages/my-packages` to confirm active subscription
- `checkFeatureAvailability(feature, adId)` → preview if action will succeed
- If backend supports, call a `dry-run` or simulate endpoint; otherwise call validations locally and fallback to backend error message

## RBAC
- Use user roles from `auth` to hide admin-only buttons. Always verify on mutation responses (403 handling)

## Testing matrix (add to QA checklist)
- Feature credits: use, logs, decrement, source (package vs type)
- Conversions: admin bypass, free allowed/denied by package, paid conversions
- Visibility: role-based and user-specific grant/revoke
- Export: backend vs client fallback
- Edge cases: pending upgrade request + manual conversion collision

## Deliverables
- `src/pages/admin/UpgradeRequests.tsx` + `src/api/admin/upgradeRequests.ts`
- `src/pages/admin/PackageVisibility.tsx` + `src/api/admin/packageVisibility.ts`
- `src/pages/admin/FeatureCredits.tsx` + `src/api/admin/featureCredits.ts`
- Ads drawer conversion UI and `src/api/ads.ts` updates

## Timeline & priorities
1. Reports, Ads core (replace mocks) — 2 days
2. UpgradeRequests + Convert UI — 2 days
3. PackageVisibility + FeatureCredits — 3 days
4. Bulk/Exports/Filters/Polish — 2 days

---
## Notes
- Link these docs into `docs/FRONTEND_API_HOOKS.md` for exact hook signatures.
