"use server";

import { revalidatePath } from "next/cache";
import { requireAdmin } from "@/lib/auth/admin";
import { prisma } from "@/lib/prisma";

const ADMIN_SESSIONS_PATH = "/admin/sessions";

export async function invalidateSessionAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const id = Number(formData.get("id"));

  await prisma.activeSession.update({
    where: { id },
    data: {
      isActive: false,
      invalidatedBy: "admin",
      invalidatedAt: new Date(),
    },
  });

  revalidatePath(ADMIN_SESSIONS_PATH);
}

export async function invalidateUserSessionsAction(
  formData: FormData,
): Promise<void> {
  await requireAdmin();

  const userId = Number(formData.get("userId"));

  await prisma.activeSession.updateMany({
    where: { userId, isActive: true },
    data: {
      isActive: false,
      invalidatedBy: "admin",
      invalidatedAt: new Date(),
    },
  });

  revalidatePath(ADMIN_SESSIONS_PATH);
}

export async function cleanupExpiredSessionsAction(): Promise<void> {
  await requireAdmin();

  await prisma.activeSession.updateMany({
    where: { isActive: true, expiresAt: { lt: new Date() } },
    data: {
      isActive: false,
      invalidatedBy: "expired",
      invalidatedAt: new Date(),
    },
  });

  revalidatePath(ADMIN_SESSIONS_PATH);
}
