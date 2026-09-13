import {
  CATEGORY_SEO_SLUGS,
  PRODUCT_SEO_SLUGS,
} from "./slugs";

export type NextRedirect = {
  source: string;
  destination: string;
  permanent: boolean;
  has?: Array<{ type: "query"; key: string; value: string }>;
};

/**
 * Exact 301 map from legacy/.htaccess — preserve for Google equity.
 */
export function getNextConfigRedirects(): NextRedirect[] {
  const redirects: NextRedirect[] = [
    {
      source: "/guest_order_track.php",
      destination: "/siparis-takip",
      permanent: true,
    },
    { source: "/sss.php", destination: "/sikca-sorulan-sorular", permanent: true },
    { source: "/hizmetler.php", destination: "/hizmetler", permanent: true },
    { source: "/iletisim.php", destination: "/iletisim", permanent: true },
    { source: "/index.php", destination: "/", permanent: true },
    { source: "/kvvk.php", destination: "/kvkk", permanent: true },
    { source: "/login.php", destination: "/giris-yap", permanent: true },
    { source: "/register.php", destination: "/kayit-ol", permanent: true },
    {
      source: "/privacy.php",
      destination: "/gizlilik-politikasi",
      permanent: true,
    },
    {
      source: "/sitemap_dynamic.php",
      destination: "/sitemap.xml",
      permanent: true,
    },
    {
      source: "/terms.php",
      destination: "/kullanim-kosullari",
      permanent: true,
    },
    { source: "/cookies.php", destination: "/cerez-politikasi", permanent: true },
    { source: "/refund.php", destination: "/iade-politikasi", permanent: true },
    { source: "/about.php", destination: "/hakkimizda", permanent: true },
    // Shop and member area pages: no SEO value, but old bookmarks should work.
    { source: "/2fa.php", destination: "/2fa", permanent: true },
    { source: "/banned.php", destination: "/erisim-engellendi", permanent: true },
    { source: "/cart.php", destination: "/sepet", permanent: true },
    { source: "/checkout.php", destination: "/odeme", permanent: true },
    { source: "/guest_purchase.php", destination: "/guest-purchase", permanent: true },
    { source: "/urunlerim.php", destination: "/hesabim", permanent: true },
    { source: "/hesaplarim.php", destination: "/hesabim", permanent: true },
    { source: "/ayarlar.php", destination: "/hesabim/ayarlar", permanent: true },
    {
      source: "/payment-success.php",
      destination: "/odeme/basarili",
      permanent: true,
    },
    {
      source: "/payment-cancel.php",
      destination: "/odeme/iptal",
      permanent: true,
    },
  ];

  for (const [id, slug] of Object.entries(CATEGORY_SEO_SLUGS)) {
    redirects.push({
      source: "/hesaplar.php",
      has: [{ type: "query", key: "category", value: String(id) }],
      destination: `/${slug}`,
      permanent: true,
    });
  }

  redirects.push({
    source: "/hesaplar.php",
    destination: "/tum-hesaplar",
    permanent: true,
  });

  for (const [id, slug] of Object.entries(PRODUCT_SEO_SLUGS)) {
    redirects.push({
      source: "/view.php",
      has: [{ type: "query", key: "id", value: String(id) }],
      destination: `/${slug}`,
      permanent: true,
    });
  }

  return redirects;
}
