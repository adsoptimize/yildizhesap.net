/**
 * Iyzico Checkout Form integration.
 *
 * Uses the IYZWSv2 authorization scheme (HMAC-SHA256) documented at
 * https://docs.iyzico.com/ apart-way key + secret pairs, sandbox toggle, and
 * plain `fetch` — no `iyzipay` npm dependency so we can call it from Route
 * Handlers and Server Actions without CJS shims.
 *
 * Two endpoints are wrapped:
 *   POST /payment/iyzipos/checkoutform/initialize/auth/ecom  → create form
 *   POST /payment/iyzipos/checkoutform/auth/ecom/detail       → verify result
 */

import { createHmac, randomBytes } from "node:crypto";
import type { IyzicoCredentials } from "./settings";

const CHECKOUT_INIT_PATH = "/payment/iyzipos/checkoutform/initialize/auth/ecom";
const CHECKOUT_DETAIL_PATH = "/payment/iyzipos/checkoutform/auth/ecom/detail";

const PROD_BASE_URL = "https://api.iyzipay.com";
const SANDBOX_BASE_URL = "https://sandbox-api.iyzipay.com";

const RANDOM_KEY_BYTES = 8;

/**
 * Iyzico rejects requests that miss any of the buyer/address fields, so a
 * fallback is provided when the storefront collects less information than the
 * schema requires. The values below match the placeholders Iyzico documents
 * for guest/digital goods flows.
 */
const IYZICO_DEFAULT_IDENTITY = "11111111111"; // dummy TC (Iyzico test/production accept)
const IYZICO_DEFAULT_ADDRESS = "Türkiye";
const IYZICO_DEFAULT_CITY = "İstanbul";
const IYZICO_DEFAULT_COUNTRY = "Turkey";
const IYZICO_DEFAULT_ZIP = "34000";
const IYZICO_DEFAULT_PHONE = "+905000000000";

export type IyzicoBuyer = {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string | null;
  ip: string;
};

export type IyzicoBasketItem = {
  id: string;
  name: string;
  category: string;
  price: string;
};

export type IyzicoInitializeInput = {
  orderCode: string;
  amount: string;
  buyer: IyzicoBuyer;
  basketItems: readonly IyzicoBasketItem[];
  callbackUrl: string;
};

export type IyzicoInitializeSuccess = {
  status: "success";
  token: string;
  /** Ready-to-redirect URL on Iyzico's hosted checkout page. */
  paymentPageUrl: string;
  /** HTML/JS content that can also be inlined to embed the form. */
  checkoutFormContent: string;
  tokenExpireSeconds: number;
};

export type IyzicoInitializeError = {
  status: "failure";
  errorCode: string | null;
  errorMessage: string | null;
};

export type IyzicoInitializeResult =
  | IyzicoInitializeSuccess
  | IyzicoInitializeError;

export type IyzicoRetrieveInput = {
  orderCode: string;
  token: string;
};

export type IyzicoRetrieveSuccess = {
  status: "success";
  paymentStatus: string; // "SUCCESS" | "FAILURE" | "INIT_THREEDS" | ...
  paymentId: string | null;
  paidPrice: string | null;
  price: string | null;
  currency: string | null;
  conversationId: string;
  buyerEmail: string | null;
  buyerName: string | null;
  fraudStatus: number | null;
};

export type IyzicoRetrieveError = {
  status: "failure";
  errorCode: string | null;
  errorMessage: string | null;
};

export type IyzicoRetrieveResult = IyzicoRetrieveSuccess | IyzicoRetrieveError;

function baseUrl(credentials: IyzicoCredentials): string {
  return credentials.testMode ? SANDBOX_BASE_URL : PROD_BASE_URL;
}

/**
 * Builds the `Authorization` header using Iyzico's IYZWSv2 scheme:
 *   payload   = HMAC-SHA256(randomKey + uriPath + bodyJson, secretKey)
 *   authRaw   = apiKey:{apiKey}&randomKey:{randomKey}&signature:{hex(payload)}
 *   header    = "IYZWSv2 " + base64(authRaw)
 */
function buildAuthorization(
  credentials: IyzicoCredentials,
  uriPath: string,
  randomKey: string,
  bodyJson: string,
): string {
  const signaturePayload = `${randomKey}${uriPath}${bodyJson}`;
  const signature = createHmac("sha256", credentials.secretKey)
    .update(signaturePayload)
    .digest("hex");

  const raw = `apiKey:${credentials.apiKey}&randomKey:${randomKey}&signature:${signature}`;
  return `IYZWSv2 ${Buffer.from(raw).toString("base64")}`;
}

async function callIyzico<TResponse>(
  credentials: IyzicoCredentials,
  path: string,
  body: Record<string, unknown>,
): Promise<TResponse> {
  const randomKey = `${Date.now()}${randomBytes(RANDOM_KEY_BYTES).toString("hex")}`;
  const bodyJson = JSON.stringify(body);
  const authorization = buildAuthorization(
    credentials,
    path,
    randomKey,
    bodyJson,
  );

  const response = await fetch(`${baseUrl(credentials)}${path}`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      Authorization: authorization,
      "x-iyzi-rnd": randomKey,
    },
    body: bodyJson,
    // Iyzico is a payment provider; never let Next cache the responses.
    cache: "no-store",
  });

  if (!response.ok) {
    // Iyzico still returns JSON with error payload on 4xx/5xx; try to parse.
    let text = "";
    try {
      text = await response.text();
    } catch {
      text = `HTTP ${response.status}`;
    }
    throw new Error(
      `Iyzico ${path} HTTP ${response.status}: ${text.slice(0, 400)}`,
    );
  }

  return (await response.json()) as TResponse;
}

function readString(value: unknown): string | null {
  return typeof value === "string" && value !== "" ? value : null;
}

function readNumberAsString(value: unknown): string | null {
  if (typeof value === "string" && value !== "") return value;
  if (typeof value === "number" && Number.isFinite(value)) return String(value);
  return null;
}

export async function initializeCheckoutForm(
  credentials: IyzicoCredentials,
  input: IyzicoInitializeInput,
): Promise<IyzicoInitializeResult> {
  const amount = Number(input.amount).toFixed(2);
  const basketItems = input.basketItems.map((item) => ({
    id: item.id,
    name: item.name.slice(0, 200),
    category1: item.category === "" ? "Digital" : item.category,
    itemType: "VIRTUAL",
    price: Number(item.price).toFixed(2),
  }));

  const buyerAddress = {
    contactName: `${input.buyer.firstName} ${input.buyer.lastName}`.trim(),
    city: IYZICO_DEFAULT_CITY,
    country: IYZICO_DEFAULT_COUNTRY,
    address: IYZICO_DEFAULT_ADDRESS,
    zipCode: IYZICO_DEFAULT_ZIP,
  };

  const body = {
    locale: "tr",
    conversationId: input.orderCode,
    price: amount,
    paidPrice: amount,
    currency: "TRY",
    basketId: input.orderCode,
    paymentGroup: "PRODUCT",
    callbackUrl: input.callbackUrl,
    enabledInstallments: [1, 2, 3, 6, 9],
    buyer: {
      id: input.buyer.id,
      name: input.buyer.firstName || "Musteri",
      surname: input.buyer.lastName || "Kullanici",
      gsmNumber:
        input.buyer.phone === null || input.buyer.phone === ""
          ? IYZICO_DEFAULT_PHONE
          : input.buyer.phone,
      email: input.buyer.email,
      identityNumber: IYZICO_DEFAULT_IDENTITY,
      registrationAddress: IYZICO_DEFAULT_ADDRESS,
      ip: input.buyer.ip === "" ? "127.0.0.1" : input.buyer.ip,
      city: IYZICO_DEFAULT_CITY,
      country: IYZICO_DEFAULT_COUNTRY,
      zipCode: IYZICO_DEFAULT_ZIP,
    },
    shippingAddress: buyerAddress,
    billingAddress: buyerAddress,
    basketItems,
  };

  const raw = await callIyzico<Record<string, unknown>>(
    credentials,
    CHECKOUT_INIT_PATH,
    body,
  );

  if (raw.status === "success") {
    return {
      status: "success",
      token: String(raw.token ?? ""),
      paymentPageUrl: String(raw.paymentPageUrl ?? ""),
      checkoutFormContent: String(raw.checkoutFormContent ?? ""),
      tokenExpireSeconds:
        typeof raw.tokenExpireTime === "number" ? raw.tokenExpireTime : 30 * 60,
    };
  }

  return {
    status: "failure",
    errorCode: readString(raw.errorCode),
    errorMessage: readString(raw.errorMessage),
  };
}

export async function retrieveCheckoutForm(
  credentials: IyzicoCredentials,
  input: IyzicoRetrieveInput,
): Promise<IyzicoRetrieveResult> {
  const body = {
    locale: "tr",
    conversationId: input.orderCode,
    token: input.token,
  };

  const raw = await callIyzico<Record<string, unknown>>(
    credentials,
    CHECKOUT_DETAIL_PATH,
    body,
  );

  if (raw.status === "success") {
    return {
      status: "success",
      paymentStatus: String(raw.paymentStatus ?? ""),
      paymentId: readString(raw.paymentId),
      paidPrice: readNumberAsString(raw.paidPrice),
      price: readNumberAsString(raw.price),
      currency: readString(raw.currency),
      conversationId: String(raw.conversationId ?? input.orderCode),
      buyerEmail: readString(
        typeof raw.itemTransactions === "object" ? null : raw.buyerEmail,
      ),
      buyerName: null,
      fraudStatus:
        typeof raw.fraudStatus === "number" ? raw.fraudStatus : null,
    };
  }

  return {
    status: "failure",
    errorCode: readString(raw.errorCode),
    errorMessage: readString(raw.errorMessage),
  };
}
