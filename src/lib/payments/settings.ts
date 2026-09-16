/** Payment provider credentials, stored in `crypto_settings` like the legacy app. */

import { prisma } from "@/lib/prisma";

const PAYMENT_SETTING_KEYS = [
  "cryptomus_enabled",
  "cryptomus_merchant_uuid",
  "cryptomus_payment_key",
  "shopier_enabled",
  "shopier_username",
  "shopier_key",
  "iyzico_enabled",
  "iyzico_test_mode",
  "iyzico_api_key",
  "iyzico_secret_key",
] as const;

const ENABLED_VALUE = "1";

export type CryptomusCredentials = {
  merchantUuid: string;
  paymentKey: string;
};

export type ShopierCredentials = {
  username: string;
  apiKey: string;
};

export type IyzicoCredentials = {
  apiKey: string;
  secretKey: string;
  /** Sandbox toggle — sandbox host is used when true. */
  testMode: boolean;
};

export type PaymentAvailability = {
  cryptomus: boolean;
  shopier: boolean;
  iyzico: boolean;
};

async function readSettings(): Promise<Map<string, string>> {
  const rows = await prisma.cryptoSetting.findMany({
    where: { settingKey: { in: [...PAYMENT_SETTING_KEYS] } },
    select: { settingKey: true, settingValue: true },
  });

  return new Map(rows.map((row) => [row.settingKey, row.settingValue ?? ""]));
}

export async function getCryptomusCredentials(): Promise<CryptomusCredentials | null> {
  const settings = await readSettings();

  if (settings.get("cryptomus_enabled") !== ENABLED_VALUE) {
    return null;
  }

  const merchantUuid = settings.get("cryptomus_merchant_uuid") ?? "";
  const paymentKey = settings.get("cryptomus_payment_key") ?? "";

  if (merchantUuid === "" || paymentKey === "") {
    return null;
  }

  return { merchantUuid, paymentKey };
}

export async function getShopierCredentials(): Promise<ShopierCredentials | null> {
  const settings = await readSettings();

  if (settings.get("shopier_enabled") !== ENABLED_VALUE) {
    return null;
  }

  const username = settings.get("shopier_username") ?? "";
  const apiKey = settings.get("shopier_key") ?? "";

  if (username === "" || apiKey === "") {
    return null;
  }

  return { username, apiKey };
}

export async function getIyzicoCredentials(): Promise<IyzicoCredentials | null> {
  const settings = await readSettings();

  if (settings.get("iyzico_enabled") !== ENABLED_VALUE) {
    return null;
  }

  const apiKey = settings.get("iyzico_api_key") ?? "";
  const secretKey = settings.get("iyzico_secret_key") ?? "";

  if (apiKey === "" || secretKey === "") {
    return null;
  }

  return {
    apiKey,
    secretKey,
    testMode: settings.get("iyzico_test_mode") === ENABLED_VALUE,
  };
}

/**
 * Iyzico webhook / callback handlers still need credentials even after the
 * method is toggled off, so they can gracefully return "failure" instead of
 * silently 401-ing.
 */
export async function getIyzicoWebhookCredentials(): Promise<IyzicoCredentials | null> {
  const settings = await readSettings();
  const apiKey = settings.get("iyzico_api_key") ?? "";
  const secretKey = settings.get("iyzico_secret_key") ?? "";

  if (apiKey === "" || secretKey === "") {
    return null;
  }

  return {
    apiKey,
    secretKey,
    testMode: settings.get("iyzico_test_mode") === ENABLED_VALUE,
  };
}

/** Webhook handlers need the keys even when the method was switched off later. */
export async function getCryptomusPaymentKey(): Promise<string> {
  const settings = await readSettings();
  return settings.get("cryptomus_payment_key") ?? "";
}

export async function getShopierWebhookCredentials(): Promise<ShopierCredentials | null> {
  const settings = await readSettings();
  const username = settings.get("shopier_username") ?? "";
  const apiKey = settings.get("shopier_key") ?? "";

  if (username === "" || apiKey === "") {
    return null;
  }

  return { username, apiKey };
}

export async function getPaymentAvailability(): Promise<PaymentAvailability> {
  const settings = await readSettings();

  return {
    cryptomus:
      settings.get("cryptomus_enabled") === ENABLED_VALUE &&
      (settings.get("cryptomus_merchant_uuid") ?? "") !== "" &&
      (settings.get("cryptomus_payment_key") ?? "") !== "",
    shopier:
      settings.get("shopier_enabled") === ENABLED_VALUE &&
      (settings.get("shopier_username") ?? "") !== "" &&
      (settings.get("shopier_key") ?? "") !== "",
    iyzico:
      settings.get("iyzico_enabled") === ENABLED_VALUE &&
      (settings.get("iyzico_api_key") ?? "") !== "" &&
      (settings.get("iyzico_secret_key") ?? "") !== "",
  };
}
