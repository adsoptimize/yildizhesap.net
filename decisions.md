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

## 2026-09-17 — Credential delivery: per-account .txt files and SMTP e-mail

- **File layout single-sourced:** `src/lib/shop/credentials-file.ts` builds every credential file. It stays free of server-only imports so the guest tracking page can build the file in the browser while the member route builds it on the server, and both produce identical output. Line format is the legacy `HESAPn | user:pass:email:emailpass:2fa:date`, with trailing empty fields trimmed.
- **One file per account:** `/hesabim/siparis/[code]/indir?hesap=N` (1-based, matching the row numbers on screen) returns a single account; omitting the parameter returns the whole order as before, so existing links keep working.
- **Guests build locally:** The tracking page has no session to authorise a download endpoint with and already holds the credentials in client state, so it generates the file via `Blob` instead of gaining an unauthenticated route.
- **Support handle travels with the file:** Every credential file and delivery e-mail carries the Telegram handle from `contact_settings.telegram_username`, so a customer who hits a problem has a contact inside the artefact they downloaded. Read through `src/lib/shop/support-contact.ts`.
- **Tracking page stays static:** The handle is returned by the `trackOrderAction` server action, which already queries the database, rather than read in the page — reading it in the page would have forced `/siparis-takip` to render dynamically.
- **E-mail is additive, never blocking:** `src/lib/notify/email.ts` follows the `notify/telegram.ts` contract: settings in `crypto_settings`, a no-op when unconfigured, and it never throws so a failed message cannot roll back a paid and delivered order. Credentials are attached as one .txt per account.
- **Hence the SMTP test:** Because send failures are deliberately silent, a misconfiguration would otherwise be invisible until a real purchase. The payment settings screen has a test-send form (`sendTestEmailAction`) as the way to verify the saved settings.
- **Port implies TLS:** 465 turns on implicit TLS automatically, other ports use STARTTLS unless `smtp_secure` is set, so filling in only host/user/password yields a working configuration.
