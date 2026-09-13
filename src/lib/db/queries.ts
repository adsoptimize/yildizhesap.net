/**
 * Read-side queries for the storefront. Pages are statically prerendered and
 * refreshed on the ISR window below, plus on-demand from admin mutations.
 */

import { prisma } from "@/lib/prisma";

const CATEGORY_CARD_SELECT = {
  id: true,
  name: true,
  seoSlug: true,
  slug: true,
  icon: true,
  description: true,
} as const;

export type CategoryCard = {
  id: number;
  name: string;
  seoSlug: string;
  icon: string;
  description: string | null;
};

export type ProductCard = {
  id: number;
  categoryId: number;
  title: string;
  seoSlug: string | null;
  price: string;
  oldPrice: string | null;
  stockQuantity: number;
  platform: string;
  isVerified: boolean;
  isPremium: boolean;
};

export type ProductDetail = ProductCard & {
  /** Unsold `account_stock` rows — the number that can be delivered instantly. */
  availableStock: number;
  description: string | null;
  technicalInfo: string | null;
  accountType: string;
  limitInfo: string | null;
  warrantyDays: number;
  rating: number | null;
  instantDelivery: boolean;
  support247: boolean;
  guarantee30Days: boolean;
  categorySeoSlug: string | null;
  categoryName: string;
  features: { featureName: string; featureValue: string }[];
};

function toCategoryCard(row: {
  id: number;
  name: string;
  seoSlug: string | null;
  slug: string;
  icon: string;
  description: string | null;
}): CategoryCard {
  return {
    id: row.id,
    name: row.name,
    seoSlug: row.seoSlug ?? row.slug,
    icon: row.icon,
    description: row.description,
  };
}

export async function getStorefrontCategories(): Promise<CategoryCard[]> {
  const rows = await prisma.category.findMany({
    where: { isActive: true },
    select: CATEGORY_CARD_SELECT,
    orderBy: { id: "asc" },
  });

  return rows.map(toCategoryCard);
}

export async function getCategoryBySeoSlug(
  seoSlug: string,
): Promise<CategoryCard | null> {
  const row = await prisma.category.findFirst({
    where: { isActive: true, OR: [{ seoSlug }, { slug: seoSlug }] },
    select: CATEGORY_CARD_SELECT,
  });

  return row === null ? null : toCategoryCard(row);
}

const PRODUCT_LIST_SELECT = {
  id: true,
  categoryId: true,
  title: true,
  seoSlug: true,
  price: true,
  oldPrice: true,
  stockQuantity: true,
  platform: true,
  isVerified: true,
  isPremium: true,
} as const;

function toProductCard(row: {
  id: number;
  categoryId: number;
  title: string;
  seoSlug: string | null;
  price: { toString(): string };
  oldPrice: { toString(): string } | null;
  stockQuantity: number;
  platform: string;
  isVerified: boolean;
  isPremium: boolean;
}): ProductCard {
  return {
    id: row.id,
    categoryId: row.categoryId,
    title: row.title,
    seoSlug: row.seoSlug,
    price: row.price.toString(),
    oldPrice: row.oldPrice === null ? null : row.oldPrice.toString(),
    stockQuantity: row.stockQuantity,
    platform: row.platform,
    isVerified: row.isVerified,
    isPremium: row.isPremium,
  };
}

export async function getActiveProducts(): Promise<ProductCard[]> {
  const rows = await prisma.account.findMany({
    where: { status: "active" },
    select: PRODUCT_LIST_SELECT,
    orderBy: [{ isFeatured: "desc" }, { id: "asc" }],
  });

  return rows.map(toProductCard);
}

export async function getProductsByCategoryId(
  categoryId: number,
): Promise<ProductCard[]> {
  const rows = await prisma.account.findMany({
    where: { status: "active", categoryId },
    select: PRODUCT_LIST_SELECT,
    orderBy: [{ isFeatured: "desc" }, { id: "asc" }],
  });

  return rows.map(toProductCard);
}

export async function getProductBySeoSlug(
  seoSlug: string,
): Promise<ProductDetail | null> {
  const row = await prisma.account.findFirst({
    where: { status: "active", seoSlug },
    select: {
      ...PRODUCT_LIST_SELECT,
      description: true,
      technicalInfo: true,
      accountType: true,
      limitInfo: true,
      warrantyDays: true,
      rating: true,
      instantDelivery: true,
      support247: true,
      guarantee30Days: true,
      category: { select: { name: true, seoSlug: true, slug: true } },
      featureRows: {
        select: { featureName: true, featureValue: true },
        orderBy: { id: "asc" },
      },
      _count: { select: { stock: { where: { isSold: false } } } },
    },
  });

  if (row === null) {
    return null;
  }

  return {
    ...toProductCard(row),
    availableStock: row._count.stock,
    description: row.description,
    technicalInfo: row.technicalInfo,
    accountType: row.accountType,
    limitInfo: row.limitInfo,
    warrantyDays: row.warrantyDays,
    rating: row.rating === null ? null : Number(row.rating),
    instantDelivery: row.instantDelivery,
    support247: row.support247,
    guarantee30Days: row.guarantee30Days,
    categoryName: row.category.name,
    categorySeoSlug: row.category.seoSlug ?? row.category.slug,
    features: row.featureRows,
  };
}

export async function getPrerenderableSlugs(): Promise<string[]> {
  const [categories, products] = await Promise.all([
    prisma.category.findMany({
      where: { isActive: true, seoSlug: { not: null } },
      select: { seoSlug: true },
    }),
    prisma.account.findMany({
      where: { status: "active", seoSlug: { not: null } },
      select: { seoSlug: true },
    }),
  ]);

  return [...categories, ...products]
    .map((row) => row.seoSlug)
    .filter((slug): slug is string => slug !== null);
}

export type FaqItem = {
  id: number;
  question: string;
  answer: string;
};

export async function getActiveFaqs(limit?: number): Promise<FaqItem[]> {
  return prisma.faq.findMany({
    where: { isActive: true },
    select: { id: true, question: true, answer: true },
    orderBy: [{ orderIndex: "asc" }, { id: "asc" }],
    take: limit,
  });
}

export type LegalPageContent = {
  title: string;
  content: string;
  metaDescription: string | null;
};

export async function getLegalPage(
  pageType: string,
): Promise<LegalPageContent | null> {
  return prisma.legalPage.findFirst({
    where: { pageType, isActive: true },
    select: { title: true, content: true, metaDescription: true },
  });
}

export type SettingsMap = Readonly<Record<string, string>>;

function toSettingsMap(
  rows: { settingKey: string; settingValue: string | null }[],
): SettingsMap {
  const map: Record<string, string> = {};

  for (const row of rows) {
    if (row.settingValue !== null) {
      map[row.settingKey] = row.settingValue;
    }
  }

  return map;
}

export async function getSiteSettings(): Promise<SettingsMap> {
  const rows = await prisma.siteSetting.findMany({
    select: { settingKey: true, settingValue: true },
  });

  return toSettingsMap(rows);
}

export async function getContactSettings(): Promise<SettingsMap> {
  const rows = await prisma.contactSetting.findMany({
    select: { settingKey: true, settingValue: true },
  });

  return toSettingsMap(rows);
}
