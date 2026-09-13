/**
 * Throttling for the flows a visitor can reach without logging in, replacing
 * legacy/GuestSecurityManager.php (`guest_rate_limits`).
 */

import { prisma } from "@/lib/prisma";

const MS_PER_SECOND = 1_000;

export type RateLimitRule = {
  action: string;
  maxAttempts: number;
  windowSeconds: number;
  blockSeconds: number;
};

const MINUTES_PER_HOUR = 60;
const SECONDS_PER_MINUTE = 60;
const SECONDS_PER_HOUR = MINUTES_PER_HOUR * SECONDS_PER_MINUTE;

export const GUEST_CHECKOUT_RULE: RateLimitRule = {
  action: "guest_checkout",
  maxAttempts: 10,
  windowSeconds: SECONDS_PER_HOUR,
  blockSeconds: SECONDS_PER_HOUR,
};

export const ORDER_TRACK_RULE: RateLimitRule = {
  action: "order_track",
  maxAttempts: 20,
  windowSeconds: SECONDS_PER_HOUR,
  blockSeconds: 15 * SECONDS_PER_MINUTE,
};

// Contact form submissions get one per minute + a soft hourly cap.
export const CONTACT_FORM_RULE: RateLimitRule = {
  action: "contact_form",
  maxAttempts: 5,
  windowSeconds: SECONDS_PER_HOUR,
  blockSeconds: 30 * SECONDS_PER_MINUTE,
};

export type RateLimitResult = {
  allowed: boolean;
  message: string | null;
};

const ALLOWED: RateLimitResult = { allowed: true, message: null };

/** Fails open: a throttling error must not block a legitimate purchase. */
export async function consumeRateLimit(
  identifier: string,
  rule: RateLimitRule,
): Promise<RateLimitResult> {
  if (identifier === "") {
    return ALLOWED;
  }

  try {
    const now = new Date();
    const existing = await prisma.guestRateLimit.findUnique({
      where: { identifier_action: { identifier, action: rule.action } },
      select: { id: true, attemptCount: true, windowStart: true, blockedUntil: true },
    });

    if (existing === null) {
      await prisma.guestRateLimit.create({
        data: { identifier, action: rule.action, windowStart: now },
      });
      return ALLOWED;
    }

    if (existing.blockedUntil !== null && existing.blockedUntil > now) {
      const minutes = Math.max(
        1,
        Math.ceil(
          (existing.blockedUntil.getTime() - now.getTime()) /
            (MS_PER_SECOND * SECONDS_PER_MINUTE),
        ),
      );

      return {
        allowed: false,
        message: `Çok fazla deneme yaptınız. ${minutes} dakika sonra tekrar deneyin.`,
      };
    }

    const windowExpired =
      now.getTime() - existing.windowStart.getTime() >
      rule.windowSeconds * MS_PER_SECOND;

    if (windowExpired) {
      await prisma.guestRateLimit.update({
        where: { id: existing.id },
        data: { attemptCount: 1, windowStart: now, blockedUntil: null },
      });
      return ALLOWED;
    }

    const attemptCount = existing.attemptCount + 1;

    if (attemptCount > rule.maxAttempts) {
      await prisma.guestRateLimit.update({
        where: { id: existing.id },
        data: {
          attemptCount,
          blockedUntil: new Date(now.getTime() + rule.blockSeconds * MS_PER_SECOND),
        },
      });

      return {
        allowed: false,
        message:
          "Çok fazla deneme yaptınız. Lütfen bir süre sonra tekrar deneyin.",
      };
    }

    await prisma.guestRateLimit.update({
      where: { id: existing.id },
      data: { attemptCount },
    });

    return ALLOWED;
  } catch (error: unknown) {
    console.error("Rate limit kontrolü başarısız", error);
    return ALLOWED;
  }
}
