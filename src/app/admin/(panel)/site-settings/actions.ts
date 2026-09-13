"use server";

import { revalidatePath } from "next/cache";
import { requireAdmin } from "@/lib/auth/admin";
import { revalidateStorefront } from "@/lib/admin/revalidate";
import type { AdminFormState } from "@/lib/admin/form-state";
import { prisma } from "@/lib/prisma";

const SETTING_FIELD_PREFIX = "setting__";

export async function saveSiteSettingsAction(
  _previousState: AdminFormState,
  formData: FormData,
): Promise<AdminFormState> {
  await requireAdmin();

  const updates: { settingKey: string; settingValue: string }[] = [];
  const checkboxKeys = new Set(
    formData
      .getAll("boolean_key")
      .map((value) => String(value))
      .filter((value) => value !== ""),
  );

  for (const [field, value] of formData.entries()) {
    if (!field.startsWith(SETTING_FIELD_PREFIX)) {
      continue;
    }

    const settingKey = field.slice(SETTING_FIELD_PREFIX.length);
    updates.push({ settingKey, settingValue: String(value) });
    checkboxKeys.delete(settingKey);
  }

  // Unchecked checkboxes are absent from the payload, so persist them as "0".
  for (const settingKey of checkboxKeys) {
    updates.push({ settingKey, settingValue: "0" });
  }

  if (updates.length === 0) {
    return { error: "Kaydedilecek alan bulunamadı.", success: null };
  }

  await prisma.$transaction(
    updates.map((update) =>
      prisma.siteSetting.update({
        where: { settingKey: update.settingKey },
        data: { settingValue: update.settingValue },
      }),
    ),
  );

  revalidateStorefront();
  revalidatePath("/admin/site-settings");

  return { error: null, success: `${updates.length} ayar güncellendi.` };
}
