/** Payment provider credentials, stored in `crypto_settings` like the legacy app. */

import { prisma } from "@/lib/prisma";

const PAYMENT_SETTING_KEYS = [
  "cryptomus_enabled",
  "cryptomus_merchant_uuid",
  "cryptomus_payment_key",
  "shopier_enabled",
  "shopier_username",
  "shopier_key",
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

export type PaymentAvailability = {
  cryptomus: boolean;
  shopier: boolean;
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
  };
}
