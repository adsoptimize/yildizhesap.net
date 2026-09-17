/** Shared limits and route names for the cart → checkout → delivery flow. */

const SECONDS_PER_DAY = 86_400;
const CART_TTL_DAYS = 7;

export const CART_COOKIE = "yh_cart";
export const CART_COOKIE_MAX_AGE_SECONDS = SECONDS_PER_DAY * CART_TTL_DAYS;

export const MIN_ITEM_QUANTITY = 1;
export const MAX_ITEM_QUANTITY = 25;
export const MAX_CART_LINES = 20;

export const CART_PATH = "/sepet";
export const CHECKOUT_PATH = "/odeme";
export const PAYMENT_SUCCESS_PATH = "/odeme/basarili";
export const PAYMENT_CANCEL_PATH = "/odeme/iptal";
export const SHOPIER_FORM_PATH = "/odeme/shopier";
export const IYZICO_FORM_PATH = "/odeme/iyzico";
export const ACCOUNT_PATH = "/hesabim";
export const ORDER_TRACK_PATH = "/siparis-takip";
export const LOGIN_PATH = "/giris-yap";
export const FORGOT_PASSWORD_PATH = "/sifremi-unuttum";
export const PASSWORD_RESET_PATH = "/sifre-sifirla";

/** Shared by registration, the reset form, and the reset validation. */
export const MIN_PASSWORD_LENGTH = 6;

export const PAYMENT_METHODS = ["cryptomus", "shopier", "iyzico"] as const;
export type PaymentMethod = (typeof PAYMENT_METHODS)[number];

export function isPaymentMethod(value: string): value is PaymentMethod {
  return PAYMENT_METHODS.includes(value as PaymentMethod);
}
