/**
 * Blocks banned IP addresses before any page renders — the job legacy/header.php
 * did with IPBanManager on every request. The admin panel and the block page
 * itself stay reachable so a banned operator can still unban themselves.
 */

import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";
import { getActiveBan } from "@/lib/security/ip-ban";

const UNKNOWN_IP = "unknown";
const BANNED_PATH = "/erisim-engellendi";

export const config = {
  matcher: [
    "/((?!api|admin|erisim-engellendi|_next/static|_next/image|images|css|assets|favicon.ico|robots.txt|sitemap.xml|llms.txt).*)",
  ],
};

function readClientIp(request: NextRequest): string {
  const forwardedFor = request.headers.get("x-forwarded-for");

  return (
    forwardedFor?.split(",")[0]?.trim() ??
    request.headers.get("x-real-ip") ??
    UNKNOWN_IP
  );
}

export async function proxy(request: NextRequest): Promise<NextResponse> {
  const ipAddress = readClientIp(request);

  if (ipAddress === UNKNOWN_IP) {
    return NextResponse.next();
  }

  const ban = await getActiveBan(ipAddress);

  if (ban === null) {
    return NextResponse.next();
  }

  return NextResponse.redirect(new URL(BANNED_PATH, request.url));
}
