"use server";

import { revalidatePath } from "next/cache";
import { requireAdmin } from "@/lib/auth/admin";
import type { AdminFormState } from "@/lib/admin/form-state";
import { revalidateStorefront } from "@/lib/admin/revalidate";
import { prisma } from "@/lib/prisma";

const MIN_FREQUENCY = 1;
const MAX_FREQUENCY = 10;
const VALID_TARGETS = new Set(["visitors", "users", "both"]);
const VIEW_RETENTION_DAYS = 30;
const MS_PER_DAY = 86_400_000;

const POPUP_KEYS = {
  enabled: "popup_ad_enabled",
  content: "popup_ad_content",
  frequency: "popup_ad_frequency",
  target: "popup_ad_target",
} as const;

async function upsertSetting(
  settingKey: string,
  settingValue: string,
  description: string,
): Promise<void> {
  await prisma.siteSetting.upsert({
    where: { settingKey },
    update: { settingValue },
    create: {
      settingKey,
      settingValue,
      settingType: "text",
      description,
      category: "ads",
    },
  });
}

export async function savePopupAdAction(
  _previousState: AdminFormState,
  formData: FormData,
): Promise<AdminFormState> {
  await requireAdmin();

  const enabled = formData.get("enabled") === "on" ? "1" : "0";
  const content = String(formData.get("content") ?? "");
  const rawFrequency = Number.parseInt(String(formData.get("frequency") ?? ""), 10);
  const target = String(formData.get("target") ?? "both");

  const frequency =
    Number.isNaN(rawFrequency) || rawFrequency < MIN_FREQUENCY || rawFrequency > MAX_FREQUENCY
      ? MIN_FREQUENCY
      : rawFrequency;

  await upsertSetting(POPUP_KEYS.enabled, enabled, "Popup reklam aktif mi");
  await upsertSetting(POPUP_KEYS.content, content, "Popup içeriği (HTML)");
  await upsertSetting(
    POPUP_KEYS.frequency,
    String(frequency),
    "Gün içinde gösterim sayısı",
  );
  await upsertSetting(
    POPUP_KEYS.target,
    VALID_TARGETS.has(target) ? target : "both",
    "Popup hedef kitlesi",
  );

  revalidateStorefront();
  revalidatePath("/admin/advertisements");

  return { error: null, success: "Popup ayarları kaydedildi." };
}

export async function resetPopupViewsAction(): Promise<void> {
  await requireAdmin();

  await prisma.popupAdView.deleteMany({});
  revalidatePath("/admin/advertisements");
}

export async function cleanupPopupViewsAction(): Promise<void> {
  await requireAdmin();

  const threshold = new Date(Date.now() - VIEW_RETENTION_DAYS * MS_PER_DAY);

  await prisma.popupAdView.deleteMany({ where: { viewDate: { lt: threshold } } });
  revalidatePath("/admin/advertisements");
}
