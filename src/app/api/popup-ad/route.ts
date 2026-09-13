/**
 * The legacy header rendered the popup inline and reported the view through
 * ajax_popup_view.php. Storefront pages are statically rendered here, so the
 * decision moved into this endpoint and the browser reports back with POST.
 */

import { NextResponse } from "next/server";
import { getClientIp, getSessionUser } from "@/lib/auth/session";
import { recordPopupView, resolvePopupAd } from "@/lib/ads/popup";

export const dynamic = "force-dynamic";

export type PopupAdResponse = {
  content: string | null;
  contentHash: string | null;
};

export async function GET(): Promise<NextResponse<PopupAdResponse>> {
  const user = await getSessionUser();
  const popup = await resolvePopupAd(user?.id ?? null);

  return NextResponse.json(
    {
      content: popup?.content ?? null,
      contentHash: popup?.contentHash ?? null,
    },
    { headers: { "cache-control": "no-store" } },
  );
}

export async function POST(request: Request): Promise<NextResponse> {
  const body: unknown = await request.json().catch(() => null);

  if (typeof body !== "object" || body === null) {
    return NextResponse.json({ ok: false }, { status: 400 });
  }

  const contentHash = (body as Record<string, unknown>).contentHash;

  if (typeof contentHash !== "string" || contentHash === "") {
    return NextResponse.json({ ok: false }, { status: 400 });
  }

  const [user, ipAddress] = await Promise.all([getSessionUser(), getClientIp()]);

  await recordPopupView(user?.id ?? null, contentHash, ipAddress);

  return NextResponse.json({ ok: true });
}
