# Registration & Ads System Requirements

This document captures detailed functional requirements for Registration & Account System, Ads System, Packages, UX, Dealers, Caishha/Auction, Marketer flows, Admin controls, and premium features.

## 1. Registration & Account System

### 1.1 User Registration
- Required fields at signup: Name, Phone Number, Country.
- Phone OTP verification (configurable per country; third-party SMS provider). OTP is used for account activation and login.
- Country is mandatory — stored as user's default and used to scope browsing, currency, and settings.
- Profile editing path: Settings → My Account (Editable: Name, Phone, Country, City, Profile Image).

### 1.2 Account Types
- Individual
- Dealer
- Showroom / Agency
- Marketer
- Moderator
- Country Manager
- Super Admin

Each account type has a dedicated public/internal page and a tailored dashboard where applicable (e.g., Dealer dashboard, Marketer workspace).

### 1.3 Account Permissions & Controls
- Admin abilities:
  - Enable / Disable account
  - Assign / Change roles
  - View verification status and request additional documents
- Dedicated pages: public-facing profiles for Dealers/Showrooms with badges, stats and contact methods.

## 2. Ads System

### 2.1 Ad Posting Process
- Platforms: Mobile App, Website, Admin Panel (manual posting/assistance).
- UX must be guided, step-by-step (category → specs → media → ad type → extras → payment → publish).
- Autosave drafts; pre-publish checks (images, mandatory fields).

### 2.2 Ad Types
- Normal Ad
- Featured Ad
- Super Featured Ad
- Auction Ad
- Caishha Ad (offers-first)
- FindIt Ad (request-by-spec)

### 2.3 Ad Properties
- Media: images, videos, optional 360° images viewer support.
- Fields / Options:
  - Customs cleared / Not cleared (Boolean)
  - Cash price
  - Installments: down payment, installment value, period
  - Electric-specific: battery range, battery capacity (kWh)
  - Contact numbers: phone, WhatsApp
  - Ad life period (days)

### 2.4 Dynamic Fields & Admin Control
- Admin can add/remove fields and customize fields per category.
- Field types supported: text, number, select, boolean, date, multi-select, media.
- Changes take effect per-country and per-category; versioned field config with migration plan for old ads.

## 3. Packages System

### 3.1 Package Types
- Individual Packages
- Dealer Packages
- Showroom Packages

### 3.2 Package Contents & Perks
- Entitlements: number of normal ads, featured ads, videos, 360° uploads, FindIt/Caishha credits.
- Duration (days), auto-renew rules, promotional pricing.
- Supports Auction / Caishha activation per package and automatic posting slots.
- Optional auto-post to Facebook (text + link) where enabled.

### 3.3 Package Management (Admin)
- Feature table for each package, description, links to payments, active dates, statistics (utilization), invoices.
- Ability to enable/disable any ad type within package and limit specific ad characteristics.

## 4. Ads Display & User Experience (UX)

### 4.1 Display Options
- Support vertical and horizontal card layouts; toggle in user settings.
- Layouts should align with Dooz / OpenSooq patterns (prominent image, brief specs, price, badges).

### 4.2 Ad Details Page
- Media carousel (images, optional video with sound, 360° viewer).
- Metadata: posting time, views count, seller type (individual/dealer), verification badges.
- Actions: Direct WhatsApp (with ad link), Call, Share, Copy link, Report.

### 4.3 Search & Filters
- Smart search with autocomplete (brand, model, city).
- Filters: Price range, City, Installments available, Brand, Year, Mileage, Condition, Featured.
- Global search bar (available on every page).
- Save search feature with notifications on matching results.

## 5. Home Page
- Manual admin customization with modules/blocks.
- Default sections: Recently added, Caishha / Auctions, Installment cars, Non-cleared cars, Quick filters.
- Sorting: Date, Price, Views; modules configurable per country.

## 6. Countries & Cities System (Future / Phased)
- Multi-country support across Arab countries; default country: Jordan.
- Each country: independent currency, independent city taxonomy, custom settings for financing and legal rules.
- Country settings locked by default; editable only by admin or country manager with audit trail.

## 7. Dealers / Stores System

### 7.1 Dealer Pages
- Verified store pages with logo, overview, contact info, all ads (normal & featured), videos, ratings & reviews, Trusted Dealer badge.

### 7.2 Dealer Benefits & Dashboard
- Bulk ad upload (CSV/API), premium subscriptions, automatic posting, analytics (views, contacts, conversions), lead management.

## 8. Caishha / Auction System

### 8.1 Caishha (Offers Flow)
- Seller posts Caishha ad (no price). Dealers receive exclusive 36-hour window to submit offers (configurable).
- After dealer window, ad opens to individuals (if enabled).
- Admin controls: enable/disable Caishha per country/package, set window duration, auto/manual approval.
- Notifications include title, description, image, and quick actions to make an offer.

### 8.2 Auction
- Timed auctions with countdown, bid submission, bid history (admin-visible), hide participant names by default.
- Admin can configure anti-sniping (extend end time on last-minute bids), auto-close behavior, reserve price.

## 9. Marketer Account
- Dedicated marketer page listing offered ads and requested ads.
- Marketer features: create offered ads, link to matching FindIt requests, auto-match suggestions, push notifications on matches, ability to create ad similar to a FindIt request.

## 10. Trust & Security
- Seller verification: identity documents, commercial record for dealers, admin verification workflow.
- Ratings & reviews; reports system for fraud/content violations.
- Trusted Car / Trusted Dealer badges based on verification criteria.
- Integrations: Carseer or alternative for inspection reports (display or paid access).

## 11. App Features
- Save favorites, compare cars (side-by-side specs), push notifications, dark mode, in-app messaging (optional), quick contact via WhatsApp.

## 12. Admin Panel (Capabilities)
- Manage: Ads, Packages, Users, Dealers, Cities, Dynamic Fields, Categories, Notifications.
- Send notifications to individuals, specific users, dealers, or all users.
- Feature toggles for AI, Caishha, Auction, FindIt, Facebook auto-posting.

## 13. Premium Packages & Ads Features (Detailed)

### 13.1 Publishing & Promotion
- Auto-post to Facebook (text + link) for eligible packages; admin controls message template.

### 13.2 Ad Quantity Control
- Programmatic limits per package (normal/featured counts enforced by backend).

### 13.3 Reposting & Ordering
- Auto-repost frequency configurable per package (e.g., every 2 or 3 days) to move ad to top of listings; respects platform visibility rules.

### 13.4 Special Services (Caishha & FindIt)
- Subscribe to Caishha / FindIt via package entitlements; admin can enable per package and set limits.

### 13.5 Promotional Video Creation
- Generate social-media-ready video from ad media via external API (e.g., Vida API), limited by package feature count.

### 13.6 Verification & Inspection Reports
- Attach Carseer inspection report (or alternative) to ad — display inline or sell as an add-on.

### 13.7 Visual Highlighting & Templates
- Visual distinction per package (colors, borders, icons) and selectable dealer page templates enabled via admin.

## 14. Notes & Controls
- All features are tied to packages and fully controllable from the admin panel (enable/disable, limit counts/duration).
- Admin must have clear UI for turning features on/off per-country and per-package, and for setting limits for each characteristic.

---

File created: `Registration and Ads System Requirements.md` — tell me if you want this converted into Laravel migration schemata, API endpoints, or an ER diagram next.
