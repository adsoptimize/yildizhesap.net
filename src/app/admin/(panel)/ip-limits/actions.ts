"use server";

import { revalidatePath } from "next/cache";
import { BanType } from "@prisma/client";
import { requireAdmin } from "@/lib/auth/admin";
import type { AdminFormState } from "@/lib/admin/form-state";
import { invalidateBanCache } from "@/lib/security/ip-ban";
import { prisma } from "@/lib/prisma";

const ADMIN_IP_PATH = "/admin/ip-limits";
const MS_PER_DAY = 86_400_000;
const IP_LIMIT_KEYS = ["max_accounts_per_ip", "ip_limit_days", "ip_limit_enabled"];

export async function saveIpLimitSettingsAction(
  _previousState: AdminFormState,
  formData: FormData,
): Promise<AdminFormState> {
  await requireAdmin();

  const maxAccounts = Number.parseInt(String(formData.get("maxAccounts") ?? ""), 10);
  const limitDays = Number.parseInt(String(formData.get("limitDays") ?? ""), 10);
  const enabled = formData.get("enabled") === "on" ? "1" : "0";

  if (Number.isNaN(maxAccounts) || maxAccounts < 1) {
    return { error: "IP başına hesap sayısı en az 1 olmalı.", success: null };
  }

  if (Number.isNaN(limitDays) || limitDays < 1) {
    return { error: "Limit süresi en az 1 gün olmalı.", success: null };
  }

  const values: Readonly<Record<string, string>> = {
    max_accounts_per_ip: String(maxAccounts),
    ip_limit_days: String(limitDays),
    ip_limit_enabled: enabled,
  };

  await prisma.$transaction(
    IP_LIMIT_KEYS.map((settingKey) =>
      prisma.siteSetting.update({
        where: { settingKey },
        data: { settingValue: values[settingKey] },
      }),
    ),
  );

  revalidatePath(ADMIN_IP_PATH);
  return { error: null, success: "IP limit ayarları güncellendi." };
}

export async function banIpAction(formData: FormData): Promise<void> {
  const admin = await requireAdmin();

  const ipAddress = String(formData.get("ipAddress") ?? "").trim();
  const reason = String(formData.get("reason") ?? "").trim();
  const days = Number.parseInt(String(formData.get("days") ?? ""), 10);

  if (ipAddress === "" || reason === "") {
    return;
  }

  const permanent = Number.isNaN(days) || days <= 0;

  await prisma.ipBan.create({
    data: {
      ipAddress,
      reason,
      banType: permanent ? BanType.permanent : BanType.temporary,
      bannedUntil: permanent ? null : new Date(Date.now() + days * MS_PER_DAY),
      bannedBy: admin.id,
    },
  });

  invalidateBanCache();
  revalidatePath(ADMIN_IP_PATH);
}

export async function liftIpBanAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const id = Number(formData.get("id"));

  await prisma.ipBan.update({
    where: { id },
    data: { isActive: false },
  });

  invalidateBanCache();
  revalidatePath(ADMIN_IP_PATH);
}

/** Legacy behaviour: log the reset so the IP can register again. */
export async function resetIpLimitAction(formData: FormData): Promise<void> {
  const admin = await requireAdmin();

  const ipAddress = String(formData.get("ipAddress") ?? "").trim();
  const reason = String(formData.get("reason") ?? "").trim();

  if (ipAddress === "") {
    return;
  }

  const accountsBeforeReset = await prisma.user.count({
    where: { registrationIp: ipAddress },
  });

  await prisma.ipLimitReset.create({
    data: {
      ipAddress,
      resetBy: admin.id,
      accountsBeforeReset,
      reason: reason === "" ? null : reason,
    },
  });

  revalidatePath(ADMIN_IP_PATH);
}
