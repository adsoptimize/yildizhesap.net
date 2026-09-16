/**
 * Seeds the catalog skeleton and editorial content.
 *
 * Two product sets are loaded:
 *   1. 32 legacy yildizhesap.net slugs (from view.php) — locked for SEO.
 *   2. 39 businesshesap.com catalog snapshots — full title + price + copy.
 *
 * Categories keep their legacy ids and seo_slugs because those ids are baked
 * into indexed URLs.
 */

import { readFileSync } from "node:fs";
import path from "node:path";
import { PrismaClient } from "@prisma/client";
import { readDumpTable, repairMojibake, toBoolean, toInt } from "./legacy-dump";
import {
  BUSINESSHESAP_CATEGORY_MAP,
  BUSINESSHESAP_ID_OFFSET,
  LEGACY_PRODUCTS,
} from "./legacy-products";
import { CATEGORY_SEO_SLUGS, PRODUCT_SEO_SLUGS } from "../src/lib/seo/slugs";
import {
  CATEGORY_META,
  PRODUCT_META_DESCRIPTIONS,
  PRODUCT_META_TITLES,
} from "../src/lib/seo/meta";
import {
  CATEGORY_ICONS,
  CATEGORY_NAMES,
  SITE_SETTING_SEEDS,
} from "../src/lib/catalog/defaults";

const prisma = new PrismaClient();
const LEGACY_DUMP_PATH = path.join(process.cwd(), "legacy", "businesshesap.sql");
const BUSINESSHESAP_CATALOG_PATH = path.join(
  process.cwd(),
  "prisma",
  "businesshesap-catalog.json",
);
const DEFAULT_STOCK = 10;

type BusinesshesapProduct = {
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

type BusinesshesapCatalog = {
  source: string;
  scrapedAt: string;
  products: BusinesshesapProduct[];
};

function readLegacyDump(): string {
  try {
    return readFileSync(LEGACY_DUMP_PATH, "utf8");
  } catch {
    console.warn("legacy dump not found, skipping FAQ/legal/contact seed");
    return "";
  }
}

async function seedCategories(): Promise<number> {
  const entries = Object.entries(CATEGORY_SEO_SLUGS);

  for (const [rawId, seoSlug] of entries) {
    const id = Number(rawId);
    const data = {
      name: CATEGORY_NAMES[id],
      slug: seoSlug,
      seoSlug,
      icon: CATEGORY_ICONS[id],
      description: CATEGORY_META[id]?.description ?? null,
    };

    await prisma.category.upsert({
      where: { id },
      update: data,
      create: { id, ...data, isActive: true },
    });
  }

  return entries.length;
}

async function seedFaqs(dump: string): Promise<number> {
  const rows = readDumpTable(dump, "faqs");

  for (const row of rows) {
    const id = toInt(row.id, 0);
    if (id === 0 || row.question === null || row.answer === null) {
      continue;
    }

    const data = {
      question: row.question,
      answer: row.answer,
      orderIndex: toInt(row.order_index, 0),
      isActive: toBoolean(row.is_active, true),
    };

    await prisma.faq.upsert({
      where: { id },
      update: data,
      create: { id, ...data },
    });
  }

  return rows.length;
}

async function seedLegalPages(dump: string): Promise<number> {
  const rows = readDumpTable(dump, "legal_pages");

  for (const row of rows) {
    if (row.page_type === null || row.title === null || row.content === null) {
      continue;
    }

    const data = {
      title: repairMojibake(row.title),
      content: repairMojibake(row.content),
      metaDescription:
        row.meta_description === null
          ? null
          : repairMojibake(row.meta_description),
      isActive: toBoolean(row.is_active, true),
    };

    // Create only. The dump ships unfilled templates ("[Firma Adı]",
    // "[destek@siteadresi.com]"), and the live pages have since been rewritten
    // with real company details by scripts/update-legal-pages.ts. Updating
    // here would silently restore the placeholders on the next seed run.
    await prisma.legalPage.upsert({
      where: { pageType: row.page_type },
      update: {},
      create: { pageType: row.page_type, ...data },
    });
  }

  return rows.length;
}

async function seedContactSettings(dump: string): Promise<number> {
  const rows = readDumpTable(dump, "contact_settings");

  for (const row of rows) {
    if (row.setting_key === null || row.setting_value === null) {
      continue;
    }

    await prisma.contactSetting.upsert({
      where: { settingKey: row.setting_key },
      update: {},
      create: {
        settingKey: row.setting_key,
        settingValue: row.setting_value,
      },
    });
  }

  return rows.length;
}

function readBusinesshesapCatalog(): BusinesshesapCatalog | null {
  try {
    const raw = readFileSync(BUSINESSHESAP_CATALOG_PATH, "utf8");
    return JSON.parse(raw) as BusinesshesapCatalog;
  } catch {
    console.warn(
      "businesshesap-catalog.json not found, skipping businesshesap seed",
    );
    return null;
  }
}

async function seedLegacyProducts(): Promise<number> {
  for (const product of LEGACY_PRODUCTS) {
    const seoSlug = PRODUCT_SEO_SLUGS[product.id];
    if (seoSlug === undefined) {
      console.warn(`legacy product ${product.id} has no SEO slug, skipping`);
      continue;
    }

    const description = PRODUCT_META_DESCRIPTIONS[product.id] ?? null;
    const data = {
      categoryId: product.categoryId,
      title: PRODUCT_META_TITLES[product.id] ?? product.title,
      seoSlug,
      description,
      price: product.price,
      stockQuantity: DEFAULT_STOCK,
      platform: product.platform,
      accountType: product.accountType,
      status: "active" as const,
      isVerified: true,
      guarantee30Days: true,
      warrantyDays: 30,
      deliveryType: "instant" as const,
    };
    // `description` intentionally omitted from `update` so re-running the seed
    // does NOT overwrite the long-form SEO copy generated by
    // `scripts/enrich-thin-descriptions.ts`. It is still written on `create`
    // for brand-new environments.
    const { description: _seedDescription, ...updateData } = data;
    void _seedDescription;

    await prisma.account.upsert({
      where: { id: product.id },
      update: updateData,
      create: { id: product.id, ...data },
    });
  }

  return LEGACY_PRODUCTS.length;
}

async function seedBusinesshesapProducts(
  catalog: BusinesshesapCatalog,
): Promise<number> {
  let seeded = 0;

  for (const product of catalog.products) {
    const id = product.sourceId + BUSINESSHESAP_ID_OFFSET;
    const seoSlug = PRODUCT_SEO_SLUGS[id];
    if (seoSlug === undefined) {
      // 1015/1016 collide with legacy 46/47 and are intentionally skipped.
      continue;
    }

    const categoryId = BUSINESSHESAP_CATEGORY_MAP[product.sourceId];
    if (categoryId === undefined) {
      console.warn(
        `no category mapping for businesshesap #${product.sourceId}, skipping`,
      );
      continue;
    }

    const data = {
      categoryId,
      title: product.title,
      seoSlug,
      description: product.descriptionHtml || product.descriptionText || null,
      price: product.priceTry,
      stockQuantity: DEFAULT_STOCK,
      platform: inferPlatform(product.title),
      accountType: inferAccountType(product.title),
      status: "active" as const,
      isVerified: true,
      guarantee30Days: true,
      warrantyDays: 30,
      deliveryType: "instant" as const,
    };
    // businesshesap.com content is duplicate on Google (source site still
    // live). We intentionally do NOT restore it on update; the enrichment
    // script owns the description field for existing rows.
    const { description: _seedDescription, ...updateData } = data;
    void _seedDescription;

    await prisma.account.upsert({
      where: { id },
      update: updateData,
      create: { id, ...data },
    });
    seeded += 1;
  }

  return seeded;
}

function inferPlatform(title: string): string {
  const lower = title.toLowerCase();
  if (lower.includes("instagram") || lower.includes("i̇nstagram")) return "Instagram";
  if (lower.includes("tiktok")) return "TikTok";
  if (lower.includes("telegram")) return "Telegram";
  if (lower.includes("gmail") || lower.includes("outlook") || lower.includes("hotmail")) return "Mail";
  if (lower.includes("discord")) return "Discord";
  if (lower.includes(" x ") || lower.includes("twitter")) return "X";
  return "Facebook";
}

function inferAccountType(title: string): string {
  const lower = title.toLowerCase();
  if (lower.includes("business manager") || lower.includes("bm")) return "Business Manager";
  if (lower.includes("marketplace")) return "Marketplace";
  if (lower.includes("reklam") || lower.includes("ads")) return "Reklam Hesabı";
  if (lower.includes("kimlik")) return "Kimlik Onaylı";
  return "Standart";
}

async function seedSiteSettings(): Promise<number> {
  for (const setting of SITE_SETTING_SEEDS) {
    await prisma.siteSetting.upsert({
      where: { settingKey: setting.settingKey },
      update: {},
      create: setting,
    });
  }

  return SITE_SETTING_SEEDS.length;
}

/** Explicit ids bypass the identity sequence, so it has to be realigned. */
async function realignSequences(): Promise<void> {
  const tables = [
    "categories",
    "faqs",
    "legal_pages",
    "site_settings",
    "contact_settings",
    "accounts",
  ];

  for (const table of tables) {
    await prisma.$executeRawUnsafe(
      `SELECT setval(pg_get_serial_sequence('"${table}"', 'id'),
        GREATEST(COALESCE((SELECT MAX(id) FROM "${table}"), 0), 1))`,
    );
  }
}

async function main(): Promise<void> {
  const dump = readLegacyDump();
  const hasDump = dump !== "";

  const categories = await seedCategories();
  const faqs = hasDump ? await seedFaqs(dump) : 0;
  const legalPages = hasDump ? await seedLegalPages(dump) : 0;
  const contact = hasDump ? await seedContactSettings(dump) : 0;
  const settings = await seedSiteSettings();
  const legacyProducts = await seedLegacyProducts();
  const bhCatalog = readBusinesshesapCatalog();
  const bhProducts =
    bhCatalog === null ? 0 : await seedBusinesshesapProducts(bhCatalog);

  await realignSequences();

  console.log(
    [
      `categories: ${categories}`,
      `faqs: ${faqs}`,
      `legal pages: ${legalPages}`,
      `contact settings: ${contact}`,
      `site settings: ${settings}`,
      `legacy products: ${legacyProducts}`,
      `businesshesap products: ${bhProducts}`,
    ].join(", "),
  );
}

main()
  .catch((error: unknown) => {
    console.error(error);
    process.exitCode = 1;
  })
  .finally(() => prisma.$disconnect());
