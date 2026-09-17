"use server";

import { consumePasswordResetToken } from "@/lib/auth/password-reset";
import { getClientIp } from "@/lib/auth/session";
import { MIN_PASSWORD_LENGTH } from "@/lib/shop/constants";
import type { ResetPasswordState } from "@/lib/shop/form-state";
import { consumeRateLimit, PASSWORD_RESET_RULE } from "@/lib/shop/rate-limit";

const INVALID_MESSAGE =
  "Bağlantı geçersiz veya daha önce kullanılmış. Giriş sayfasından yeni bir bağlantı isteyin.";
const EXPIRED_MESSAGE =
  "Bağlantının süresi dolmuş. Giriş sayfasından yeni bir bağlantı isteyin.";

export async function resetPasswordAction(
  _previousState: ResetPasswordState,
  formData: FormData,
): Promise<ResetPasswordState> {
  const token = String(formData.get("token") ?? "").trim();
  const password = String(formData.get("password") ?? "");
  const passwordConfirm = String(formData.get("passwordConfirm") ?? "");

  if (token === "") {
    return { error: INVALID_MESSAGE, done: false };
  }

  if (password.length < MIN_PASSWORD_LENGTH) {
    return {
      error: `Şifre en az ${MIN_PASSWORD_LENGTH} karakter olmalıdır.`,
      done: false,
    };
  }

  if (password !== passwordConfirm) {
    return { error: "Şifreler birbiriyle uyuşmuyor.", done: false };
  }

  // Guessing a 64-hex token is not realistic, but the throttle also caps how
  // often one IP can hammer this endpoint.
  const ip = await getClientIp();
  const limit = await consumeRateLimit(ip, PASSWORD_RESET_RULE);

  if (!limit.allowed) {
    return {
      error: limit.message ?? "Çok fazla deneme yaptınız.",
      done: false,
    };
  }

  const outcome = await consumePasswordResetToken(token, password);

  if (outcome === "expired") {
    return { error: EXPIRED_MESSAGE, done: false };
  }

  if (outcome !== "ok") {
    return { error: INVALID_MESSAGE, done: false };
  }

  return { error: null, done: true };
}
