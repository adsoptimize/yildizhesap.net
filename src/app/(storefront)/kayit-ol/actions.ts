"use server";

import { randomBytes } from "node:crypto";
import { redirect } from "next/navigation";
import { hashPassword } from "@/lib/auth/password";
import { createSession, getClientIp, logUserActivity } from "@/lib/auth/session";
import { ACCOUNT_PATH } from "@/lib/shop/constants";
import type { RegisterState } from "@/lib/shop/form-state";
import { prisma } from "@/lib/prisma";

/** Legacy/Auth.php rules, kept identical so existing customers see no change. */
const MIN_USERNAME_LENGTH = 3;
const MIN_PASSWORD_LENGTH = 6;
const VERIFICATION_TOKEN_BYTES = 32;
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const DEFAULT_IP_LIMIT_ENABLED = "1";
const DEFAULT_MAX_ACCOUNTS_PER_IP = 5;
const DEFAULT_IP_LIMIT_DAYS = 365;
const MS_PER_DAY = 86_400_000;

const IP_LIMIT_KEYS = [
  "ip_limit_enabled",
  "max_accounts_per_ip",
  "ip_limit_days",
] as const;

function readText(formData: FormData, key: string): string {
  return String(formData.get(key) ?? "").trim();
}

function parseSettingInt(value: string | undefined, fallback: number): number {
  const parsed = Number.parseInt(value ?? "", 10);
  return Number.isNaN(parsed) || parsed <= 0 ? fallback : parsed;
}

/**
 * Registration cap per IP address, ported from Auth::checkIPAccountLimit().
 * Fails open on errors, exactly like the legacy implementation.
 */
async function checkIpAccountLimit(ip: string): Promise<string | null> {
  try {
    const rows = await prisma.siteSetting.findMany({
      where: { settingKey: { in: [...IP_LIMIT_KEYS] } },
      select: { settingKey: true, settingValue: true },
    });

    const settings = new Map(
      rows.map((row) => [row.settingKey, row.settingValue ?? ""]),
    );

    const enabled =
      (settings.get("ip_limit_enabled") ?? DEFAULT_IP_LIMIT_ENABLED) ===
      DEFAULT_IP_LIMIT_ENABLED;

    if (!enabled || ip === "" || ip === "unknown") {
      return null;
    }

    const maxAccounts = parseSettingInt(
      settings.get("max_accounts_per_ip"),
      DEFAULT_MAX_ACCOUNTS_PER_IP,
    );
    const limitDays = parseSettingInt(
      settings.get("ip_limit_days"),
      DEFAULT_IP_LIMIT_DAYS,
    );

    const count = await prisma.user.count({
      where: {
        registrationIp: ip,
        createdAt: { gte: new Date(Date.now() - limitDays * MS_PER_DAY) },
      },
    });

    if (count >= maxAccounts) {
      return `Bu IP adresinden son ${limitDays} gün içinde maksimum ${maxAccounts} hesap oluşturulabilir. Mevcut: ${count} hesap`;
    }

    return null;
  } catch (error: unknown) {
    console.error("IP limiti kontrol edilemedi", error);
    return null;
  }
}

export async function registerCustomerAction(
  _previousState: RegisterState,
  formData: FormData,
): Promise<RegisterState> {
  const username = readText(formData, "username");
  const email = readText(formData, "email").toLowerCase();
  const password = String(formData.get("password") ?? "");
  const passwordConfirm = String(formData.get("passwordConfirm") ?? "");
  const firstName = readText(formData, "firstName");
  const lastName = readText(formData, "lastName");
  const phone = readText(formData, "phone");

  if (username.length < MIN_USERNAME_LENGTH) {
    return { error: "Kullanıcı adı en az 3 karakter olmalıdır." };
  }

  if (!EMAIL_PATTERN.test(email)) {
    return { error: "Geçerli bir e-posta adresi girin." };
  }

  if (password.length < MIN_PASSWORD_LENGTH) {
    return { error: "Şifre en az 6 karakter olmalıdır." };
  }

  if (password !== passwordConfirm) {
    return { error: "Şifreler eşleşmiyor." };
  }

  if (firstName === "") {
    return { error: "Ad alanı gereklidir." };
  }

  if (lastName === "") {
    return { error: "Soyad alanı gereklidir." };
  }

  const existing = await prisma.user.findFirst({
    where: { OR: [{ email }, { username }] },
    select: { id: true },
  });

  if (existing !== null) {
    return { error: "Bu e-posta veya kullanıcı adı zaten kullanılıyor." };
  }

  const ip = await getClientIp();
  const limitError = await checkIpAccountLimit(ip);

  if (limitError !== null) {
    return { error: limitError };
  }

  const user = await prisma.user.create({
    data: {
      username,
      email,
      passwordHash: await hashPassword(password),
      firstName,
      lastName,
      phone: phone === "" ? null : phone,
      registrationIp: ip === "unknown" ? null : ip,
      verificationToken: randomBytes(VERIFICATION_TOKEN_BYTES).toString("hex"),
      isActive: true,
    },
    select: { id: true },
  });

  await createSession(user.id);
  await logUserActivity(user.id, "register", "Yeni hesap oluşturuldu");

  redirect(ACCOUNT_PATH);
}
