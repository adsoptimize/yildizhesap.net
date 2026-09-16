import type { NextConfig } from "next";
import { getNextConfigRedirects } from "./src/lib/seo/redirects";

const nextConfig: NextConfig = {
  async redirects() {
    return getNextConfigRedirects();
  },
  async headers() {
    return [
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
