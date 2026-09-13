import type { MetadataRoute } from "next";
import { getPrerenderableSlugs } from "@/lib/db/queries";
import { SITE_URL, STATIC_SEO_ROUTES } from "@/lib/seo/slugs";

// Segment config must be a literal; keep in sync with the other storefront routes.
export const revalidate = 300;

const AUTH_ROUTES = new Set(["giris-yap", "kayit-ol"]);

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const staticEntries: MetadataRoute.Sitemap = [
    {
      url: `${SITE_URL}/`,
      changeFrequency: "daily",
      priority: 1,
    },
    ...STATIC_SEO_ROUTES.filter((route) => !AUTH_ROUTES.has(route)).map(
      (route) => ({
        url: `${SITE_URL}/${route}`,
        changeFrequency: "weekly" as const,
        priority: 0.8,
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
  }));

  return [...staticEntries, ...dynamicEntries];
}
