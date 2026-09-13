/**
 * Shopier pay4 form integration, ported from legacy/ShopierClient.php.
 *
 * The legacy client posted hardcoded buyer details ("Bera Ramazan" with a dummy
 * e-mail and phone) for every order; the real customer is sent instead so Shopier
 * receipts and fraud checks match the order.
 */

import { createHmac, randomInt, timingSafeEqual } from "node:crypto";
import type { ShopierCredentials } from "./settings";

const SHOPIER_FORM_URL = "https://www.shopier.com/ShowProduct/api_pay4.php";

const RANDOM_NR_MIN = 100_000;
const RANDOM_NR_MAX = 999_999;

/** Shopier currency codes: 0 = TRY, 1 = USD, 2 = EUR. */
const CURRENCY_TRY = "0";
const PRODUCT_TYPE_DIGITAL = "1";
const PLATFORM_OTHER = "2";
const WEBSITE_INDEX = "1";
const LANGUAGE_TURKISH = "0";
const NOT_IN_FRAME = "0";
const DEFAULT_BUYER_ACCOUNT_AGE = "0";

export type ShopierBuyer = {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
};

export type ShopierFormInput = {
  orderCode: string;
  amount: string;
  productName: string;
  buyer: ShopierBuyer;
};

export type ShopierForm = {
  actionUrl: string;
  fields: Readonly<Record<string, string>>;
};

export function buildShopierForm(
  credentials: ShopierCredentials,
  input: ShopierFormInput,
): ShopierForm {
  const randomNr = String(randomInt(RANDOM_NR_MIN, RANDOM_NR_MAX + 1));
  const totalOrderValue = Number(input.amount).toFixed(2);
  const signatureData = `${randomNr}${input.orderCode}${totalOrderValue}${CURRENCY_TRY}`;
  const signature = createHmac("sha256", credentials.apiKey)
    .update(signatureData)
    .digest("base64");

  return {
    actionUrl: SHOPIER_FORM_URL,
    fields: {
      API_key: credentials.username,
      website_index: WEBSITE_INDEX,
      platform_order_id: input.orderCode,
      product_name: input.productName,
      product_type: PRODUCT_TYPE_DIGITAL,
      buyer_name: input.buyer.firstName,
      buyer_surname: input.buyer.lastName,
      buyer_email: input.buyer.email,
      buyer_account_age: DEFAULT_BUYER_ACCOUNT_AGE,
      buyer_phone: input.buyer.phone,
      billing_address: "-",
      billing_city: "-",
      billing_country: "Türkiye",
      billing_postcode: "-",
      shipping_address: "-",
      shipping_city: "-",
      shipping_country: "Türkiye",
      shipping_postcode: "-",
      total_order_value: totalOrderValue,
      currency: CURRENCY_TRY,
      platform: PLATFORM_OTHER,
      is_in_frame: NOT_IN_FRAME,
      current_language: LANGUAGE_TURKISH,
      random_nr: randomNr,
      signature,
    },
  };
}

export type ShopierWebhookPayload = {
  orderCode: string;
  email: string | null;
  customerName: string | null;
  price: string | null;
  isTest: boolean;
};

/**
 * Shopier posts `res` (base64 JSON) and `hash`, where
 * hash = hex hmac-sha256(res + apiUsername, apiKey).
 */
export function verifyShopierWebhook(
  credentials: ShopierCredentials,
  res: string,
  hash: string,
): ShopierWebhookPayload | null {
  const expected = createHmac("sha256", credentials.apiKey)
    .update(`${res}${credentials.username}`)
    .digest("hex");

  const provided = Buffer.from(hash);
  const expectedBuffer = Buffer.from(expected);

  if (
    provided.length !== expectedBuffer.length ||
    !timingSafeEqual(provided, expectedBuffer)
  ) {
    return null;
  }

  let parsed: unknown;

  try {
    parsed = JSON.parse(Buffer.from(res, "base64").toString("utf8"));
  } catch {
    return null;
  }

  if (typeof parsed !== "object" || parsed === null) {
    return null;
  }

  const payload = parsed as Record<string, unknown>;
  const orderCode = payload.orderid;

  if (typeof orderCode !== "string" || orderCode === "") {
    return null;
  }

  const buyerName =
    typeof payload.buyername === "string" ? payload.buyername : "";
  const buyerSurname =
    typeof payload.buyersurname === "string" ? payload.buyersurname : "";
  const fullName = `${buyerName} ${buyerSurname}`.trim();

  return {
    orderCode,
    email: typeof payload.email === "string" ? payload.email : null,
    customerName: fullName === "" ? null : fullName,
    price:
      typeof payload.price === "string"
        ? payload.price
        : typeof payload.price === "number"
          ? String(payload.price)
          : null,
    isTest: String(payload.istest ?? "0") === "1",
  };
}
