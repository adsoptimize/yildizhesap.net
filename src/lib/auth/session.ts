/**
 * Database-backed sessions, stored in the legacy `active_sessions` table so the
 * admin "Session Yönetimi" screen keeps working the same way.
 */

import { createHash, randomBytes } from "node:crypto";
import { cookies, headers } from "next/headers";
import { prisma } from "@/lib/prisma";

export const SESSION_COOKIE = "yh_session";

const SESSION_TOKEN_BYTES = 32;
const SESSION_TTL_HOURS = 12;
const MS_PER_HOUR = 3_600_000;
const UNKNOWN_VALUE = "unknown";

export type SessionUser = {
  id: number;
  username: string;
  email: string;
  firstName: string;
  lastName: string;
  isAdmin: boolean;
};

type RequestContext = {
  ipAddress: string;
  userAgent: string;
  deviceFingerprint: string;
};

async function readRequestContext(): Promise<RequestContext> {
  const headerList = await headers();
  const forwardedFor = headerList.get("x-forwarded-for");
  const ipAddress =
    forwardedFor?.split(",")[0]?.trim() ??
    headerList.get("x-real-ip") ??
    UNKNOWN_VALUE;
  const userAgent = headerList.get("user-agent") ?? UNKNOWN_VALUE;

  return {
    ipAddress,
    userAgent,
    deviceFingerprint: createHash("sha256")
      .update(`${ipAddress}|${userAgent}`)
      .digest("hex"),
  };
}

export async function createSession(userId: number): Promise<void> {
  const token = randomBytes(SESSION_TOKEN_BYTES).toString("hex");
  const context = await readRequestContext();
  const expiresAt = new Date(Date.now() + SESSION_TTL_HOURS * MS_PER_HOUR);

  await prisma.activeSession.create({
    data: {
      sessionToken: token,
      userId,
      ipAddress: context.ipAddress,
      userAgent: context.userAgent,
      deviceFingerprint: context.deviceFingerprint,
      expiresAt,
    },
  });

  const cookieStore = await cookies();
  cookieStore.set(SESSION_COOKIE, token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    expires: expiresAt,
  });
}

export async function getSessionUser(): Promise<SessionUser | null> {
  const cookieStore = await cookies();
  const token = cookieStore.get(SESSION_COOKIE)?.value;

  if (token === undefined) {
    return null;
  }

  const session = await prisma.activeSession.findUnique({
    where: { sessionToken: token },
    select: {
      id: true,
      expiresAt: true,
      isActive: true,
      user: {
        select: {
          id: true,
          username: true,
          email: true,
          firstName: true,
          lastName: true,
          isAdmin: true,
          isActive: true,
        },
      },
    },
  });

  if (
    session === null ||
    !session.isActive ||
    session.expiresAt.getTime() < Date.now() ||
    !session.user.isActive
  ) {
    return null;
  }

  await prisma.activeSession.update({
    where: { id: session.id },
    data: { lastActivity: new Date() },
  });

  return {
    id: session.user.id,
    username: session.user.username,
    email: session.user.email,
    firstName: session.user.firstName,
    lastName: session.user.lastName,
    isAdmin: session.user.isAdmin,
  };
}

export async function destroySession(): Promise<void> {
  const cookieStore = await cookies();
  const token = cookieStore.get(SESSION_COOKIE)?.value;

  if (token !== undefined) {
    await prisma.activeSession.updateMany({
      where: { sessionToken: token, isActive: true },
      data: {
        isActive: false,
        invalidatedBy: "user",
        invalidatedAt: new Date(),
      },
    });
  }

  cookieStore.delete(SESSION_COOKIE);
}

export async function logUserActivity(
  userId: number,
  activityType: string,
  description: string,
): Promise<void> {
  const context = await readRequestContext();

  await prisma.userActivityLog.create({
    data: {
      userId,
      activityType,
      description,
      ipAddress: context.ipAddress,
      userAgent: context.userAgent,
    },
  });
}
