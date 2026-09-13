import type { MetadataRoute } from "next";
import { SITE_URL } from "@/lib/seo/slugs";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: "*",
        allow: "/",
        disallow: [
          "/admin/",
          "/dashboard/",
          "/cart",
          "/checkout",
          "/order",
          "/orders",
          "/sepet",
          "/odeme",
          "/hesabim",
          "/2fa",
          "/erisim-engellendi",
          "/giris-yap",
          "/kayit-ol",
          "/api/",
          "/legacy/",
        ],
      },
      { userAgent: "GPTBot", allow: "/" },
      { userAgent: "ChatGPT-User", allow: "/" },
      { userAgent: "Googlebot", allow: "/" },
      { userAgent: "Bingbot", allow: "/" },
    ],
    sitemap: `${SITE_URL}/sitemap.xml`,
    host: SITE_URL,
  };
}
