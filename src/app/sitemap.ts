import type { MetadataRoute } from "next";
import {
  CATEGORY_SEO_SLUGS,
  PRODUCT_SEO_SLUGS,
  SITE_URL,
  STATIC_SEO_ROUTES,
} from "@/lib/seo/slugs";

export default function sitemap(): MetadataRoute.Sitemap {
  const staticEntries: MetadataRoute.Sitemap = [
    {
      url: `${SITE_URL}/`,
      changeFrequency: "daily",
      priority: 1,
    },
    ...STATIC_SEO_ROUTES.filter(
      (route) => route !== "giris-yap" && route !== "kayit-ol",
    ).map((route) => ({
      url: `${SITE_URL}/${route}`,
      changeFrequency: "weekly" as const,
      priority: 0.8,
    })),
  ];

  const categoryEntries: MetadataRoute.Sitemap = Object.values(
    CATEGORY_SEO_SLUGS,
  ).map((slug) => ({
    url: `${SITE_URL}/${slug}`,
    changeFrequency: "daily" as const,
    priority: 0.9,
  }));

  const productEntries: MetadataRoute.Sitemap = Object.values(
    PRODUCT_SEO_SLUGS,
  ).map((slug) => ({
    url: `${SITE_URL}/${slug}`,
    changeFrequency: "weekly" as const,
    priority: 0.7,
  }));

  return [...staticEntries, ...categoryEntries, ...productEntries];
}
