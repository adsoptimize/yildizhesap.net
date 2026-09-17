"use server";

import { revalidatePath } from "next/cache";
import { requireAdmin } from "@/lib/auth/admin";
import type { AdminFormState } from "@/lib/admin/form-state";
import { sendTemplatePreviewEmail } from "@/lib/notify/order-email";
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

/**
 * The mailer swallows SMTP errors so a paid order is never rolled back by a
 * failed notification, which also makes a misconfiguration invisible. This is
 * the operator's way to confirm the saved settings actually work.
 */
export async function sendTestEmailAction(
  _previousState: AdminFormState,
  formData: FormData,
): Promise<AdminFormState> {
  await requireAdmin();

  const to = String(formData.get("test_email") ?? "").trim();

  if (to === "") {
    return { error: "Test için bir e-posta adresi girin.", success: null };
  }

  const sent = await sendTemplatePreviewEmail(to);

  if (!sent) {
    return {
      error:
        "Gönderilemedi. Ayarları kaydettiğinizden, 'E-posta gönderimi aktif' kutusunu işaretlediğinizden ve şifrenin doğru olduğundan emin olun.",
      success: null,
    };
  }

  return { error: null, success: `Test e-postası ${to} adresine gönderildi.` };
}
