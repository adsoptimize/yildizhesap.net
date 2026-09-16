import type { NextConfig } from "next";
import { getNextConfigRedirects } from "./src/lib/seo/redirects";

const nextConfig: NextConfig = {
  async redirects() {
    return getNextConfigRedirects();
  },
  async headers() {
    return [
      {
        // Vercel already sends HSTS; these are the ones it does not.
        // No Content-Security-Policy here on purpose: the layout loads Font
        // Awesome from a CDN and every page inlines a JSON-LD script, so a
        // policy strict enough to be worth having needs its own pass with
        // report-only first.
        source: "/:path*",
        headers: [
          // The storefront is never meant to be framed, and it has login and
          // checkout flows worth protecting from clickjacking.
          { key: "X-Frame-Options", value: "DENY" },
          { key: "X-Content-Type-Options", value: "nosniff" },
          // Send the full URL within our own origin, bare origin elsewhere,
          // so payment providers never receive order codes in the referrer.
          {
            key: "Referrer-Policy",
            value: "strict-origin-when-cross-origin",
          },
          // Nothing on the site uses these; denying them limits what an
          // injected script could reach for.
          {
            key: "Permissions-Policy",
            value: "camera=(), microphone=(), geolocation=(), payment=()",
          },
        ],
      },
      {
        // `/.well-known/ai-preferences` has no file extension, so Vercel would
        // otherwise serve it as a binary download instead of text.
        source: "/.well-known/:file(ai-preferences|ai.txt)",
        headers: [
          { key: "Content-Type", value: "text/plain; charset=utf-8" },
          { key: "Cache-Control", value: "public, max-age=86400" },
        ],
      },
    ];
  },
};

export default nextConfig;
