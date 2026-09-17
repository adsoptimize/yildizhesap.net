"use server";

import { issuePasswordResetToken } from "@/lib/auth/password-reset";
import { getClientIp } from "@/lib/auth/session";
import { sendPasswordResetEmail } from "@/lib/notify/password-reset-email";
import type { ForgotPasswordState } from "@/lib/shop/form-state";
import { consumeRateLimit, PASSWORD_RESET_RULE } from "@/lib/shop/rate-limit";

export async function requestPasswordResetAction(
  _previousState: ForgotPasswordState,
  formData: FormData,
): Promise<ForgotPasswordState> {
  const email = String(formData.get("email") ?? "").trim();

  if (email === "") {
    return { error: "E-posta adresi gereklidir.", submitted: false };
  }

  const ip = await getClientIp();
  const limit = await consumeRateLimit(ip, PASSWORD_RESET_RULE);

  if (!limit.allowed) {
    return {
      error: limit.message ?? "Çok fazla deneme yaptınız.",
      submitted: false,
    };
  }

  const issued = await issuePasswordResetToken(email);

  if (issued !== null) {
    await sendPasswordResetEmail({
      to: issued.email,
      firstName: issued.firstName,
      token: issued.token,
    });
  }

  // Reported the same way whether or not the address is registered: telling
  // the visitor would turn this form into a way to discover which e-mail
  // addresses have accounts here.
  return { error: null, submitted: true };
}
