# Architecture decisions — YildizHesap Next.js migration

## 2026-09-13 — Neon Postgres + Next.js on Vercel

- **Decision:** Rebuild the PHP monolith as Next.js (App Router) for GitHub → Vercel CI/CD.
- **Database:** Neon Postgres (Vercel Marketplace). Not MySQL-on-shared-hosting.
- **ORM:** Prisma 6 with schema ported from `legacy/businesshesap.sql` plus production columns (`seo_slug`, account feature flags).
- **SEO lock:** Legacy URL slugs, meta titles, and `.htaccess` 301s are frozen in `src/lib/seo/*` and `next.config.ts` redirects. Ranking keyword target: homepage title for “facebook hesap / reklam hesabı satın al”.
- **Legacy code:** Original PHP lives under `legacy/` on branch `nextjs-migration` for reference until feature parity + cutover.
- **Out of scope for Phase 1:** Live DB migrate, payment webhooks, full UI, admin panel UI (schema only).
- **Migration rule:** Do not run `prisma migrate` against production without explicit approval.

## 2026-09-13 — Data layer and storefront on Neon

- **Lost catalog:** The live MySQL database is gone and `legacy/businesshesap.sql` is July 2025 demo data. Categories, FAQs, legal pages, and contact/site settings were restored; **product rows are entered manually through the admin panel** at the owner's request.
- **Category ids frozen:** Seeded with the legacy ids (7, 8, 12–16, 20–22, 27) because `seo_slug` values behind indexed URLs are keyed to them. `slug` mirrors `seo_slug`.
- **Content source:** `prisma/legacy-dump.ts` parses the archived dump for `faqs`, `legal_pages`, and `contact_settings`; double-encoded UTF-8 rows are repaired on read. Branding/menu settings are re-seeded from `src/lib/catalog/defaults.ts` with the new SEO routes, not from the dump's `.php` URLs.
- **Rendering:** Storefront pages are ISR with `revalidate = 300` (literal, since Next requires statically analyzable segment config) and flushed on admin writes via `revalidateStorefront()`.
- **Sitemap:** Generated from live DB rows only, so unpublished product URLs are never advertised as 404s.
- **Route groups:** `src/app/(storefront)` owns the public header/footer; `src/app/admin` has its own bare layout with `robots: noindex`. URLs are unchanged.

## 2026-09-13 — Admin panel parity

- **Auth:** Node `crypto` scrypt hashes (`scrypt:<salt>:<hash>`) plus DB-backed sessions in the legacy `active_sessions` table, cookie `yh_session`, 12 hour TTL. No auth library added.
- **Admin bootstrap:** `npm run admin:create` reads credentials from the environment so passwords never enter the repo or shell history.
- **Sections:** All 13 legacy sidebar entries rebuilt (dashboard, site settings, accounts, categories, users, orders, support, analytics, payment settings, advertisements, IP limits, legal pages, sessions).
- **Secrets:** Payment keys are stored in `crypto_settings`, rendered masked, and left untouched when the field is submitted blank.
- **Stock:** `account_stock` rows drive `accounts.stock_quantity`; the counter is recomputed after every stock write instead of being edited by hand.
