# Smart Car Ads Platform & Application — Detailed Requirements

An integrated digital marketplace (Website + Mobile App) specialized in car advertisements and discovery, serving B2C, B2B, and C2C use cases. Target audiences include Individuals, Dealers, and Showrooms. Supports multiple selling systems: Direct Sale, Installments, Offers, and Auctions.

## 1. Overview & Scope
- **Channels:** Responsive Web (SEO-friendly), iOS, Android.
- **Audiences:** Individuals, Dealers/Showrooms, Marketers, Moderators, Country Managers, Super Admins.
- **Core domains:** User Management, Multi-Country & Localization, Ads Creation & Publishing, Packages & Subscriptions, Search & Discovery, Dealer System, AI-assisted workflows, Payments, Notifications, Moderation.
- **Out-of-scope (MVP):** Full AI price logic training and advanced Car history integrations beyond basic checks may be phased.

## 2. Non-Functional Requirements
- **Performance:** Fast page loads (<2s TTI on 4G for primary pages), responsive UI, server-side pagination for listings.
- **Scalability:** Horizontally scalable services; CDN-backed media; queue-based jobs for heavy tasks (imports, image processing, notifications).
- **Availability:** Target 99.9% uptime for core listing and search; graceful degraded modes when AI/external APIs fail.
- **Security:** OTP-based phone verification, role-based access control (RBAC), audited admin actions, secure payments (PCI-compliant gateways), CSRF/XSS protections, encrypted at-rest secrets.
- **Privacy & Compliance:** GDPR-like consent for tracking, cookie banner, opt-out of marketing; data retention policies per country.
- **Localization:** Multi-language (Arabic default, English optional), country-specific currency/cities/financing rules.
- **Observability:** Centralized logging, analytics events, error tracking, uptime monitoring, basic BI dashboards in admin.

## 3. User Management System

### 3.1 Registration & Authentication
- **Data:** Full Name, Phone Number (OTP Verification — Paid Service), Country (mandatory), optional City at signup.
- **Constraints:** App usage requires country selection; selected country saved as default (cookie/local storage and server profile).
- **Flows:**
	- Signup with phone → OTP verification → country selection → profile completion.
	- Login with phone → OTP (or trusted device token).
- **Paid OTP:** Configurable per country; free trials or paid bundles; rate limits per number.

### 3.2 Account Types & Roles
- **Guest (Read-only):**
	- Browse listings in selected/default country.
	- Use search/filters; view basic ad details.
	- Cannot post, contact directly (WhatsApp copy allowed optionally), or save comparisons.
- **Individual:**
	- Create/manage personal ads (limits per package).
	- Access favorites, comparisons, offers; direct contact via WhatsApp; receive FindIt responses.
- **Dealer / Showroom:**
	- Dealer page (logo, overview, reviews, badge eligibility).
	- Bulk ad upload, package-based normal/featured quotas.
	- Access performance reports (views, contacts, conversions), auto-homepage posting (if enabled in package).
- **Marketer:**
	- Run promotional campaigns within platform (feature boosts, coupons where applicable).
	- Limited content rights; cannot modify dealer content without permission.
- **Moderator:**
	- Review and approve/decline ads, auctions, Caishha/FindIt requests.
	- Handle reports, content flags, suspected fraud; edit/disable content per policy.
	- Audit trail for actions.
- **Country Manager:**
	- Manage country-specific settings (currency, cities, financing rules, packages, pricing).
	- Oversee local moderation queue; view country analytics; assign local moderators.
- **Super Admin:**
	- Global configuration, role assignment, all-country visibility.
	- Financial dashboards, system health, feature toggles (AI, auctions), integration keys.

Each role must have explicit permissions defined (CRUD on ads, manage packages, access analytics, perform moderation actions, billing visibility). A role-permission matrix is maintained in admin.

### 3.3 Profile Management
- **Editable fields:** Name, Phone Number, Profile Picture, City.
- **User assets:**
	- Ads: view/manage state (draft, pending review, published, featured, expired).
	- Offers: sent/received and statuses.
	- Favorites: saved ads, dealer pages.
	- Comparisons: add/remove cars, view side-by-side specs (brand, model, year, mileage, condition, price, installment details).

## 4. Multi-Country System

### 4.1 Countries & Localization
- **Coverage:** All Arab countries; default country: Jordan.
- **Per-country data:** Independent currency, independent city taxonomy, country-specific settings (Financing, Installments, Regulations, content policies).
- **Selection mechanisms:**
	- Automatic via IP (best-effort; allow override).
	- Manual selection (on first visit; persist in cookies + server profile).
- **Browsing scope:** Users cannot browse or mix ads across countries without switching; search results scoped to active country.

### 4.2 Country Admin Panel
- **Capabilities:**
	- Manage content per country (cities, packages, pricing, banners, featured slots).
	- Assign Country Manager roles and local moderators.
- **Options:**
	- Separate admin panel per country (scoped views) or unified global admin with role-based scoping.

## 5. Ads Creation System

### 5.1 Ad Posting Process (Guided)
- **Inspiration:** Ajalkom, OpenSooq.
- **Steps:**
	1. Select Category (Car → brand/model/year, etc.).
	2. Enter Details (condition, mileage, owners, fuel, transmission, color, city).
	3. Upload Photos/Videos (min/max per package, quality checks).
	4. Select Ad Type (Normal, Featured, Super Featured, Installment, Auction).
	5. Payment (if applicable; feature fees, auction listing fee).
	6. Publish (moderation step optionally required).
- **Drafts & Autosave:** Enabled to reduce friction.

### 5.2 Ad Types & Rules
- **Normal Ad:**
	- Visible in standard listings; eligible for search and filters.
	- Creation: Individuals, Dealers; package quotas apply.
- **Featured Ad:**
	- Elevated placement in category pages and country homepage slots; highlight badge.
	- Creation: Individuals/Dealers with feature entitlements or paid one-off boost.
- **Super Featured Ad:**
	- Top-of-list placements, larger card, frequent carousel on homepage.
	- Creation: Dealers/Showrooms primarily; higher fee/limited slots; admin configurable.
- **Installment Ad:**
	- Displays financing details prominently; filtered under “Installments”.
	- Creation: Dealers or Individuals where financing partners exist; requires compliance checks.
- **Auction Ad (Caishha / FindIt-compatible):**
	- Timed bidding, reserve price optional; anti-sniping extension configurable.
	- Creation: Dealers or verified Individuals; fees/escrow optional per country.

For each type, define: who can create, required fields, placement locations (homepage modules, category tops, carousels), pricing, moderation requirements.

### 5.3 Installment Fields
- **Fields:** Down Payment, Installment Value, Duration (months), APR/fees (if applicable).
- **Display:** Prominent badges and structured box on ad card and details page.
- **Compliance:** Per-country regulations and disclosures.

## 6. AI in Ads Creation

### 6.1 Ad via External Link (Optional Feature Toggle)
- **User input:** External ad URL.
- **System actions:** Extract images (if allowed), parse specifications, generate initial description.
- **Notes:**
	- Not all websites allow scraping; handle gracefully.
	- User can edit extracted results before publish.
	- Toggleable from admin per country.

### 6.2 Ad Quality Analysis
- **Automated checks:**
	- Image Quality (external AI API): resolution, sharpness, brightness, no-watermark.
	- Data Completeness: mandatory fields filled; suggestion hints.
	- Price Logic (long-term): trained AI model for price sanity; flagged if outliers.
	- View Optimization Suggestions (external AI API): title improvements, ordering of photos, missing angles.
- **UX:** Inline tips during creation and pre-publish review summary.

## 7. Packages & Subscriptions

### 7.1 Individual Packages
- **Parameters:** Number of ads, ad duration, media quotas (images/videos), ability to feature (discounts or add-ons).
- **Renewals:** Reminders near expiry; grace periods configurable.

### 7.2 Dealer Packages
- **Parameters:** Number of normal ads, number of featured ads, package duration.
- **Perks:** Automatic homepage posting (slots), performance reports, bulk upload/import, API access (optional premium).

### 7.3 Package Management (Admin)
- **Controls:** Set price, duration, features, quotas; country-specific.
- **Tracking:** Payments, expiration dates, utilization; invoices.
- **Financials:** Revenue stats per country, package performance, ARPU.

## 8. Ad Display & UX

### 8.1 Listing Layouts
- **Layouts:** Vertical and Horizontal card layouts similar to Dooz/OpenSooq.
- **Modules:** Featured carousels, recommended blocks, category headers, dealer highlights.

### 8.2 Search & Filtering
- **Search:** Smart search with Autocomplete (brand/model/city).
- **Filters:** Price range, City, Condition, Installments available, Featured, Year, Mileage, Fuel, Transmission.
- **Scope:** Search within any page context (country/category), with clear breadcrumbs.

### 8.3 Sorting
- **Options:** Latest, Price (low→high, high→low), Nearest (geo/selected city), Most viewed.

## 9. Ad Details Page
- **Media:** High-quality images, short video with sound, optional 360° images (equirectangular or multi-angle stitched; requires viewer component).
- **Meta:** Views count, posting date, seller type (Individual/Dealer), city.
- **Actions:** Direct WhatsApp button, Share, Copy link, Report.
- **Installments:** Prominent box if applicable; calculator where partners allow.

### 9.1 Verified Information
- **Ad change history:** Changelog of edits (price changes, description updates).
- **Car history:** Number of owners, accidents (if any) — integrate with Carfax or country-specific alternatives where available.
- **Badges:** Verified seller, Trusted dealer, No-accident claim (where verified).

## 10. Dealers System

### 10.1 Dealer Page
- **Content:** Overview, Logo, All ads, Reviews, Badge (Trusted Dealer when criteria met).
- **SEO:** Indexable dealer pages, structured data.

### 10.2 Dealer Dashboard
- **Tools:** Manage ads (bulk upload CSV/API), track views/contacts/sales conversions, manage packages, request badges.
- **Reports:** Time-series charts, top-performing ads, contact sources, homepage slot effectiveness.

## 11. Special Features (Competitive Advantage)

### 11.1 Caishha (Offer-First Flow)
- **Mechanism:**
	- Seller posts car; dealers receive offer window first (e.g., 36 hours).
	- After window, open to individuals (optional per country/policy).
	- Approvals: automatic (threshold-based) or manual by moderator.
	- Notifications: real-time push/SMS (configurable), email.

### 11.2 FindIt (Request-by-Specs)
- **Mechanism:**
	- Buyer requests car by specs (brand, model, year, budget, city).
	- Notify dealers matching inventory; responses collected within set period.
	- Buyer reviews quotes; direct contact via WhatsApp or in-app messaging (optional).
	- Moderation for spam/irrelevant offers.

## 12. Admin & Moderation
- **Global Admin:** Feature toggles (AI, auctions, scraping), role management, country setups, payment gateways, homepage modules.
- **Country Admin:** Cities, packages, currency, financing rules; local moderation queue; analytics.
- **Moderation:** Workflows for reviewing ads, auctions, Caishha/FindIt; fraud flags; escalation; audit log.
- **Content Policies:** Disallowed content auto-detection (AI-supported); enforced takedowns.

## 13. Payments & Monetization
- **Paid services:** OTP verification (per-country), ad featuring, super featuring, auction fees, subscription packages.
- **Gateways:** PCI-compliant providers; country-specific options; refunds and disputes handling.
- **Invoices & Receipts:** Email + dashboard; VAT per country rules.

## 14. Notifications & Messaging
- **Channels:** Push (mobile/web), SMS (critical only), Email, WhatsApp deep links.
- **Events:** New offer, auction bid updates, package expiry, ad approval/rejection, dealer review responses, FindIt responses.
- **Preferences:** User-level opt-ins per channel.

## 15. Data Model (High-Level)
- **Users:** roles, profile, country, verification status.
- **Dealers:** company profile, badge status, packages, reviews.
- **Ads:** type, specs (brand, model, year, mileage, fuel, transmission, color), price, city, media, installment info, status, history.
- **Packages:** entitlements, quotas, durations, pricing, utilization.
- **Auctions:** bids, reserve, timing, status, anti-sniping policy.
- **FindIt:** requests, responses, matching rules.
- **Moderation:** queues, decisions, audit logs.
- **Payments:** invoices, transactions, refunds.

## 16. Search & Indexing
- **Indexing:** Dedicated search index per country; facets on brand/model/city/price/year/mileage/type.
- **Autocomplete:** Brand/model/city with popularity weighting.
- **Geo:** City-based nearest sorting; optional geocoordinates for precise distance where available.

## 17. Media Handling
- **Images/Videos:** Upload limits per package; server-side compression; aspect ratio checks; watermark policy per country.
- **360° support:** Optional viewer; guidance for upload formats.
- **CDN:** Global distribution; signed URLs; expiry policies.

## 18. Analytics & Reporting
- **User analytics:** DAU/MAU, session funnels, conversion from view→contact→sale (self-reported).
- **Ad analytics:** Views, saves, shares, contact clicks; by type (normal/featured/super/auction/installment).
- **Dealer analytics:** Page views, ad performance, lead sources, ROI of featured slots.
- **Admin dashboards:** Revenue by country, package mix, moderation volumes, fraud flags.

## 19. Compliance & Legal
- **Terms:** Clear marketplace terms per country; dispute resolution processes.
- **Privacy:** Consent management, data export request flow, account deletion.
- **Content:** Prohibited content lists; reporting tools; law-enforcement request handling.

## 20. Delivery & Phasing

### 20.1 MVP (Initial Operation)
- Country selection & scoping (Jordan default), core RBAC (Guest, Individual, Dealer, Moderator, Country Manager, Super Admin).
- Ad creation (Normal, Featured), media uploads, basic search & filters, ad details page.
- Packages for Individuals & Dealers, payment integration (at least one gateway), OTP verification.
- Dealer page basics; favorites & comparisons; basic moderation; notifications.

### 20.2 Iterative Releases (Agile SLC)
- Installment ads with fields and compliance per country.
- Auction ads with timed bidding, anti-sniping.
- Caishha (dealer-first offers) and FindIt (request-by-specs) flows.
- AI-assisted creation (external link import, quality analysis); expand to price logic model later.
- Advanced analytics dashboards; Car history integrations per market.

## 21. Operational Notes
- **Clean Code:** Follow SOLID principles, linting, code review gates.
- **Clear Architecture:** Layered modules (domain/services/controllers), RBAC middleware, queue workers.
- **Documentation:** API specs, admin user guides, deployment runbooks.
- **Code Comments:** Pragmatic comments on non-obvious logic; moderation workflows documented.
- **Transferability:** Onboarding docs; infrastructure as code; env templates; seed data for countries.

---

This document captures the functional and non-functional requirements for the Smart Car Ads Platform, outlining role permissions, country-specific scoping, ad types and flows, AI-assisted features, monetization, and phased delivery to ensure a robust, scalable marketplace.

