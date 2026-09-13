/**
 * Cryptomus payment callback. Ported from legacy/cryptomus_webhook.php, with the
 * signature computed over the payload without the `sign` field as Cryptomus
 * documents (the legacy code hashed the payload including `sign`).
 */

import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import {
  mapCryptomusStatus,
  verifyCryptomusWebhook,
} from "@/lib/payments/cryptomus";
import { getCryptomusPaymentKey } from "@/lib/payments/settings";
import { confirmPayment, markPaymentFailed } from "@/lib/shop/orders";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

const WEBHOOK_LOG_SOURCE = "cryptomus";

async function logWebhook(payload: string, status: string): Promise<void> {
  try {
    await prisma.cryptoWebhookLog.create({
      data: { payload, headers: WEBHOOK_LOG_SOURCE, status },
    });
  } catch (error: unknown) {
    console.error("Webhook logu yazılamadı", error);
  }
}

export async function POST(request: Request): Promise<NextResponse> {
  const rawBody = await request.text();
  const paymentKey = await getCryptomusPaymentKey();
  const payload = verifyCryptomusWebhook(rawBody, paymentKey);

  if (payload === null) {
    await logWebhook(rawBody, "invalid_signature");
    return NextResponse.json({ status: "invalid" }, { status: 400 });
  }

  const outcome = mapCryptomusStatus(payload.status);

  if (outcome === "paid") {
    const result = await confirmPayment({
      orderCode: payload.orderId,
      paidAmount: payload.paymentAmount,
      txid: payload.txid,
      cryptomusUuid: payload.uuid,
    });

    await logWebhook(rawBody, `paid:${result}`);
    return NextResponse.json({ status: "ok", result });
  }

  if (outcome === "failed" || outcome === "expired") {
    await markPaymentFailed(
      payload.orderId,
      outcome === "expired" ? "expired" : "failed",
    );
    await logWebhook(rawBody, outcome);
    return NextResponse.json({ status: "ok" });
  }

  await logWebhook(rawBody, `pending:${payload.status}`);
  return NextResponse.json({ status: "ignored" });
}
