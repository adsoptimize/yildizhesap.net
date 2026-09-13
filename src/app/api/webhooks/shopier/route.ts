/**
 * Shopier OSB callback. Ported from legacy/shopier_webhook.php: Shopier posts
 * `res` (base64 JSON) plus `hash`, and expects the plain text body "success".
 */

import { prisma } from "@/lib/prisma";
import { verifyShopierWebhook } from "@/lib/payments/shopier";
import { getShopierWebhookCredentials } from "@/lib/payments/settings";
import { confirmPayment } from "@/lib/shop/orders";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

const WEBHOOK_LOG_SOURCE = "shopier";
const SUCCESS_BODY = "success";
const ERROR_BODY = "error";

async function logWebhook(payload: string, status: string): Promise<void> {
  try {
    await prisma.cryptoWebhookLog.create({
      data: { payload, headers: WEBHOOK_LOG_SOURCE, status },
    });
  } catch (error: unknown) {
    console.error("Webhook logu yazılamadı", error);
  }
}

function textResponse(body: string, status: number): Response {
  return new Response(body, {
    status,
    headers: { "Content-Type": "text/plain; charset=utf-8" },
  });
}

export async function POST(request: Request): Promise<Response> {
  const formData = await request.formData();
  const res = String(formData.get("res") ?? "");
  const hash = String(formData.get("hash") ?? "");

  if (res === "" || hash === "") {
    await logWebhook("missing_fields", "invalid");
    return textResponse(ERROR_BODY, 400);
  }

  const credentials = await getShopierWebhookCredentials();

  if (credentials === null) {
    await logWebhook(res, "missing_credentials");
    return textResponse(ERROR_BODY, 500);
  }

  const payload = verifyShopierWebhook(credentials, res, hash);

  if (payload === null) {
    await logWebhook(res, "invalid_signature");
    return textResponse(ERROR_BODY, 400);
  }

  const result = await confirmPayment({
    orderCode: payload.orderCode,
    paidAmount: payload.price,
    shopierPaymentId: payload.orderCode,
    email: payload.email,
    customerName: payload.customerName,
  });

  await logWebhook(res, `${payload.isTest ? "test" : "live"}:${result}`);

  if (result === "not_found") {
    return textResponse(ERROR_BODY, 404);
  }

  return textResponse(SUCCESS_BODY, 200);
}
