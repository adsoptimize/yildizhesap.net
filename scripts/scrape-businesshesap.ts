/**
 * Snapshots the businesshesap.com catalog into
 * `prisma/businesshesap-catalog.json` for offline seeding.
 *
 * Each category page on businesshesap.com is a single-product page: it exposes
 * one title, one price, one stock counter, and one long description block.
 * Run this script manually whenever the source catalog changes:
 *
 *   npx tsx scripts/scrape-businesshesap.ts
 */

import { mkdir, writeFile } from "node:fs/promises";
import path from "node:path";

const BASE = "https://www.businesshesap.com";
const OUTPUT = path.join(process.cwd(), "prisma", "businesshesap-catalog.json");
const CONCURRENCY = 2;
const REQUEST_DELAY_MS = 750;
const MAX_RETRIES = 4;

const sleep = (ms: number): Promise<void> =>
  new Promise((resolve) => setTimeout(resolve, ms));

/** Category slugs on businesshesap.com are `/kategori/{id}-{slug}`. */
const CATEGORY_URLS: readonly string[] = [
  `${BASE}/kategori/1-facebook-instagram-reklamlariniz-acilir`,
  `${BASE}/kategori/2-satilik-instagram-hesaplari`,
  `${BASE}/kategori/3-business-manager-satin-al`,
  `${BASE}/kategori/4-eski-business-manager-satin-al`,
  `${BASE}/kategori/5-facebook-hesaplari-paket-3`,
  `${BASE}/kategori/6-facebook-hesaplari-kimlik-onayli`,
  `${BASE}/kategori/7-5li-business-manager-satin-al`,
  `${BASE}/kategori/8-satilik-eski-facebook-sayfalari-satin-al`,
  `${BASE}/kategori/9-organik-yorum-hizmeti`,
  `${BASE}/kategori/10-facebook-hesaplari-turk-1-paket`,
  `${BASE}/kategori/11-facebook-hesaplari-turk-2-paket`,
  `${BASE}/kategori/12-onaylacom-smsonayservisi`,
  `${BASE}/kategori/13-discort-hesap-satin-al`,
  `${BASE}/kategori/14-instagram-hesaplari-1000-takipcili`,
  `${BASE}/kategori/15-instagram-hesaplari-5000-takipcili`,
  `${BASE}/kategori/16-instagram-hesaplari-10000-takipcili`,
  `${BASE}/kategori/17-tanitim-onayli-instagram-hesaplari`,
  `${BASE}/kategori/18-onayli-gmail-hesaplari`,
  `${BASE}/kategori/19-onayli-x-hesaplari-satin-al`,
  `${BASE}/kategori/20-outlook-hesaplari`,
  `${BASE}/kategori/21-tiktok-hesaplari`,
  `${BASE}/kategori/22-satilik-telegram-hesaplari`,
  `${BASE}/kategori/23-dogrulanmis-business-manager-hesaplari`,
  `${BASE}/kategori/24-5li-business-manager-satin-al`,
  `${BASE}/kategori/25-gunluk-250dolar-limitli-tekli-kisisel-reklam-hesaplari`,
  `${BASE}/kategori/26-facebook-reklam-hesabi-kisisel-10lu`,
  `${BASE}/kategori/27-facebook-marketplace-hesap-satin-al`,
  `${BASE}/kategori/28-kisittan-donmus-business-manager`,
  `${BASE}/kategori/29-250usd-tekli-business-manager`,
  `${BASE}/kategori/30-facebook-30-60-li-reklam-hesabi-satin-al`,
  `${BASE}/kategori/31-dogrulanmis-business-manager-5li`,
  `${BASE}/kategori/32-gonderili-instagram-hesaplari`,
  `${BASE}/kategori/33-2012-tarihli-instagram-hesap-al`,
  `${BASE}/kategori/34-mavi-tik-alinan-instagram-hesaplari`,
  `${BASE}/kategori/35-10-adet-gonderili-instagram-hesaplari-eski`,
  `${BASE}/kategori/36-gonderili-instagram-hesaplari`,
  `${BASE}/kategori/37-harcama-yapmis-facebook-hesaplari-sayfali`,
  `${BASE}/kategori/38-reklam-erisimi-eklenmis-facebook-hesaplari`,
  `${BASE}/kategori/39-ads-aktif-mail-onayli-sayfali-facebook-hesaplari`,
];

export type ScrapedProduct = {
  sourceId: number;
  slug: string;
  title: string;
  metaTitle: string;
  metaDescription: string | null;
  metaKeywords: string | null;
  priceTry: number;
  stock: number;
  descriptionHtml: string;
  descriptionText: string;
};

const HTML_ENTITY: Readonly<Record<string, string>> = {
  amp: "&",
  lt: "<",
  gt: ">",
  quot: '"',
  apos: "'",
  nbsp: " ",
  "#39": "'",
};

function decodeEntities(input: string): string {
  return input.replace(/&(#?\w+);/g, (match, key: string) => {
    const named = HTML_ENTITY[key];
    if (named !== undefined) {
      return named;
    }
    if (key.startsWith("#")) {
      const codePoint = Number.parseInt(key.slice(1), 10);
      if (Number.isFinite(codePoint)) {
        return String.fromCodePoint(codePoint);
      }
    }
    return match;
  });
}

function stripTags(html: string): string {
  return decodeEntities(html.replace(/<[^>]+>/g, " "))
    .replace(/\s+/g, " ")
    .trim();
}

function matchOne(html: string, regex: RegExp): string | null {
  const match = regex.exec(html);
  return match === null ? null : match[1];
}

function parseCategoryPath(url: string): { id: number; slug: string } {
  const path = new URL(url).pathname;
  const segment = path.replace(/^\/kategori\//, "");
  const dashIndex = segment.indexOf("-");
  if (dashIndex === -1) {
    throw new Error(`Unexpected category URL: ${url}`);
  }
  return {
    id: Number.parseInt(segment.slice(0, dashIndex), 10),
    slug: segment.slice(dashIndex + 1),
  };
}

function parsePriceTry(html: string): number {
  const raw = matchOne(html, /Hesap Birim Fiyat[ıi]\s*:\s*([\d.,]+)\s*[₺]/i);
  if (raw === null) {
    throw new Error("price not found");
  }

  // Handle both "1.234,56" (Turkish) and "1234.56" (English) formats.
  const hasComma = raw.includes(",");
  const hasDot = raw.includes(".");
  let normalized: string;
  if (hasComma && hasDot) {
    normalized = raw.replace(/\./g, "").replace(",", ".");
  } else if (hasComma) {
    normalized = raw.replace(",", ".");
  } else {
    normalized = raw;
  }

  const value = Number.parseFloat(normalized);
  if (!Number.isFinite(value) || value <= 0) {
    throw new Error(`invalid price: ${raw}`);
  }
  return value;
}

function parseStock(html: string): number {
  const raw = matchOne(html, /Kalan Stok\s*:\s*([\d.,]+)/i);
  if (raw === null) {
    return 0;
  }
  const value = Number.parseInt(raw.replace(/[.,]/g, ""), 10);
  return Number.isFinite(value) ? value : 0;
}

function parseTitle(html: string): string {
  const h1 = matchOne(
    html,
    /<h1[^>]*class="text-center text-lg-left"[^>]*>([\s\S]*?)<\/h1>/i,
  );
  const raw = h1 ?? matchOne(html, /<title>([\s\S]*?)<\/title>/i);
  if (raw === null) {
    throw new Error("title not found");
  }
  return decodeEntities(raw)
    .replace(/\|.*$/, "") // trim " | Facebook Hesap Satın Al..." suffix
    .replace(/\s+/g, " ")
    .trim();
}

function parseMetaTitle(html: string): string {
  const raw = matchOne(html, /<title>([\s\S]*?)<\/title>/i);
  if (raw === null) {
    throw new Error("meta title not found");
  }
  return decodeEntities(raw).replace(/\s+/g, " ").trim();
}

function parseMetaDescription(html: string): string | null {
  const raw = matchOne(
    html,
    /<meta[^>]+name=["']description["'][^>]+content=["']([^"']+)["']/i,
  );
  return raw === null ? null : decodeEntities(raw).replace(/\s+/g, " ").trim();
}

function parseMetaKeywords(html: string): string | null {
  const raw = matchOne(
    html,
    /<meta[^>]+name=["']keywords["'][^>]+content=["']([^"']+)["']/i,
  );
  return raw === null ? null : decodeEntities(raw).replace(/\s+/g, " ").trim();
}

function parseDescription(html: string): { html: string; text: string } {
  // The long description sits inside <div class="landing alert ..."> after
  // the "Açıklama" heading.
  const match = /<div class="landing alert[\s\S]*?">([\s\S]*?)<\/div>\s*<\/div>/i.exec(
    html,
  );
  const inner = match?.[1] ?? "";
  const trimmed = inner.trim();
  return {
    html: trimmed,
    text: stripTags(trimmed),
  };
}

async function fetchWithRetry(url: string): Promise<string> {
  let attempt = 0;
  let lastError: unknown;

  while (attempt < MAX_RETRIES) {
    attempt += 1;

    try {
      const response = await fetch(url, {
        headers: {
          "user-agent":
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) yildizhesap-migrator/1.0",
          accept: "text/html",
        },
      });

      if (response.status === 429 || response.status >= 500) {
        const backoff = 1500 * 2 ** (attempt - 1);
        console.warn(`retry ${attempt}/${MAX_RETRIES} in ${backoff}ms — ${url}`);
        await sleep(backoff);
        continue;
      }

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      return await response.text();
    } catch (error) {
      lastError = error;
      const backoff = 1500 * 2 ** (attempt - 1);
      console.warn(`retry ${attempt}/${MAX_RETRIES} in ${backoff}ms — ${url}`);
      await sleep(backoff);
    }
  }

  throw lastError ?? new Error(`giving up on ${url}`);
}

async function fetchCategory(url: string): Promise<ScrapedProduct> {
  const html = await fetchWithRetry(url);
  await sleep(REQUEST_DELAY_MS);
  const { id, slug } = parseCategoryPath(url);
  const description = parseDescription(html);

  return {
    sourceId: id,
    slug,
    title: parseTitle(html),
    metaTitle: parseMetaTitle(html),
    metaDescription: parseMetaDescription(html),
    metaKeywords: parseMetaKeywords(html),
    priceTry: parsePriceTry(html),
    stock: parseStock(html),
    descriptionHtml: description.html,
    descriptionText: description.text,
  };
}

async function runWithConcurrency<T>(
  items: readonly string[],
  worker: (url: string) => Promise<T>,
  limit: number,
): Promise<T[]> {
  const results: T[] = new Array(items.length);
  let cursor = 0;

  const runners = Array.from({ length: Math.min(limit, items.length) }, async () => {
    while (true) {
      const index = cursor;
      cursor += 1;
      if (index >= items.length) {
        return;
      }
      const url = items[index];
      try {
        const data = await worker(url);
        results[index] = data;
        console.log(`ok  ${url}`);
      } catch (error) {
        console.error(`fail ${url}:`, (error as Error).message);
        throw error;
      }
    }
  });

  await Promise.all(runners);
  return results;
}

async function main(): Promise<void> {
  const products = await runWithConcurrency(CATEGORY_URLS, fetchCategory, CONCURRENCY);

  await mkdir(path.dirname(OUTPUT), { recursive: true });
  await writeFile(
    OUTPUT,
    `${JSON.stringify(
      {
        source: BASE,
        scrapedAt: new Date().toISOString(),
        products,
      },
      null,
      2,
    )}\n`,
    "utf8",
  );

  console.log(`\nwrote ${products.length} products → ${OUTPUT}`);
}

main().catch((error: unknown) => {
  console.error(error);
  process.exitCode = 1;
});
