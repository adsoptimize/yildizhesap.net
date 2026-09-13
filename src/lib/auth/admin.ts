import { redirect } from "next/navigation";
import { getSessionUser, type SessionUser } from "@/lib/auth/session";

export const ADMIN_LOGIN_PATH = "/admin/login";

/** Guard for every admin segment: non-admins never reach the page body. */
export async function requireAdmin(): Promise<SessionUser> {
  const user = await getSessionUser();

  if (user === null || !user.isAdmin) {
    redirect(ADMIN_LOGIN_PATH);
  }

  return user;
}
