/**
 * Support handle used in the credential .txt files and the delivery e-mail, so
 * a customer who runs into trouble has a way to reach us inside the artefact
 * they downloaded. Edited on the admin contact settings screen.
 */

import { prisma } from "@/lib/prisma";

export async function readSupportTelegramUsername(): Promise<string | null> {
  const row = await prisma.contactSetting.findFirst({
    where: { settingKey: "telegram_username" },
    select: { settingValue: true },
  });

  return row?.settingValue ?? null;
}
