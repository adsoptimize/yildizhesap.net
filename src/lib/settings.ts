/**
 * Typed accessors over the key/value site_settings rows so components never
 * parse raw strings inline.
 */

import type { SettingsMap } from "@/lib/db/queries";

export type HeroStat = {
  number: string;
  label: string;
};

export type MenuLink = {
  name: string;
  url: string;
  icon?: string;
};

export type FooterLinkGroup = {
  title: string;
  links: { name: string; url: string }[];
};

export type SocialLink = {
  platform: string;
  url: string;
  icon: string;
};

export function getSetting(
  settings: SettingsMap,
  key: string,
  fallback: string,
): string {
  const value = settings[key];
  return value === undefined || value.trim() === "" ? fallback : value;
}

function parseJsonSetting<T>(
  settings: SettingsMap,
  key: string,
  fallback: T[],
): T[] {
  const raw = settings[key];

  if (raw === undefined || raw.trim() === "") {
    return fallback;
  }

  try {
    const parsed: unknown = JSON.parse(raw);
    return Array.isArray(parsed) ? (parsed as T[]) : fallback;
  } catch {
    return fallback;
  }
}

export function getHeroStats(settings: SettingsMap): HeroStat[] {
  return parseJsonSetting<HeroStat>(settings, "hero_stats", []);
}

export function getFooterLinkGroups(settings: SettingsMap): FooterLinkGroup[] {
  return parseJsonSetting<FooterLinkGroup>(settings, "footer_links", []);
}

export function getSocialLinks(settings: SettingsMap): SocialLink[] {
  return parseJsonSetting<SocialLink>(settings, "footer_social", []);
}

export function digitsOnly(value: string): string {
  return value.replace(/[^0-9]/g, "");
}
