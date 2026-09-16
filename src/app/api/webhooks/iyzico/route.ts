/**
 * Iyzico Checkout Form callback.
 *
 * Iyzico posts `token` (and `conversationId`) back to the callbackUrl as a
 * form-urlencoded body once the buyer completes 3DS. The same URL also serves
 * as the buyer's return redirect, so we finish by 302'ing to the site
 * `success` / `cancel` page depending on `paymentStatus`.
 */

import { NextResponse, type NextRequest } from "next/server";
import { prisma } from "@/lib/prisma";
import { retrieveCheckoutForm } from "@/lib/payments/iyzico";
import { getIyzicoWebhookCredentials } from "@/lib/payments/settings";
import {
  PAYMENT_CANCEL_PATH,
  PAYMENT_SUCCESS_PATH,
} from "@/lib/shop/constants";
import { getAbsoluteUrl } from "@/lib/shop/site-url";
import {
  confirmPayment,
  markPaymentFailed,
} from "@/lib/shop/orders";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

const WEBHOOK_LOG_SOURCE = "iyzico";
const IYZICO_PAYMENT_STATUS_SUCCESS = "SUCCESS";
const IYZICO_STATUS_SUCCESS = "success";

async function logWebhook(payload: string, status: string): Promise<void> {
  try {
    await prisma.cryptoWebhookLog.create({
      data: { payload, headers: WEBHOOK_LOG_SOURCE, status },
    });
  } catch (error: unknown) {
    console.error("Iyzico webhook logu yazılamadı", error);
  }
}

async function redirectToCancel(orderCode: string): Promise<NextResponse> {
  const target = await getAbsoluteUrl(
    `${PAYMENT_CANCEL_PATH}?order=${encodeURIComponent(orderCode)}`,
  );
  // 303 → browser follows GET on the redirect, safe after POST.
  return NextResponse.redirect(target, 303);
}

async function redirectToSuccess(orderCode: string): Promise<NextResponse> {
  const target = await getAbsoluteUrl(
    `${PAYMENT_SUCCESS_PATH}?order=${encodeURIComponent(orderCode)}`,
  );
  return NextResponse.redirect(target, 303);
}

export async function POST(request: NextRequest): Promise<NextResponse> {
  const formData = await request.formData();
  const token = String(formData.get("token") ?? "").trim();
  const conversationId = String(formData.get("conversationId") ?? "").trim();

  if (token === "" || conversationId === "") {
    await logWebhook(
      `token=${token.slice(0, 10)}, conv=${conversationId}`,
      "missing_fields",
    );
    return redirectToCancel(conversationId);
  }

  const credentials = await getIyzicoWebhookCredentials();

  if (credentials === null) {
    await logWebhook(conversationId, "missing_credentials");
    return redirectToCancel(conversationId);
  }

  // Iyzico's docs recommend re-fetching the payment result server-side; we
  // never trust the callback body for the payment status.
  let detail;
  try {
    detail = await retrieveCheckoutForm(credentials, {
      orderCode: conversationId,
      token,
    });
  } catch (error: unknown) {
    console.error("Iyzico retrieve hatası", error);
    await logWebhook(conversationId, "retrieve_error");
    return redirectToCancel(conversationId);
  }

  if (detail.status !== IYZICO_STATUS_SUCCESS) {
    await logWebhook(
      `${conversationId}: ${detail.errorCode ?? "-"} ${detail.errorMessage ?? "-"}`,
      "retrieve_failure",
    );
    await markPaymentFailed(conversationId, "failed");
    return redirectToCancel(conversationId);
  }

  if (detail.paymentStatus !== IYZICO_PAYMENT_STATUS_SUCCESS) {
    await logWebhook(
      `${conversationId}: paymentStatus=${detail.paymentStatus}`,
      "payment_not_success",
    );
    await markPaymentFailed(conversationId, "failed");
    return redirectToCancel(conversationId);
  }

  const result = await confirmPayment({
    orderCode: conversationId,
    paidAmount: detail.paidPrice ?? detail.price,
    shopierPaymentId: detail.paymentId,
  });

  await logWebhook(conversationId, `success:${result}`);

  if (result === "not_found") {
    return redirectToCancel(conversationId);
  }

  return redirectToSuccess(conversationId);
}

/**
 * Iyzico currently only POSTs. Some proxy setups may re-open the redirect as
 * GET; we mirror the same redirect so nothing bounces to a 405 page.
 */
export async function GET(request: NextRequest): Promise<NextResponse> {
  const conversationId =
    request.nextUrl.searchParams.get("order") ??
    request.nextUrl.searchParams.get("conversationId") ??
    "";
  return redirectToCancel(conversationId);
}
