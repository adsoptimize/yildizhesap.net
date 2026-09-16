import type { Metadata } from "next";
import Link from "next/link";

/**
 * Global 404.
 *
 * Deliberately self-contained: because the storefront's only catch-all route
 * is the top-level dynamic segment `[slug]`, Next.js cannot compose this page
 * inside the root layout and instead renders it in its bare error shell
 * (`<html id="__next_error__">`). No global stylesheet, font, or Font Awesome
 * icon reaches it, so every style here is inline and the icon is inline SVG.
 *
 * The documented alternative is `app/global-not-found.tsx`, which requires the
 * experimental `globalNotFound` flag — not worth enabling in production for a
 * 404 page.
 *
 * Links are hardcoded so a database outage can never turn a 404 into a 500.
 */

export const metadata: Metadata = {
  title: { absolute: "Sayfa Bulunamadı (404) | YildizHesap" },
  description:
    "Aradığınız sayfa taşınmış veya kaldırılmış olabilir. Hesap kategorilerine göz atın.",
  robots: { index: false, follow: true },
};

const BRAND = "#EE5A24";
const INK = "#0F172A";
const MUTED = "#475569";
const BORDER = "#E2E8F0";

const POPULAR_LINKS = [
  { href: "/tum-hesaplar", label: "Tüm Hesaplar" },
  { href: "/facebook-hesaplari", label: "Facebook Hesapları" },
  { href: "/instagram-hesaplari", label: "Instagram Hesapları" },
  { href: "/business-manager", label: "Business Manager" },
  { href: "/tiktok-hesaplari", label: "TikTok Hesapları" },
  { href: "/telegram-hesaplari", label: "Telegram Hesapları" },
  { href: "/sikca-sorulan-sorular", label: "Sıkça Sorulan Sorular" },
  { href: "/siparis-takip", label: "Sipariş Takip" },
  { href: "/iletisim", label: "İletişim" },
] as const;

export default function GlobalNotFound() {
  return (
    <main
      style={{
        minHeight: "100vh",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: "48px 20px",
        background: "#FFFFFF",
        fontFamily:
          "'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
        margin: 0,
      }}
    >
      <div style={{ maxWidth: 640, width: "100%", textAlign: "center" }}>
        <svg
          width="64"
          height="64"
          viewBox="0 0 24 24"
          fill="none"
          stroke={BRAND}
          strokeWidth="1.6"
          strokeLinecap="round"
          strokeLinejoin="round"
          aria-hidden="true"
          style={{ marginBottom: 20 }}
        >
          <circle cx="11" cy="11" r="7" />
          <line x1="16.5" y1="16.5" x2="21" y2="21" />
          <line x1="8.5" y1="8.5" x2="13.5" y2="13.5" />
          <line x1="13.5" y1="8.5" x2="8.5" y2="13.5" />
        </svg>

        <p
          style={{
            fontSize: "3.25rem",
            fontWeight: 800,
            color: BRAND,
            margin: 0,
            lineHeight: 1,
            letterSpacing: "-0.02em",
          }}
        >
          404
        </p>

        <h1
          style={{
            fontSize: "1.55rem",
            fontWeight: 700,
            color: INK,
            margin: "12px 0 8px",
          }}
        >
          Sayfa Bulunamadı
        </h1>

        <p
          style={{
            color: MUTED,
            margin: "0 0 28px",
            fontSize: "0.95rem",
            lineHeight: 1.6,
          }}
        >
          Aradığınız sayfa taşınmış, adı değişmiş veya hiç var olmamış olabilir.
          Aşağıdaki bölümlerden devam edebilirsiniz.
        </p>

        <div
          style={{
            display: "flex",
            gap: 10,
            justifyContent: "center",
            flexWrap: "wrap",
            marginBottom: 32,
          }}
        >
          <Link
            href="/tum-hesaplar"
            style={{
              padding: "12px 24px",
              borderRadius: 10,
              background: BRAND,
              color: "#FFFFFF",
              fontWeight: 600,
              fontSize: "0.92rem",
              textDecoration: "none",
            }}
          >
            Hesapları İncele
          </Link>
          <Link
            href="/"
            style={{
              padding: "12px 24px",
              borderRadius: 10,
              border: `1px solid ${BORDER}`,
              background: "#FFFFFF",
              color: INK,
              fontWeight: 600,
              fontSize: "0.92rem",
              textDecoration: "none",
            }}
          >
            Ana Sayfa
          </Link>
        </div>

        <div
          style={{
            paddingTop: 24,
            borderTop: `1px solid ${BORDER}`,
          }}
        >
          <p
            style={{
              fontSize: "0.78rem",
              fontWeight: 600,
              color: MUTED,
              textTransform: "uppercase",
              letterSpacing: "0.06em",
              margin: "0 0 14px",
            }}
          >
            Popüler Bölümler
          </p>
          <nav
            style={{
              display: "flex",
              gap: 8,
              flexWrap: "wrap",
              justifyContent: "center",
            }}
          >
            {POPULAR_LINKS.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                style={{
                  padding: "7px 14px",
                  borderRadius: 999,
                  border: `1px solid ${BORDER}`,
                  background: "#F8FAFC",
                  color: INK,
                  fontSize: "0.83rem",
                  fontWeight: 500,
                  textDecoration: "none",
                }}
              >
                {link.label}
              </Link>
            ))}
          </nav>
        </div>
      </div>
    </main>
  );
}
