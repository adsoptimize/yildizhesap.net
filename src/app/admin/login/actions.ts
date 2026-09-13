"use server";

import { redirect } from "next/navigation";
import { verifyPassword } from "@/lib/auth/password";
import { createSession, logUserActivity } from "@/lib/auth/session";
import { prisma } from "@/lib/prisma";

export type LoginState = {
  error: string | null;
};

const GENERIC_ERROR = "Kullanıcı adı veya şifre hatalı.";

export async function loginAction(
  _previousState: LoginState,
  formData: FormData,
): Promise<LoginState> {
  const identifier = String(formData.get("identifier") ?? "").trim();
  const password = String(formData.get("password") ?? "");

  if (identifier === "" || password === "") {
    return { error: "Kullanıcı adı ve şifre zorunludur." };
  }

  const user = await prisma.user.findFirst({
    where: {
      isAdmin: true,
      isActive: true,
      OR: [{ username: identifier }, { email: identifier.toLowerCase() }],
    },
    select: { id: true, passwordHash: true },
  });

  if (user === null) {
    return { error: GENERIC_ERROR };
  }

  const valid = await verifyPassword(password, user.passwordHash);

  if (!valid) {
    await prisma.user.update({
      where: { id: user.id },
      data: {
        loginAttempts: { increment: 1 },
        lastLoginAttempt: new Date(),
      },
    });
    return { error: GENERIC_ERROR };
  }

  await prisma.user.update({
    where: { id: user.id },
    data: { loginAttempts: 0, lastLogin: new Date() },
  });

  await createSession(user.id);
  await logUserActivity(user.id, "admin_login", "Yönetim paneline giriş yapıldı");

  redirect("/admin");
}
