import { redirect } from "next/navigation";
import { getSessionUser, type SessionUser } from "@/lib/auth/session";

export const CUSTOMER_LOGIN_PATH = "/giris-yap";
export const CUSTOMER_REGISTER_PATH = "/kayit-ol";

export async function getCurrentCustomer(): Promise<SessionUser | null> {
  return getSessionUser();
}

/** Guard for the customer panel; admins can browse it as well. */
export async function requireCustomer(): Promise<SessionUser> {
  const user = await getSessionUser();

  if (user === null) {
    redirect(CUSTOMER_LOGIN_PATH);
  }

  return user;
}
