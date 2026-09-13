import type { MetadataRoute } from "next";
import { getPrerenderableSlugs } from "@/lib/db/queries";
import { SITE_URL, STATIC_SEO_ROUTES } from "@/lib/seo/slugs";

// Segment config must be a literal; keep in sync with the other storefront routes.
export const revalidate = 300;

const AUTH_ROUTES = new Set(["giris-yap", "kayit-ol"]);

// Static thumbnail served with the site until per-product images are uploaded.
// Google Image sitemap requires absolute URLs.
const DEFAULT_IMAGE = `${SITE_URL}/images/mockup.png`;
const OG_IMAGE = `${SITE_URL}/og-image.jpg`;

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const staticEntries: MetadataRoute.Sitemap = [
    {
      url: `${SITE_URL}/`,
      changeFrequency: "daily",
      priority: 1,
      images: [OG_IMAGE],
    },
    ...STATIC_SEO_ROUTES.filter((route) => !AUTH_ROUTES.has(route)).map(
      (route) => ({
        url: `${SITE_URL}/${route}`,
        changeFrequency: "weekly" as const,
        priority: 0.8,
        images: [DEFAULT_IMAGE],
      }),
    ),
  ];

  // Only live rows are listed; missing products would otherwise report as 404s.
  const dynamicEntries: MetadataRoute.Sitemap = (
    await getPrerenderableSlugs()
  ).map((slug) => ({
    url: `${SITE_URL}/${slug}`,
    changeFrequency: "daily" as const,
    priority: 0.9,
    images: [DEFAULT_IMAGE],
  }));

  return [...staticEntries, ...dynamicEntries];
}
