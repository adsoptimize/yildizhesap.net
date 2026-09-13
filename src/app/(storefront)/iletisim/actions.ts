"use server";

/**
 * Contact form submission handler. Sends a Telegram notification to the admin
 * (matching how support tickets and stock alerts are surfaced today) and
 * throttles per IP using the shared guest_rate_limits table.
 */

import { getClientIp } from "@/lib/auth/session";
import { sendAdminTelegramMessage } from "@/lib/notify/telegram";
import { CONTACT_FORM_RULE, consumeRateLimit } from "@/lib/shop/rate-limit";

const NAME_MIN = 2;
const NAME_MAX = 80;
const EMAIL_MAX = 120;
const MESSAGE_MIN = 10;
const MESSAGE_MAX = 2_000;
const SUBJECT_MAX = 160;

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

export type ContactFormState = {
  status: "idle" | "success" | "error";
  message: string;
  fieldErrors?: Partial<Record<"name" | "email" | "subject" | "message", string>>;
};

export const INITIAL_CONTACT_FORM_STATE: ContactFormState = {
  status: "idle",
  message: "",
};

function normalize(input: FormDataEntryValue | null): string {
  if (typeof input !== "string") return "";
  return input.trim();
}

export async function submitContactAction(
  _previous: ContactFormState,
  formData: FormData,
): Promise<ContactFormState> {
  const name = normalize(formData.get("name"));
  const email = normalize(formData.get("email"));
  const subject = normalize(formData.get("subject"));
  const message = normalize(formData.get("message"));

  const fieldErrors: NonNullable<ContactFormState["fieldErrors"]> = {};

  if (name.length < NAME_MIN || name.length > NAME_MAX) {
    fieldErrors.name = "Adınızı 2-80 karakter arası girin.";
  }
  if (email.length === 0 || email.length > EMAIL_MAX || !EMAIL_RE.test(email)) {
    fieldErrors.email = "Geçerli bir e-posta adresi girin.";
  }
  if (subject.length > SUBJECT_MAX) {
    fieldErrors.subject = "Konu en fazla 160 karakter olabilir.";
  }
  if (message.length < MESSAGE_MIN || message.length > MESSAGE_MAX) {
    fieldErrors.message = "Mesajınızı 10-2000 karakter arası girin.";
  }

  if (Object.keys(fieldErrors).length > 0) {
    return {
      status: "error",
      message: "Lütfen formdaki hataları düzeltin.",
      fieldErrors,
    };
  }

  const ip = await getClientIp();
  const limit = await consumeRateLimit(ip, CONTACT_FORM_RULE);
  if (!limit.allowed) {
    return {
      status: "error",
      message:
        limit.message ??
        "Çok fazla mesaj gönderdiniz. Lütfen daha sonra tekrar deneyin.",
    };
  }

  // Ports the legacy admin alert style so the operator sees the same layout.
  const lines = [
    "*Yeni İletişim Formu Mesajı*",
    `*Ad:* ${name}`,
    `*E-posta:* ${email}`,
    subject === "" ? null : `*Konu:* ${subject}`,
    `*IP:* ${ip === "" ? "-" : ip}`,
    "",
    "*Mesaj:*",
    message,
  ].filter((line): line is string => line !== null);

  await sendAdminTelegramMessage(lines.join("\n"));

  return {
    status: "success",
    message:
      "Mesajınız alındı! Size en kısa sürede WhatsApp veya Telegram üzerinden dönüş yapılacaktır.",
  };
}
