"use server";

import { revalidatePath } from "next/cache";
import { requireAdmin } from "@/lib/auth/admin";
import { prisma } from "@/lib/prisma";

const ADMIN_USERS_PATH = "/admin/users";

/** Status changes are journalled in user_status_history like the legacy panel. */
export async function toggleUserStatusAction(formData: FormData): Promise<void> {
  const admin = await requireAdmin();

  const id = Number(formData.get("id"));
  const reason = String(formData.get("reason") ?? "").trim();

  const user = await prisma.user.findUnique({
    where: { id },
    select: { isActive: true, isAdmin: true },
  });

  if (user === null || user.isAdmin) {
    return;
  }

  const nextActive = !user.isActive;

  await prisma.$transaction([
    prisma.user.update({
      where: { id },
      data: {
        isActive: nextActive,
        deactivationReason: nextActive ? null : reason === "" ? null : reason,
        deactivatedBy: nextActive ? null : admin.id,
      },
    }),
    prisma.userStatusHistory.create({
      data: {
        userId: id,
        oldStatus: user.isActive ? "active" : "inactive",
        newStatus: nextActive ? "active" : "inactive",
        reason: reason === "" ? null : reason,
        changedBy: admin.id,
      },
    }),
    prisma.activeSession.updateMany({
      where: { userId: id, isActive: true },
      data: {
        isActive: false,
        invalidatedBy: "admin",
        invalidatedAt: new Date(),
      },
    }),
  ]);

  revalidatePath(ADMIN_USERS_PATH);
}

export async function updateUserBalanceAction(formData: FormData): Promise<void> {
  const admin = await requireAdmin();

  const id = Number(formData.get("id"));
  const raw = String(formData.get("balance") ?? "").trim().replace(",", ".");
  const parsed = Number(raw);

  if (!Number.isFinite(parsed) || parsed < 0) {
    return;
  }

  await prisma.user.update({
    where: { id },
    data: { balance: parsed.toFixed(2) },
  });

  await prisma.userActivityLog.create({
    data: {
      userId: id,
      activityType: "balance_update",
      description: `Bakiye ${parsed.toFixed(2)} TL olarak güncellendi (admin: ${admin.username})`,
    },
  });

  revalidatePath(ADMIN_USERS_PATH);
}
