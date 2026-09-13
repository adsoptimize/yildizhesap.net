/**
 * Seeds the catalog skeleton and editorial content.
 *
 * The live MySQL database was lost, so product rows are intentionally NOT
 * seeded: they are entered through the admin panel. Categories keep their
 * legacy ids and seo_slugs because those ids are baked into indexed URLs.
 */

import { readFileSync } from "node:fs";
import path from "node:path";
import { PrismaClient } from "@prisma/client";
import { readDumpTable, repairMojibake, toBoolean, toInt } from "./legacy-dump";
import { CATEGORY_SEO_SLUGS } from "../src/lib/seo/slugs";
import { CATEGORY_META } from "../src/lib/seo/meta";
import {
  CATEGORY_ICONS,
  CATEGORY_NAMES,
  SITE_SETTING_SEEDS,
} from "../src/lib/catalog/defaults";

const prisma = new PrismaClient();
const LEGACY_DUMP_PATH = path.join(process.cwd(), "legacy", "businesshesap.sql");

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

    await prisma.legalPage.upsert({
      where: { pageType: row.page_type },
      update: data,
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

  await realignSequences();

  console.log(
    [
      `categories: ${categories}`,
      `faqs: ${faqs}`,
      `legal pages: ${legalPages}`,
      `contact settings: ${contact}`,
      `site settings: ${settings}`,
    ].join(", "),
  );
}

main()
  .catch((error: unknown) => {
    console.error(error);
    process.exitCode = 1;
  })
  .finally(() => prisma.$disconnect());
