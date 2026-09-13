/**
 * Admin notifications, ported from legacy/TelegramNotifier.php. Settings live in
 * `crypto_settings` and are edited on the admin payment settings screen.
 */

import { prisma } from "@/lib/prisma";

const TELEGRAM_API_BASE = "https://api.telegram.org";
const SETTING_KEYS = [
  "telegram_enabled",
  "telegram_bot_token",
  "telegram_chat_id",
] as const;

async function readTelegramConfig(): Promise<{
  botToken: string;
  chatId: string;
} | null> {
  const rows = await prisma.cryptoSetting.findMany({
    where: { settingKey: { in: [...SETTING_KEYS] } },
    select: { settingKey: true, settingValue: true },
  });

  const config = new Map(
    rows.map((row) => [row.settingKey, row.settingValue ?? ""]),
  );

  if (config.get("telegram_enabled") !== "1") {
    return null;
  }

  const botToken = config.get("telegram_bot_token") ?? "";
  const chatId = config.get("telegram_chat_id") ?? "";

  if (botToken === "" || chatId === "") {
    return null;
  }

  return { botToken, chatId };
}

/** Never throws: a failed notification must not roll back a paid order. */
export async function sendAdminTelegramMessage(text: string): Promise<void> {
  try {
    const config = await readTelegramConfig();

    if (config === null) {
      return;
    }

    await fetch(`${TELEGRAM_API_BASE}/bot${config.botToken}/sendMessage`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        chat_id: config.chatId,
        text,
        parse_mode: "Markdown",
      }),
      cache: "no-store",
    });
  } catch (error: unknown) {
    console.error("Telegram bildirimi gönderilemedi", error);
  }
}
