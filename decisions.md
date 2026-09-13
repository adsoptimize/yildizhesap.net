# Architecture decisions — YildizHesap Next.js migration

## 2026-09-13 — Neon Postgres + Next.js on Vercel

- **Decision:** Rebuild the PHP monolith as Next.js (App Router) for GitHub → Vercel CI/CD.
- **Database:** Neon Postgres (Vercel Marketplace). Not MySQL-on-shared-hosting.
- **ORM:** Prisma 6 with schema ported from `legacy/businesshesap.sql` plus production columns (`seo_slug`, account feature flags).
- **SEO lock:** Legacy URL slugs, meta titles, and `.htaccess` 301s are frozen in `src/lib/seo/*` and `next.config.ts` redirects. Ranking keyword target: homepage title for “facebook hesap / reklam hesabı satın al”.
- **Legacy code:** Original PHP lives under `legacy/` on branch `nextjs-migration` for reference until feature parity + cutover.
- **Out of scope for Phase 1:** Live DB migrate, payment webhooks, full UI, admin panel UI (schema only).
- **Migration rule:** Do not run `prisma migrate` against production without explicit approval.
