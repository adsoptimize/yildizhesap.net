import type { MetadataRoute } from "next";
import { getSitemapEntries } from "@/lib/db/queries";
import { SITE_URL, STATIC_SEO_ROUTES } from "@/lib/seo/slugs";

// Segment config must be a literal; keep in sync with the other storefront routes.
export const revalidate = 300;

const AUTH_ROUTES = new Set(["giris-yap", "kayit-ol"]);

// Static thumbnail served with the site until per-product images are uploaded.
// Google Image sitemap requires absolute URLs.
const DEFAULT_IMAGE = `${SITE_URL}/images/mockup.png`;
const OG_IMAGE = `${SITE_URL}/og-image.jpg`;

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  // Freshness signal that flips daily so Google/AI answer engines re-crawl
  // the storefront homepage even if we don't push a code change. Dynamic rows
  // carry their own `updatedAt` timestamps below.
  const buildTime = new Date();

  const staticEntries: MetadataRoute.Sitemap = [
    {
      url: `${SITE_URL}/`,
      lastModified: buildTime,
      changeFrequency: "daily",
      priority: 1,
      images: [OG_IMAGE],
    },
    ...STATIC_SEO_ROUTES.filter((route) => !AUTH_ROUTES.has(route)).map(
      (route) => ({
        url: `${SITE_URL}/${route}`,
        lastModified: buildTime,
        changeFrequency: "weekly" as const,
        priority: 0.8,
        images: [DEFAULT_IMAGE],
      }),
    ),
  ];

  // Only live rows are listed; missing products would otherwise report as 404s.
  const dynamicEntries: MetadataRoute.Sitemap = (
    await getSitemapEntries()
  ).map((entry) => ({
    url: `${SITE_URL}/${entry.seoSlug}`,
    lastModified: entry.updatedAt,
    changeFrequency: "daily" as const,
    priority: entry.kind === "product" ? 0.9 : 0.85,
    images: [DEFAULT_IMAGE],
  }));

  // A category and a product can end up sharing a `seoSlug`, in which case
  // the dynamic rows above would emit the same <loc> twice. Only one of the
  // two is actually reachable (the route resolves categories first), and a
  // sitemap that repeats a URL is a quality signal working against us. Keep
  // the first entry for a URL: static routes outrank dynamic ones, and
  // categories are listed before products.
  const seen = new Set<string>();

  return [...staticEntries, ...dynamicEntries].filter((entry) => {
    if (seen.has(entry.url)) {
      return false;
    }
    seen.add(entry.url);
    return true;
  });
}
