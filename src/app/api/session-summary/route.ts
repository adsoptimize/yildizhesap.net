/**
 * The storefront layout is statically rendered, so the header cannot read the
 * session or cart cookie during the build. It fetches this endpoint on mount
 * instead — the same data the legacy header rendered inline from $_SESSION.
 */

import { NextResponse } from "next/server";
import { getSessionUser } from "@/lib/auth/session";
import { getCartQuantity } from "@/lib/shop/cart";

export const dynamic = "force-dynamic";

export type SessionSummary = {
  firstName: string | null;
  isAdmin: boolean;
  cartQuantity: number;
};

export async function GET(): Promise<NextResponse<SessionSummary>> {
  const [user, cartQuantity] = await Promise.all([
    getSessionUser(),
    getCartQuantity(),
  ]);

  return NextResponse.json(
    {
      firstName: user?.firstName ?? null,
      isAdmin: user?.isAdmin ?? false,
      cartQuantity,
    },
    { headers: { "cache-control": "no-store" } },
  );
}
