/**
 * Customer e-mail transport. Follows the same shape as notify/telegram.ts:
 * configuration lives in `crypto_settings`, is edited on the admin payment
 * settings screen, and a send failure can never break the caller.
 *
 * The legacy PHP site had no mail layer at all, so this is additive: when SMTP
 * is left unconfigured every call is a no-op and delivery still works through
 * the panel and the order tracking page.
 */

import nodemailer from "nodemailer";
import { prisma } from "@/lib/prisma";

const SETTING_KEYS = [
  "smtp_enabled",
  "smtp_host",
  "smtp_port",
  "smtp_secure",
  "smtp_user",
  "smtp_password",
  "smtp_from_name",
  "smtp_from_email",
] as const;

/** Submission port; 465 is the implicit-TLS alternative toggled by smtp_secure. */
const DEFAULT_SMTP_PORT = 587;
const IMPLICIT_TLS_PORT = 465;

type SmtpConfig = {
  host: string;
  port: number;
  secure: boolean;
  user: string;
  password: string;
  fromName: string;
  fromEmail: string;
};

export type MailAttachment = {
  filename: string;
  content: string;
};

export type MailMessage = {
  to: string;
  subject: string;
  /**
   * Always required, even when `html` is set: clients that refuse HTML fall
   * back to it, and a multipart message without a text part scores worse with
   * spam filters.
   */
  text: string;
  html?: string;
  attachments?: readonly MailAttachment[];
};

async function readSmtpConfig(): Promise<SmtpConfig | null> {
  const rows = await prisma.cryptoSetting.findMany({
    where: { settingKey: { in: [...SETTING_KEYS] } },
    select: { settingKey: true, settingValue: true },
  });

  const config = new Map(
    rows.map((row) => [row.settingKey, row.settingValue ?? ""]),
  );

  if (config.get("smtp_enabled") !== "1") {
    return null;
  }

  const host = config.get("smtp_host") ?? "";
  const user = config.get("smtp_user") ?? "";
  const password = config.get("smtp_password") ?? "";

  if (host === "" || user === "" || password === "") {
    return null;
  }

  const parsedPort = Number(config.get("smtp_port") ?? "");
  const port = Number.isInteger(parsedPort) && parsedPort > 0
    ? parsedPort
    : DEFAULT_SMTP_PORT;

  return {
    host,
    port,
    // Hostinger and most providers expect implicit TLS on 465 and STARTTLS
    // elsewhere, so the port implies the mode unless it is set explicitly.
    secure: config.get("smtp_secure") === "1" || port === IMPLICIT_TLS_PORT,
    user,
    password,
    // Falling back to the SMTP user keeps the envelope valid when the operator
    // fills in only the credentials.
    fromName: config.get("smtp_from_name") ?? "",
    fromEmail: config.get("smtp_from_email") || user,
  };
}

/** Never throws: a failed e-mail must not roll back a paid, delivered order. */
export async function sendCustomerEmail(message: MailMessage): Promise<boolean> {
  try {
    const config = await readSmtpConfig();

    if (config === null) {
      return false;
    }

    const transport = nodemailer.createTransport({
      host: config.host,
      port: config.port,
      secure: config.secure,
      auth: { user: config.user, pass: config.password },
    });

    await transport.sendMail({
      from:
        config.fromName === ""
          ? config.fromEmail
          : `"${config.fromName}" <${config.fromEmail}>`,
      to: message.to,
      subject: message.subject,
      text: message.text,
      html: message.html,
      attachments: message.attachments?.map((attachment) => ({
        filename: attachment.filename,
        content: attachment.content,
        contentType: "text/plain; charset=utf-8",
      })),
    });

    return true;
  } catch (error: unknown) {
    console.error("Müşteri e-postası gönderilemedi", error);
    return false;
  }
}

// The admin test-send lives in order-email.ts so it can exercise the real
// delivery template instead of a plain-text stub, which makes it double as a
// preview of what buyers receive.
