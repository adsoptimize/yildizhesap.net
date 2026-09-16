import type { MetadataRoute } from "next";
import { SITE_URL } from "@/lib/seo/slugs";

/**
 * Private routes never leak to any crawler.
 */
const DISALLOW = [
  "/admin",
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
] as const;

/**
 * Explicit allow-list for AI answer engine and generative training crawlers.
 * See: openai.com/gptbot, google-extended, perplexity.ai/robotstxt,
 * darkvisitors.com bot list. Adding an explicit entry gives us a clear audit
 * signal and makes it easy to block one later without touching the wildcard
 * rule.
 */
const AI_BOTS = [
  "GPTBot",
  "ChatGPT-User",
  "OAI-SearchBot",
  "Google-Extended",
  "PerplexityBot",
  "Perplexity-User",
  "ClaudeBot",
  "Claude-Web",
  "anthropic-ai",
  "CCBot",
  "cohere-ai",
  "cohere-training-data-crawler",
  "YouBot",
  "Applebot-Extended",
  "Bytespider",
  "Meta-ExternalAgent",
  "Meta-ExternalFetcher",
  "FacebookBot",
  "Amazonbot",
  "CopilotBot",
  "MistralAI-User",
  "Diffbot",
  "omgili",
  "DuckAssistBot",
] as const;

/** Traditional search engines. */
const SEARCH_BOTS = [
  "Googlebot",
  "Googlebot-Image",
  "Bingbot",
  "DuckDuckBot",
  "Yandex",
] as const;

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      { userAgent: "*", allow: "/", disallow: [...DISALLOW] },
      ...SEARCH_BOTS.map((agent) => ({
        userAgent: agent,
        allow: "/",
        disallow: [...DISALLOW],
      })),
      ...AI_BOTS.map((agent) => ({
        userAgent: agent,
        allow: "/",
        disallow: [...DISALLOW],
      })),
    ],
    sitemap: `${SITE_URL}/sitemap.xml`,
    host: SITE_URL,
  };
}
