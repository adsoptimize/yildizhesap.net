/**
 * Recreates the indexed product URLs as unpublished drafts.
 *
 * Only the SEO-critical fields are known (legacy id, seo_slug, meta title);
 * price and copy were lost with the database, so rows are created with price 0
 * and status "inactive". They stay out of the sitemap and return 404 until an
 * admin fills in the details and publishes them.
 *
 * The category mapping is inferred from the slug/title wording and can be
 * changed from the admin panel with one click.
 */

import { PrismaClient } from "@prisma/client";
import { PRODUCT_SEO_SLUGS } from "../src/lib/seo/slugs";
import { PRODUCT_META_TITLES } from "../src/lib/seo/meta";
import { CATEGORY_NAMES } from "../src/lib/catalog/defaults";

const prisma = new PrismaClient();

/** legacy accountId → categoryId */
const PRODUCT_CATEGORY_IDS: Readonly<Record<number, number>> = {
  12: 7,
  13: 7,
  14: 7,
  15: 7,
  16: 7,
  17: 7,
  18: 8,
  19: 8,
  20: 8,
  21: 8,
  26: 12,
  27: 12,
  28: 12,
  29: 12,
  30: 12,
  31: 12,
  35: 13,
  36: 13,
  37: 13,
  38: 13,
  39: 13,
  40: 13,
  41: 14,
  42: 15,
  43: 15,
  44: 15,
  45: 15,
  46: 16,
  47: 16,
  49: 16,
  50: 16,
  60: 27,
};

const PLATFORM_BY_CATEGORY_ID: Readonly<Record<number, string>> = {
  7: "Facebook",
  8: "Facebook",
  12: "Facebook",
  13: "Facebook",
  14: "Facebook",
  15: "Facebook",
  16: "Instagram",
  20: "X (Twitter)",
  21: "TikTok",
  22: "Mail",
  27: "Telegram",
};

const DRAFT_PRICE = "0";

async function main(): Promise<void> {
  let created = 0;
  let skipped = 0;

  for (const [rawId, seoSlug] of Object.entries(PRODUCT_SEO_SLUGS)) {
    const id = Number(rawId);
    const categoryId = PRODUCT_CATEGORY_IDS[id];
    const title = PRODUCT_META_TITLES[id];

    if (categoryId === undefined || title === undefined) {
      console.warn(`atlandı: ${seoSlug} (kategori veya başlık eşleşmedi)`);
      skipped += 1;
      continue;
    }

    const existing = await prisma.account.findUnique({
      where: { id },
      select: { id: true },
    });

    if (existing !== null) {
      skipped += 1;
      continue;
    }

    await prisma.account.create({
      data: {
        id,
        categoryId,
        title,
        seoSlug,
        price: DRAFT_PRICE,
        stockQuantity: 0,
        platform: PLATFORM_BY_CATEGORY_ID[categoryId] ?? "Facebook",
        accountType: CATEGORY_NAMES[categoryId] ?? "Hesap",
        status: "inactive",
      },
    });

    created += 1;
  }

  await prisma.$executeRawUnsafe(
    `SELECT setval(pg_get_serial_sequence('"accounts"', 'id'),
      GREATEST(COALESCE((SELECT MAX(id) FROM "accounts"), 0), 1))`,
  );

  console.log(`taslak ürün → oluşturulan: ${created}, atlanan: ${skipped}`);
}

main()
  .catch((error: unknown) => {
    console.error(error);
    process.exitCode = 1;
  })
  .finally(() => prisma.$disconnect());
