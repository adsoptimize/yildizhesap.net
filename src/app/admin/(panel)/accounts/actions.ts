"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { requireAdmin } from "@/lib/auth/admin";
import { revalidateStorefront } from "@/lib/admin/revalidate";
import { prisma } from "@/lib/prisma";
import { slugify } from "@/lib/seo/slugify";

export type AccountFormState = {
  error: string | null;
  success: string | null;
};

const ADMIN_ACCOUNTS_PATH = "/admin/accounts";
const DEFAULT_WARRANTY_DAYS = 30;

function readCheckbox(formData: FormData, key: string): boolean {
  return formData.get(key) === "on";
}

function readOptionalText(formData: FormData, key: string): string | null {
  const value = String(formData.get(key) ?? "").trim();
  return value === "" ? null : value;
}

function readDecimal(formData: FormData, key: string): string | null {
  const raw = String(formData.get(key) ?? "").trim().replace(",", ".");

  if (raw === "") {
    return null;
  }

  const parsed = Number(raw);
  return Number.isFinite(parsed) && parsed >= 0 ? parsed.toFixed(2) : null;
}

function readInt(formData: FormData, key: string, fallback: number): number {
  const parsed = Number.parseInt(String(formData.get(key) ?? ""), 10);
  return Number.isNaN(parsed) || parsed < 0 ? fallback : parsed;
}

export async function saveAccountAction(
  _previousState: AccountFormState,
  formData: FormData,
): Promise<AccountFormState> {
  await requireAdmin();

  const rawId = String(formData.get("id") ?? "").trim();
  const title = String(formData.get("title") ?? "").trim();
  const categoryId = Number.parseInt(String(formData.get("categoryId") ?? ""), 10);
  const price = readDecimal(formData, "price");
  const platform = String(formData.get("platform") ?? "").trim();
  const accountType = String(formData.get("accountType") ?? "").trim();

  if (title === "") {
    return { error: "Ürün başlığı zorunludur.", success: null };
  }

  if (Number.isNaN(categoryId)) {
    return { error: "Kategori seçmelisiniz.", success: null };
  }

  if (price === null) {
    return { error: "Geçerli bir fiyat girin.", success: null };
  }

  const seoSlugInput = String(formData.get("seoSlug") ?? "").trim();
  const seoSlug = seoSlugInput === "" ? slugify(title) : slugify(seoSlugInput);

  if (seoSlug === "") {
    return { error: "SEO slug üretilemedi, elle girin.", success: null };
  }

  const conflict = await prisma.account.findFirst({
    where: {
      seoSlug,
      ...(rawId === "" ? {} : { id: { not: Number(rawId) } }),
    },
    select: { id: true },
  });

  if (conflict !== null) {
    return {
      error: `"${seoSlug}" slug'ı başka bir ürüne ait (id: ${conflict.id}).`,
      success: null,
    };
  }

  const data = {
    categoryId,
    title,
    seoSlug,
    description: readOptionalText(formData, "description"),
    price,
    oldPrice: readDecimal(formData, "oldPrice"),
    stockQuantity: readInt(formData, "stockQuantity", 0),
    platform: platform === "" ? "Facebook" : platform,
    accountType: accountType === "" ? "Hesap" : accountType,
    limitInfo: readOptionalText(formData, "limitInfo"),
    technicalInfo: readOptionalText(formData, "technicalInfo"),
    location: readOptionalText(formData, "location"),
    status: readCheckbox(formData, "isPublished")
      ? ("active" as const)
      : ("inactive" as const),
    isVerified: readCheckbox(formData, "isVerified"),
    isPremium: readCheckbox(formData, "isPremium"),
    isFeatured: readCheckbox(formData, "isFeatured"),
    isSecure: readCheckbox(formData, "isSecure"),
    instantDelivery: readCheckbox(formData, "instantDelivery"),
    support247: readCheckbox(formData, "support247"),
    guarantee30Days: readCheckbox(formData, "guarantee30Days"),
    warrantyDays: readInt(formData, "warrantyDays", DEFAULT_WARRANTY_DAYS),
    deliveryType: readCheckbox(formData, "instantDelivery")
      ? ("instant" as const)
      : ("manual" as const),
  };

  if (rawId === "") {
    const created = await prisma.account.create({
      data,
      select: { id: true },
    });
    revalidateStorefront([seoSlug]);
    revalidatePath(ADMIN_ACCOUNTS_PATH);
    redirect(`${ADMIN_ACCOUNTS_PATH}/${created.id}`);
  }

  const id = Number(rawId);
  const existing = await prisma.account.findUnique({
    where: { id },
    select: { seoSlug: true, category: { select: { seoSlug: true } } },
  });

  await prisma.account.update({ where: { id }, data });

  revalidateStorefront([
    seoSlug,
    existing?.seoSlug ?? null,
    existing?.category.seoSlug ?? null,
  ]);
  revalidatePath(`${ADMIN_ACCOUNTS_PATH}/${id}`);

  return { error: null, success: "Ürün kaydedildi." };
}

export async function deleteAccountAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const id = Number(formData.get("id"));
  const account = await prisma.account.findUnique({
    where: { id },
    select: { seoSlug: true, category: { select: { seoSlug: true } } },
  });

  if (account === null) {
    return;
  }

  await prisma.account.delete({ where: { id } });

  revalidateStorefront([account.seoSlug, account.category.seoSlug]);
  revalidatePath(ADMIN_ACCOUNTS_PATH);
  redirect(ADMIN_ACCOUNTS_PATH);
}

export async function toggleAccountStatusAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const id = Number(formData.get("id"));
  const account = await prisma.account.findUnique({
    where: { id },
    select: { status: true, seoSlug: true, category: { select: { seoSlug: true } } },
  });

  if (account === null) {
    return;
  }

  await prisma.account.update({
    where: { id },
    data: { status: account.status === "active" ? "inactive" : "active" },
  });

  revalidateStorefront([account.seoSlug, account.category.seoSlug]);
  revalidatePath(ADMIN_ACCOUNTS_PATH);
}

export async function addFeatureAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const accountId = Number(formData.get("accountId"));
  const featureName = String(formData.get("featureName") ?? "").trim();
  const featureValue = String(formData.get("featureValue") ?? "").trim();

  if (featureName === "" || featureValue === "") {
    return;
  }

  await prisma.accountFeature.create({
    data: { accountId, featureName, featureValue },
  });

  const account = await prisma.account.findUnique({
    where: { id: accountId },
    select: { seoSlug: true },
  });

  revalidateStorefront([account?.seoSlug ?? null]);
  revalidatePath(`${ADMIN_ACCOUNTS_PATH}/${accountId}`);
}

export async function deleteFeatureAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const id = Number(formData.get("id"));
  const feature = await prisma.accountFeature.findUnique({
    where: { id },
    select: { accountId: true, account: { select: { seoSlug: true } } },
  });

  if (feature === null) {
    return;
  }

  await prisma.accountFeature.delete({ where: { id } });

  revalidateStorefront([feature.account.seoSlug]);
  revalidatePath(`${ADMIN_ACCOUNTS_PATH}/${feature.accountId}`);
}

/** Adds credential rows and keeps stock_quantity aligned with unsold rows. */
export async function addStockAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const accountId = Number(formData.get("accountId"));
  const username = String(formData.get("username") ?? "").trim();
  const password = String(formData.get("password") ?? "").trim();

  if (username === "" || password === "") {
    return;
  }

  const createdDate = readOptionalText(formData, "accountCreatedDate");

  await prisma.accountStock.create({
    data: {
      accountId,
      username,
      password,
      email: readOptionalText(formData, "email"),
      emailPassword: readOptionalText(formData, "emailPassword"),
      totpSecret: readOptionalText(formData, "totpSecret"),
      accountCreatedDate: createdDate === null ? null : new Date(createdDate),
      additionalInfo: readOptionalText(formData, "additionalInfo"),
    },
  });

  await syncStockQuantity(accountId);
  revalidatePath(`${ADMIN_ACCOUNTS_PATH}/${accountId}`);
}

/**
 * Bulk import stock rows from a textarea. Each non-empty line represents one
 * credential set. Supported line formats:
 *   username:password
 *   username:password:email
 *   username:password:email:emailPassword
 *   username:password:email:emailPassword:totpSecret
 * Delimiters accepted: `:`, `|`, `;`, `\t`.
 * Duplicate credentials (same username + password) already in this account's
 * stock table are silently skipped to prevent double-insertion.
 */
export async function addBulkStockAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const accountId = Number(formData.get("accountId"));
  if (Number.isNaN(accountId)) return;

  const raw = String(formData.get("bulkData") ?? "");
  if (raw.trim() === "") return;

  const lines = raw
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter((line) => line.length > 0);

  // Fetch existing username+password pairs once to avoid N queries.
  const existing = await prisma.accountStock.findMany({
    where: { accountId },
    select: { username: true, password: true },
  });
  const existingKeys = new Set(
    existing.map((row) => `${row.username}::${row.password}`),
  );

  const rowsToInsert: Array<{
    accountId: number;
    username: string;
    password: string;
    email: string | null;
    emailPassword: string | null;
    totpSecret: string | null;
  }> = [];

  for (const line of lines) {
    // Accept any of `:`, `|`, `;`, tab.
    const parts = line.split(/[:|;\t]/).map((part) => part.trim());
    const [username, password, email, emailPassword, totpSecret] = parts;

    if (
      username === undefined ||
      password === undefined ||
      username === "" ||
      password === ""
    ) {
      continue;
    }

    const key = `${username}::${password}`;
    if (existingKeys.has(key)) continue;
    existingKeys.add(key);

    rowsToInsert.push({
      accountId,
      username,
      password,
      email: email === undefined || email === "" ? null : email,
      emailPassword:
        emailPassword === undefined || emailPassword === "" ? null : emailPassword,
      totpSecret:
        totpSecret === undefined || totpSecret === "" ? null : totpSecret,
    });
  }

  if (rowsToInsert.length === 0) {
    return;
  }

  await prisma.accountStock.createMany({ data: rowsToInsert });
  await syncStockQuantity(accountId);
  revalidatePath(`${ADMIN_ACCOUNTS_PATH}/${accountId}`);
}

/**
 * Removes stock rows that are marked sold=false but have empty username or
 * password (data hygiene action mirroring legacy `clean_empty_stock`).
 */
export async function cleanEmptyStockAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const accountId = Number(formData.get("accountId"));
  if (Number.isNaN(accountId)) return;

  await prisma.accountStock.deleteMany({
    where: {
      accountId,
      isSold: false,
      OR: [{ username: "" }, { password: "" }],
    },
  });

  await syncStockQuantity(accountId);
  revalidatePath(`${ADMIN_ACCOUNTS_PATH}/${accountId}`);
}

export async function deleteStockAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const id = Number(formData.get("id"));
  const stock = await prisma.accountStock.findUnique({
    where: { id },
    select: { accountId: true, isSold: true },
  });

  if (stock === null || stock.isSold) {
    return;
  }

  await prisma.accountStock.delete({ where: { id } });

  await syncStockQuantity(stock.accountId);
  revalidatePath(`${ADMIN_ACCOUNTS_PATH}/${stock.accountId}`);
}

async function syncStockQuantity(accountId: number): Promise<void> {
  const available = await prisma.accountStock.count({
    where: { accountId, isSold: false },
  });

  const account = await prisma.account.update({
    where: { id: accountId },
    data: { stockQuantity: available },
    select: { seoSlug: true, category: { select: { seoSlug: true } } },
  });

  revalidateStorefront([account.seoSlug, account.category.seoSlug]);
}
