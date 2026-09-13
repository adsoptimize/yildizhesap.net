/**
 * Popup advertisement logic, ported from legacy/includes/PopupAd.php.
 *
 * Frequency is counted per day and per content hash, so editing the ad in the
 * admin panel makes it visible again to everyone — the same behaviour as the
 * legacy content_hash column.
 */

import { createHash, randomBytes } from "node:crypto";
import { cookies } from "next/headers";
import { prisma } from "@/lib/prisma";

const SETTING_KEYS = {
  enabled: "popup_ad_enabled",
  content: "popup_ad_content",
  frequency: "popup_ad_frequency",
  target: "popup_ad_target",
} as const;

const VISITOR_COOKIE = "popup_visitor_id";
const VISITOR_COOKIE_MAX_AGE_SECONDS = 30 * 24 * 60 * 60;
const VISITOR_ID_BYTES = 16;
const DEFAULT_FREQUENCY = 1;
const CONTENT_HASH_ALGORITHM = "sha256";

/** Same whitelist as legacy strip_tags(); everything else is dropped. */
const ALLOWED_TAGS = new Set([
  "p",
  "br",
  "strong",
  "b",
  "em",
  "i",
  "u",
  "a",
  "img",
  "div",
  "span",
  "h1",
  "h2",
  "h3",
  "h4",
  "h5",
  "h6",
  "ul",
  "ol",
  "li",
]);

type PopupSettings = {
  enabled: boolean;
  content: string;
  frequency: number;
  target: string;
};

async function readSettings(): Promise<PopupSettings> {
  const rows = await prisma.siteSetting.findMany({
    where: { settingKey: { in: Object.values(SETTING_KEYS) } },
    select: { settingKey: true, settingValue: true },
  });

  const byKey = new Map(rows.map((row) => [row.settingKey, row.settingValue]));
  const frequency = Number.parseInt(
    byKey.get(SETTING_KEYS.frequency) ?? "",
    10,
  );

  return {
    enabled: byKey.get(SETTING_KEYS.enabled) === "1",
    content: byKey.get(SETTING_KEYS.content) ?? "",
    frequency: Number.isNaN(frequency) ? DEFAULT_FREQUENCY : frequency,
    target: byKey.get(SETTING_KEYS.target) ?? "both",
  };
}

function contentHashOf(settings: PopupSettings): string {
  return createHash(CONTENT_HASH_ALGORITHM)
    .update(`${settings.content}|${settings.frequency}|${settings.target}`)
    .digest("hex");
}

/** Strips every tag outside the legacy whitelist plus inline event handlers. */
export function sanitizePopupContent(raw: string): string {
  return raw
    .replace(/<script\b[\s\S]*?<\/script>/gi, "")
    .replace(/<\/?([a-z0-9]+)([^>]*)>/gi, (match, rawTag: string) => {
      return ALLOWED_TAGS.has(rawTag.toLowerCase()) ? match : "";
    })
    .replace(/\son\w+\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi, "")
    .replace(/javascript:/gi, "");
}

/** Cookie-backed id so a visitor is not re-counted per request. */
async function resolveVisitorId(): Promise<string> {
  const cookieStore = await cookies();
  const existing = cookieStore.get(VISITOR_COOKIE)?.value;

  if (existing !== undefined && existing !== "") {
    return existing;
  }

  const visitorId = `visitor_${randomBytes(VISITOR_ID_BYTES).toString("hex")}`;

  cookieStore.set(VISITOR_COOKIE, visitorId, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge: VISITOR_COOKIE_MAX_AGE_SECONDS,
  });

  return visitorId;
}

function startOfToday(): Date {
  const now = new Date();
  return new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate()));
}

export type PopupPayload = {
  content: string;
  contentHash: string;
};

/**
 * Returns the ad to render, or null when it is disabled, the audience does not
 * match, or today's frequency cap is already reached.
 */
export async function resolvePopupAd(
  userId: number | null,
): Promise<PopupPayload | null> {
  const settings = await readSettings();

  if (!settings.enabled || settings.content.trim() === "") {
    return null;
  }

  if (settings.target === "visitors" && userId !== null) {
    return null;
  }

  if (settings.target === "users" && userId === null) {
    return null;
  }

  const contentHash = contentHashOf(settings);
  const visitorId = await resolveVisitorId();

  const view = await prisma.popupAdView.findFirst({
    where:
      userId === null
        ? { visitorId, userId: null, viewDate: startOfToday(), contentHash }
        : { userId, viewDate: startOfToday(), contentHash },
    select: { viewCount: true },
  });

  if ((view?.viewCount ?? 0) >= settings.frequency) {
    return null;
  }

  return { content: sanitizePopupContent(settings.content), contentHash };
}

/** Called once the browser has actually displayed the ad. */
export async function recordPopupView(
  userId: number | null,
  contentHash: string,
  ipAddress: string,
): Promise<void> {
  const visitorId = await resolveVisitorId();
  const viewDate = startOfToday();

  const existing = await prisma.popupAdView.findFirst({
    where:
      userId === null
        ? { visitorId, userId: null, viewDate, contentHash }
        : { userId, viewDate, contentHash },
    select: { id: true },
  });

  if (existing === null) {
    await prisma.popupAdView.create({
      data: { ipAddress, userId, viewDate, contentHash, visitorId },
    });
    return;
  }

  await prisma.popupAdView.update({
    where: { id: existing.id },
    data: { viewCount: { increment: 1 } },
  });
}
