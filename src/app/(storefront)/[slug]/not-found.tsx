import Link from "next/link";

/**
 * Segment-level 404 for the storefront catch-all route. Placed inside
 * `[slug]` (rather than at the route-group root) so Next.js resolves it as
 * the nearest boundary to the `notFound()` call in `page.tsx` and keeps the
 * surrounding layout chain — header, footer, and `shop.css`.
 *
 * Links are hardcoded: a 404 must not depend on a database round-trip.
 */

const POPULAR_LINKS = [
  { href: "/tum-hesaplar", label: "Tüm Hesaplar", icon: "fas fa-store" },
  {
    href: "/facebook-hesaplari",
    label: "Facebook Hesapları",
    icon: "fab fa-facebook",
  },
  {
    href: "/instagram-hesaplari",
    label: "Instagram Hesapları",
    icon: "fab fa-instagram",
  },
  {
    href: "/business-manager",
    label: "Business Manager",
    icon: "fas fa-briefcase",
  },
  { href: "/tiktok-hesaplari", label: "TikTok Hesapları", icon: "fab fa-tiktok" },
  {
    href: "/telegram-hesaplari",
    label: "Telegram Hesapları",
    icon: "fab fa-telegram",
  },
  {
    href: "/sikca-sorulan-sorular",
    label: "Sıkça Sorulan Sorular",
    icon: "fas fa-circle-question",
  },
  {
    href: "/siparis-takip",
    label: "Sipariş Takip",
    icon: "fas fa-magnifying-glass",
  },
  { href: "/iletisim", label: "İletişim", icon: "fas fa-headset" },
] as const;

export default function SlugNotFound() {
  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>Sayfa Bulunamadı</h1>
          <p>
            Aradığınız sayfa taşınmış, adı değişmiş veya hiç var olmamış
            olabilir.
          </p>
        </div>
      </section>

      <section className="shop-section">
        <div className="container">
          <div className="shop-panel">
            <div className="shop-empty">
              <i className="fas fa-compass" aria-hidden="true" />
              <h3>404 — Bu adres artık geçerli değil</h3>
              <p className="shop-muted">
                Eski bir bağlantıdan geldiyseniz sayfa yeni adrese taşınmış
                olabilir.
              </p>
            </div>

            <div className="shop-nav" style={{ marginTop: 24 }}>
              {POPULAR_LINKS.map((link) => (
                <Link key={link.href} href={link.href}>
                  <i className={link.icon} aria-hidden="true" /> {link.label}
                </Link>
              ))}
            </div>

            <div className="shop-actions" style={{ marginTop: 24 }}>
              <Link href="/tum-hesaplar" className="shop-btn accent">
                <i className="fas fa-store" aria-hidden="true" /> Hesapları
                İncele
              </Link>
              <Link href="/" className="shop-btn">
                <i className="fas fa-house" aria-hidden="true" /> Ana Sayfa
              </Link>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
