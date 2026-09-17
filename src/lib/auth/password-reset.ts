/**
 * Self-service password reset.
 *
 * The legacy schema already carried `reset_token` / `reset_token_expires` on
 * the users table but no PHP code ever used them, so the feature was never
 * finished. This completes it on the same columns.
 *
 * Only the SHA-256 digest of the token is stored. A database leak therefore
 * cannot be turned into account takeovers, and the digest is 64 hex characters
 * wide — exactly the legacy column, so no migration is needed.
 */

import { createHash, randomBytes } from "node:crypto";
import { hashPassword } from "@/lib/auth/password";
import { logUserActivity } from "@/lib/auth/session";
import { MIN_PASSWORD_LENGTH } from "@/lib/shop/constants";
import { prisma } from "@/lib/prisma";

const TOKEN_BYTES = 32;
const TOKEN_HEX_LENGTH = TOKEN_BYTES * 2;
/** Short enough to limit the window on a leaked inbox, long enough to be usable. */
export const RESET_TOKEN_TTL_MINUTES = 60;
const MS_PER_MINUTE = 60_000;

export type IssuedReset = {
  email: string;
  firstName: string;
  /** Plain token; only ever leaves the server inside the e-mail link. */
  token: string;
};

function digest(token: string): string {
  return createHash("sha256").update(token).digest("hex");
}

/**
 * Returns null when there is no account to reset. Callers must still report
 * success to the visitor: a different answer would turn this form into a way
 * to discover which e-mail addresses are registered.
 */
export async function issuePasswordResetToken(
  email: string,
): Promise<IssuedReset | null> {
  const normalised = email.trim().toLowerCase();

  if (normalised === "") {
    return null;
  }

  const user = await prisma.user.findFirst({
    where: { email: normalised },
    select: { id: true, email: true, firstName: true, isActive: true },
  });

  // Deactivated accounts cannot log in, so a new password would not help.
  if (user === null || !user.isActive) {
    return null;
  }

  const token = randomBytes(TOKEN_BYTES).toString("hex");

  await prisma.user.update({
    where: { id: user.id },
    data: {
      resetToken: digest(token),
      resetTokenExpires: new Date(Date.now() + RESET_TOKEN_TTL_MINUTES * MS_PER_MINUTE),
    },
  });

  return { email: user.email, firstName: user.firstName, token };
}

export type ResetOutcome = "ok" | "invalid" | "expired" | "weak_password";

export async function consumePasswordResetToken(
  token: string,
  newPassword: string,
): Promise<ResetOutcome> {
  // Checked before hitting the database: anything else cannot be one of ours.
  if (!/^[0-9a-f]+$/.test(token) || token.length !== TOKEN_HEX_LENGTH) {
    return "invalid";
  }

  if (newPassword.length < MIN_PASSWORD_LENGTH) {
    return "weak_password";
  }

  const user = await prisma.user.findFirst({
    where: { resetToken: digest(token) },
    select: { id: true, resetTokenExpires: true },
  });

  if (user === null) {
    return "invalid";
  }

  if (user.resetTokenExpires === null || user.resetTokenExpires < new Date()) {
    return "expired";
  }

  await prisma.user.update({
    where: { id: user.id },
    data: {
      passwordHash: await hashPassword(newPassword),
      resetToken: null,
      resetTokenExpires: null,
      // A locked-out account should be usable again after a successful reset.
      loginAttempts: 0,
      lastLoginAttempt: null,
    },
  });

  // Whoever knew the old password — including an intruder — is signed out.
  await prisma.activeSession.updateMany({
    where: { userId: user.id, isActive: true },
    data: {
      isActive: false,
      invalidatedBy: "security",
      invalidatedAt: new Date(),
    },
  });

  await logUserActivity(user.id, "password_reset", "Şifre e-posta ile sıfırlandı");

  return "ok";
}
