"use server";

import { redirect } from "next/navigation";
import { verifyPassword } from "@/lib/auth/password";
import { createSession, logUserActivity } from "@/lib/auth/session";
import { formatDate } from "@/lib/admin/format";
import { ACCOUNT_PATH } from "@/lib/shop/constants";
import type { CustomerLoginState } from "@/lib/shop/form-state";
import { prisma } from "@/lib/prisma";

/** Same thresholds as legacy/Auth.php. */
const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 15;
const MS_PER_MINUTE = 60_000;

const GENERIC_ERROR = "Geçersiz e-posta/kullanıcı adı veya şifre.";

function buildDeactivatedMessage(
  reason: string | null,
  until: Date | null,
): string {
  const parts = ["Hesabınız devre dışı bırakılmıştır."];

  if (reason !== null && reason.trim() !== "") {
    parts.push(`Sebep: ${reason}`);
  }

  parts.push(
    until === null
      ? "Bu durum kalıcıdır."
      : `Durumunuz şu tarihe kadar geçerlidir: ${formatDate(until)}`,
  );

  parts.push(
    "Bunun bir hata olduğunu düşünüyorsanız destek ekibi ile iletişime geçin.",
  );

  return parts.join(" ");
}

export async function loginCustomerAction(
  _previousState: CustomerLoginState,
  formData: FormData,
): Promise<CustomerLoginState> {
  const identifier = String(formData.get("identifier") ?? "").trim();
  const password = String(formData.get("password") ?? "");

  if (identifier === "") {
    return { error: "E-posta veya kullanıcı adı gereklidir." };
  }

  if (password === "") {
    return { error: "Şifre gereklidir." };
  }

  const user = await prisma.user.findFirst({
    where: {
      OR: [{ username: identifier }, { email: identifier.toLowerCase() }],
    },
    select: {
      id: true,
      passwordHash: true,
      isActive: true,
      loginAttempts: true,
      lastLoginAttempt: true,
      deactivationReason: true,
      deactivatedUntil: true,
    },
  });

  if (user === null) {
    return { error: GENERIC_ERROR };
  }

  const lockedUntil =
    user.lastLoginAttempt === null
      ? null
      : user.lastLoginAttempt.getTime() + LOCKOUT_MINUTES * MS_PER_MINUTE;

  if (
    user.loginAttempts >= MAX_LOGIN_ATTEMPTS &&
    lockedUntil !== null &&
    lockedUntil > Date.now()
  ) {
    return {
      error: `Hesap geçici olarak kilitlendi. ${LOCKOUT_MINUTES} dakika sonra tekrar deneyin.`,
    };
  }

  const valid = await verifyPassword(password, user.passwordHash);

  if (!valid) {
    await prisma.user.update({
      where: { id: user.id },
      data: {
        loginAttempts: { increment: 1 },
        lastLoginAttempt: new Date(),
      },
    });

    return { error: GENERIC_ERROR };
  }

  if (!user.isActive) {
    return {
      error: buildDeactivatedMessage(
        user.deactivationReason,
        user.deactivatedUntil,
      ),
    };
  }

  await prisma.user.update({
    where: { id: user.id },
    data: { loginAttempts: 0, lastLoginAttempt: null, lastLogin: new Date() },
  });

  await createSession(user.id);
  await logUserActivity(user.id, "login", "Başarılı giriş");

  redirect(ACCOUNT_PATH);
}
