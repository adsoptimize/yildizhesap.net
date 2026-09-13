"use server";

import { revalidatePath } from "next/cache";
import { requireAdmin } from "@/lib/auth/admin";
import type { AdminFormState } from "@/lib/admin/form-state";
import { prisma } from "@/lib/prisma";

const SETTING_FIELD_PREFIX = "payment__";

/** Blank secret fields keep the stored value instead of wiping it. */
export async function savePaymentSettingsAction(
  _previousState: AdminFormState,
  formData: FormData,
): Promise<AdminFormState> {
  await requireAdmin();

  const secretKeys = new Set(
    formData.getAll("secret_key").map((value) => String(value)),
  );
  const booleanKeys = new Set(
    formData.getAll("boolean_key").map((value) => String(value)),
  );

  const updates: { settingKey: string; settingValue: string }[] = [];

  for (const [field, rawValue] of formData.entries()) {
    if (!field.startsWith(SETTING_FIELD_PREFIX)) {
      continue;
    }

    const settingKey = field.slice(SETTING_FIELD_PREFIX.length);
    const value = String(rawValue);
    booleanKeys.delete(settingKey);

    if (secretKeys.has(settingKey) && value.trim() === "") {
      continue;
    }

    updates.push({ settingKey, settingValue: value });
  }

  for (const settingKey of booleanKeys) {
    updates.push({ settingKey, settingValue: "0" });
  }

  if (updates.length === 0) {
    return { error: null, success: "Değişiklik yok." };
  }

  await prisma.$transaction(
    updates.map((update) =>
      prisma.cryptoSetting.upsert({
        where: { settingKey: update.settingKey },
        update: { settingValue: update.settingValue },
        create: update,
      }),
    ),
  );

  revalidatePath("/admin/payment-settings");

  return { error: null, success: `${updates.length} ayar güncellendi.` };
}
