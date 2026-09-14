"use server";

import { revalidatePath } from "next/cache";
import { requireAdmin } from "@/lib/auth/admin";
import { hashPassword } from "@/lib/auth/password";
import { prisma } from "@/lib/prisma";
import type { AdminFormState } from "@/lib/admin/form-state";

const ADMIN_USERS_PATH = "/admin/users";
const USERNAME_RE = /^[a-zA-Z0-9._-]{3,32}$/;
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const MIN_PASSWORD_LENGTH = 8;

// Re-export the shared form-state type under the users action namespace so
// client components can import it alongside the actions without pulling
// concrete const values through a `"use server"` module (which is forbidden).
export type UserFormState = AdminFormState;

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

/**
 * Creates a new user from the admin panel. Password is hashed with scrypt via
 * the shared password helper — same code path as the customer register flow.
 */
export async function createUserAction(
  _previous: UserFormState,
  formData: FormData,
): Promise<UserFormState> {
  const admin = await requireAdmin();

  const username = String(formData.get("username") ?? "").trim();
  const email = String(formData.get("email") ?? "").trim().toLowerCase();
  const password = String(formData.get("password") ?? "");
  const firstName = String(formData.get("firstName") ?? "").trim();
  const lastName = String(formData.get("lastName") ?? "").trim();
  const phoneRaw = String(formData.get("phone") ?? "").trim();
  const isAdmin = formData.get("isAdmin") === "on";

  if (!USERNAME_RE.test(username)) {
    return {
      error: "Kullanıcı adı 3-32 karakter, harf/rakam/nokta/tire/alt tire içerebilir.",
      success: null,
    };
  }
  if (!EMAIL_RE.test(email) || email.length > 120) {
    return { error: "Geçerli bir e-posta adresi girin.", success: null };
  }
  if (password.length < MIN_PASSWORD_LENGTH) {
    return {
      error: `Şifre en az ${MIN_PASSWORD_LENGTH} karakter olmalı.`,
      success: null,
    };
  }

  const clash = await prisma.user.findFirst({
    where: { OR: [{ username }, { email }] },
    select: { id: true, username: true, email: true },
  });
  if (clash !== null) {
    const field = clash.username === username ? "Kullanıcı adı" : "E-posta";
    return { error: `${field} zaten kayıtlı.`, success: null };
  }

  const passwordHash = await hashPassword(password);

  await prisma.$transaction([
    prisma.user.create({
      data: {
        username,
        email,
        passwordHash,
        // Schema requires firstName/lastName (varchar(50), not null); store an
        // empty string when the admin skips the optional inputs.
        firstName,
        lastName,
        phone: phoneRaw === "" ? null : phoneRaw,
        isActive: true,
        isAdmin,
      },
    }),
    prisma.userActivityLog.create({
      data: {
        activityType: "admin_create_user",
        description: `Kullanıcı ${username} admin ${admin.username} tarafından oluşturuldu.${isAdmin ? " (admin yetkili)" : ""}`,
      },
    }),
  ]);

  revalidatePath(ADMIN_USERS_PATH);
  return { error: null, success: `Kullanıcı ${username} oluşturuldu.` };
}

/**
 * Toggles the isAdmin flag for a target user. Guards against removing the
 * last admin so the panel can't get locked out.
 */
export async function toggleUserAdminAction(formData: FormData): Promise<void> {
  const admin = await requireAdmin();

  const id = Number(formData.get("id"));
  if (Number.isNaN(id) || id === admin.id) {
    // Refuse to demote yourself — legacy behaviour, prevents self-lockout.
    return;
  }

  const target = await prisma.user.findUnique({
    where: { id },
    select: { isAdmin: true, username: true },
  });
  if (target === null) return;

  if (target.isAdmin) {
    // Prevent removing the last admin.
    const adminCount = await prisma.user.count({ where: { isAdmin: true } });
    if (adminCount <= 1) return;
  }

  await prisma.$transaction([
    prisma.user.update({
      where: { id },
      data: { isAdmin: !target.isAdmin },
    }),
    prisma.userActivityLog.create({
      data: {
        userId: id,
        activityType: "admin_toggle_role",
        description: `Yetki ${target.isAdmin ? "kaldırıldı" : "verildi"} (admin: ${admin.username}).`,
      },
    }),
  ]);

  revalidatePath(ADMIN_USERS_PATH);
}

/**
 * Admin-initiated password reset. Overwrites the target hash and invalidates
 * all active sessions so the user is forced to re-login with the new password.
 */
export async function resetUserPasswordAction(
  _previous: UserFormState,
  formData: FormData,
): Promise<UserFormState> {
  const admin = await requireAdmin();

  const id = Number(formData.get("id"));
  const newPassword = String(formData.get("newPassword") ?? "");

  if (Number.isNaN(id)) {
    return { error: "Geçersiz kullanıcı.", success: null };
  }
  if (newPassword.length < MIN_PASSWORD_LENGTH) {
    return {
      error: `Şifre en az ${MIN_PASSWORD_LENGTH} karakter olmalı.`,
      success: null,
    };
  }

  const target = await prisma.user.findUnique({
    where: { id },
    select: { username: true },
  });
  if (target === null) {
    return { error: "Kullanıcı bulunamadı.", success: null };
  }

  const passwordHash = await hashPassword(newPassword);

  await prisma.$transaction([
    prisma.user.update({ where: { id }, data: { passwordHash } }),
    prisma.activeSession.updateMany({
      where: { userId: id, isActive: true },
      data: {
        isActive: false,
        invalidatedBy: "admin",
        invalidatedAt: new Date(),
      },
    }),
    prisma.userActivityLog.create({
      data: {
        userId: id,
        activityType: "admin_password_reset",
        description: `Şifre admin ${admin.username} tarafından sıfırlandı.`,
      },
    }),
  ]);

  revalidatePath(ADMIN_USERS_PATH);
  return {
    error: null,
    success: `${target.username} için yeni şifre kaydedildi.`,
  };
}

/**
 * Permanently deletes a user. Refuses to delete admins or the caller
 * themselves so the panel cannot be locked out. Related rows are removed via
 * Prisma cascade (see schema).
 */
export async function deleteUserAction(formData: FormData): Promise<void> {
  const admin = await requireAdmin();

  const id = Number(formData.get("id"));
  if (Number.isNaN(id) || id === admin.id) return;

  const target = await prisma.user.findUnique({
    where: { id },
    select: {
      isAdmin: true,
      username: true,
      _count: { select: { orders: true, supportTickets: true } },
    },
  });
  if (target === null || target.isAdmin) return;
  if (target._count.orders > 0 || target._count.supportTickets > 0) {
    // Legacy would refuse deletion when the user has orders or tickets; we
    // preserve that guardrail so historical audit data stays consistent.
    // Admin should pasifleştir instead.
    return;
  }

  await prisma.user.delete({ where: { id } });
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
