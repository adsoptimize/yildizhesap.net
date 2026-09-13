/**
 * Cryptomus invoice API, ported from legacy/CryptomusClient.php.
 *
 * Signature: md5(base64(jsonBody) + paymentKey). Cryptomus verifies it against
 * the exact bytes we transmit, so the signed string and the request body must be
 * the same string. Incoming webhooks are signed the PHP way — json_encode
 * escapes forward slashes — so the same escaping is applied before hashing.
 */

import { createHash, timingSafeEqual } from "node:crypto";
import type { CryptomusCredentials } from "./settings";

const CRYPTOMUS_API_URL = "https://api.cryptomus.com/v1/payment";
const INVOICE_LIFETIME_SECONDS = 7_200;

export type CryptomusInvoiceInput = {
  orderCode: string;
  amount: string;
  currency: string;
  toCurrency: string;
  callbackUrl: string;
  successUrl: string;
  returnUrl: string;
};

export type CryptomusInvoice = {
  uuid: string;
  paymentUrl: string;
};

export type CryptomusWebhookPayload = {
  orderId: string;
  status: string;
  uuid: string | null;
  paymentAmount: string | null;
  txid: string | null;
};

function signBody(body: string, paymentKey: string): string {
  return createHash("md5")
    .update(Buffer.from(body).toString("base64") + paymentKey)
    .digest("hex");
}

/** Mirrors PHP json_encode, which escapes forward slashes by default. */
function phpStyleJson(value: unknown): string {
  return JSON.stringify(value).replace(/\//g, "\\/");
}

export async function createCryptomusInvoice(
  credentials: CryptomusCredentials,
  input: CryptomusInvoiceInput,
): Promise<CryptomusInvoice> {
  const body = JSON.stringify({
    amount: input.amount,
    currency: input.currency,
    order_id: input.orderCode,
    to_currency: input.toCurrency,
    lifetime: INVOICE_LIFETIME_SECONDS,
    url_callback: input.callbackUrl,
    url_success: input.successUrl,
    url_return: input.returnUrl,
  });

  const response = await fetch(CRYPTOMUS_API_URL, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      merchant: credentials.merchantUuid,
      sign: signBody(body, credentials.paymentKey),
    },
    body,
    cache: "no-store",
  });

  const text = await response.text();
  let parsed: unknown;

  try {
    parsed = JSON.parse(text);
  } catch {
    throw new Error("Cryptomus yanıtı okunamadı.");
  }

  const payload = parsed as {
    state?: number;
    message?: string;
    errors?: unknown;
    result?: { uuid?: string; url?: string };
  };

  const uuid = payload.result?.uuid;
  const paymentUrl = payload.result?.url;

  if (!response.ok || uuid === undefined || paymentUrl === undefined) {
    const message = payload.message ?? `HTTP ${response.status}`;
    throw new Error(`Cryptomus ödeme oluşturulamadı: ${message}`);
  }

  return { uuid, paymentUrl };
}

export function verifyCryptomusWebhook(
  rawBody: string,
  paymentKey: string,
): CryptomusWebhookPayload | null {
  if (paymentKey === "") {
    return null;
  }

  let parsed: unknown;

  try {
    parsed = JSON.parse(rawBody);
  } catch {
    return null;
  }

  if (typeof parsed !== "object" || parsed === null) {
    return null;
  }

  const payload = parsed as Record<string, unknown>;
  const providedSign = payload.sign;

  if (typeof providedSign !== "string" || providedSign === "") {
    return null;
  }

  const { sign: _ignored, ...signedFields } = payload;
  const expectedSign = signBody(phpStyleJson(signedFields), paymentKey);
  const provided = Buffer.from(providedSign);
  const expected = Buffer.from(expectedSign);

  if (
    provided.length !== expected.length ||
    !timingSafeEqual(provided, expected)
  ) {
    return null;
  }

  const orderId = payload.order_id;
  const status = payload.status;

  if (typeof orderId !== "string" || typeof status !== "string") {
    return null;
  }

  return {
    orderId,
    status,
    uuid: typeof payload.uuid === "string" ? payload.uuid : null,
    paymentAmount:
      typeof payload.payment_amount === "string" ? payload.payment_amount : null,
    txid: typeof payload.txid === "string" ? payload.txid : null,
  };
}

const PAID_STATUSES = new Set(["paid", "paid_over"]);
const FAILED_STATUSES = new Set(["fail", "failed", "cancel", "cancelled"]);
const EXPIRED_STATUSES = new Set(["expired", "system_fail", "wrong_amount"]);

export type CryptomusOutcome = "paid" | "failed" | "expired" | "pending";

export function mapCryptomusStatus(status: string): CryptomusOutcome {
  if (PAID_STATUSES.has(status)) {
    return "paid";
  }

  if (FAILED_STATUSES.has(status)) {
    return "failed";
  }

  if (EXPIRED_STATUSES.has(status)) {
    return "expired";
  }

  return "pending";
}
