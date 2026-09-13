/**
 * Absolute URLs for payment callbacks. The host is read from the incoming
 * request so preview deployments call back to themselves instead of production.
 */

import { headers } from "next/headers";
import { SITE_URL } from "@/lib/seo/slugs";

export async function getAbsoluteUrl(path: string): Promise<string> {
  const headerList = await headers();
  const host =
    headerList.get("x-forwarded-host") ?? headerList.get("host") ?? "";

  if (host === "") {
    return `${SITE_URL}${path}`;
  }

  const protocol = headerList.get("x-forwarded-proto") ?? "https";

  return `${protocol}://${host}${path}`;
}
