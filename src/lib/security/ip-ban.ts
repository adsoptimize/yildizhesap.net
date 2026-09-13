/**
 * IP ban lookup, ported from legacy/IPBanManager.php::isIPBanned.
 *
 * The legacy header hit the database on every page load. The proxy runs for
 * every request here, so the active ban list (a handful of rows) is cached in
 * memory for a short window instead.
 */

import { prisma } from "@/lib/prisma";

const CACHE_TTL_MS = 60_000;

export type ActiveBan = {
  ipAddress: string;
  reason: string;
  banType: "temporary" | "permanent";
  bannedUntil: Date | null;
  createdAt: Date;
};

type BanCache = {
  loadedAt: number;
  bans: Map<string, ActiveBan>;
};

let cache: BanCache | null = null;

async function loadBans(): Promise<Map<string, ActiveBan>> {
  const rows = await prisma.ipBan.findMany({
    where: {
      isActive: true,
      OR: [{ banType: "permanent" }, { bannedUntil: { gt: new Date() } }],
    },
    orderBy: { createdAt: "desc" },
    select: {
      ipAddress: true,
      reason: true,
      banType: true,
      bannedUntil: true,
      createdAt: true,
    },
  });

  const bans = new Map<string, ActiveBan>();

  for (const row of rows) {
    if (!bans.has(row.ipAddress)) {
      bans.set(row.ipAddress, row);
    }
  }

  return bans;
}

export async function getActiveBan(
  ipAddress: string,
): Promise<ActiveBan | null> {
  const now = Date.now();

  if (cache === null || now - cache.loadedAt > CACHE_TTL_MS) {
    try {
      cache = { loadedAt: now, bans: await loadBans() };
    } catch {
      // Never lock the site out because the ban table is unreachable.
      return null;
    }
  }

  const ban = cache.bans.get(ipAddress) ?? null;

  if (ban === null) {
    return null;
  }

  if (
    ban.banType === "temporary" &&
    ban.bannedUntil !== null &&
    ban.bannedUntil.getTime() <= now
  ) {
    return null;
  }

  return ban;
}

/** Called after admin ban mutations so the block takes effect immediately. */
export function invalidateBanCache(): void {
  cache = null;
}
