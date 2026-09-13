"use server";

import { redirect } from "next/navigation";
import { ADMIN_LOGIN_PATH } from "@/lib/auth/admin";
import { destroySession } from "@/lib/auth/session";

export async function logoutAction(): Promise<void> {
  await destroySession();
  redirect(ADMIN_LOGIN_PATH);
}
