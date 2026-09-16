"use client";

import Link from "next/link";

/**
 * Global error boundary. Without this, an uncaught render error in any route
 * shows Next.js' generic "Application error" screen — a hard dead-end for both
 * visitors and crawlers. A real page keeps the response useful and gives the
 * visitor a way back into the catalog.
 *
 * Must be a Client Component (Next.js requirement) and receives `reset` to
 * retry rendering the segment without a full page reload.
 *
 * Styled inline with an inline SVG icon: like `not-found.tsx`, this renders in
 * Next.js' bare error shell where neither the global stylesheet nor the Font
 * Awesome CDN link is present.
 */
export default function GlobalError({
  error,
  reset,
}: Readonly<{ error: Error & { digest?: string }; reset: () => void }>) {
  return (
    <main
      style={{
        minHeight: "100vh",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: "48px 20px",
        background: "#ffffff",
        fontFamily:
          "'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
        margin: 0,
      }}
    >
      <div style={{ maxWidth: 560, textAlign: "center" }}>
        <svg
          width="60"
          height="60"
          viewBox="0 0 24 24"
          fill="none"
          stroke="#EE5A24"
          strokeWidth="1.6"
          strokeLinecap="round"
          strokeLinejoin="round"
          aria-hidden="true"
          style={{ marginBottom: 18 }}
        >
          <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
          <line x1="12" y1="9" x2="12" y2="13" />
          <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
        <h1
          style={{
            fontSize: "1.6rem",
            fontWeight: 700,
            color: "#0F172A",
            margin: "0 0 0.5rem",
          }}
        >
          Beklenmeyen Bir Hata Oluştu
        </h1>
        <p
          style={{
            color: "#475569",
            margin: "0 0 1.5rem",
            fontSize: "0.95rem",
            lineHeight: 1.6,
          }}
        >
          Sayfa yüklenirken bir sorun yaşandı. Tekrar denemeyi veya ana sayfaya
          dönmeyi tercih edebilirsiniz.
        </p>

        {error.digest === undefined ? null : (
          <p
            style={{
              fontSize: "0.75rem",
              color: "#94a3b8",
              margin: "0 0 1.25rem",
              fontFamily: "monospace",
            }}
          >
            Hata kodu: {error.digest}
          </p>
        )}

        <div
          style={{
            display: "flex",
            gap: "0.6rem",
            justifyContent: "center",
            flexWrap: "wrap",
          }}
        >
          <button
            type="button"
            onClick={reset}
            style={{
              padding: "12px 24px",
              borderRadius: 10,
              background: "#EE5A24",
              color: "#ffffff",
              fontWeight: 600,
              fontSize: "0.92rem",
              border: "none",
              cursor: "pointer",
            }}
          >
            Tekrar Dene
          </button>
          <Link
            href="/tum-hesaplar"
            style={{
              padding: "12px 24px",
              borderRadius: 10,
              border: "1px solid #e2e8f0",
              background: "#ffffff",
              color: "#0F172A",
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
              border: "1px solid #e2e8f0",
              background: "#ffffff",
              color: "#0F172A",
              fontWeight: 600,
              fontSize: "0.92rem",
              textDecoration: "none",
            }}
          >
            Ana Sayfa
          </Link>
        </div>
      </div>
    </main>
  );
}
