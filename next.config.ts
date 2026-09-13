import type { NextConfig } from "next";
import { getNextConfigRedirects } from "./src/lib/seo/redirects";

const nextConfig: NextConfig = {
  async redirects() {
    return getNextConfigRedirects();
  },
};

export default nextConfig;
