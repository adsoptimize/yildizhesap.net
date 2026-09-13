/**
 * Cart state lives in an httpOnly cookie: the legacy app kept it in the PHP
 * session, and a cookie is the equivalent that needs no extra infrastructure on
 * Vercel. Only ids and quantities are stored — prices always come from the
 * database, so a tampered cookie cannot change what a customer pays.
 */

import { cookies } from "next/headers";
import { prisma } from "@/lib/prisma";
import {
  CART_COOKIE,
  CART_COOKIE_MAX_AGE_SECONDS,
  MAX_CART_LINES,
  MAX_ITEM_QUANTITY,
  MIN_ITEM_QUANTITY,
} from "./constants";

export type CartLine = {
  accountId: number;
  quantity: number;
};

export type CartItem = CartLine & {
  title: string;
  seoSlug: string | null;
  categoryName: string;
  platform: string;
  unitPrice: string;
  availableStock: number;
  lineTotal: string;
};

export type Cart = {
  items: CartItem[];
  totalQuantity: number;
  totalPrice: string;
  /** Lines dropped because the product went inactive or out of stock. */
  removedTitles: string[];
};

const EMPTY_CART: Cart = {
  items: [],
  totalQuantity: 0,
  totalPrice: "0.00",
  removedTitles: [],
};

function clampQuantity(value: number, available: number): number {
  const upperBound = Math.min(MAX_ITEM_QUANTITY, available);

  if (upperBound < MIN_ITEM_QUANTITY) {
    return 0;
  }

  return Math.min(Math.max(Math.trunc(value), MIN_ITEM_QUANTITY), upperBound);
}

function parseCartCookie(raw: string | undefined): CartLine[] {
  if (raw === undefined || raw === "") {
    return [];
  }

  let parsed: unknown;

  try {
    parsed = JSON.parse(raw);
  } catch {
    return [];
  }

  if (!Array.isArray(parsed)) {
    return [];
  }

  const lines: CartLine[] = [];

  for (const entry of parsed) {
    if (typeof entry !== "object" || entry === null) {
      continue;
    }

    const candidate = entry as Record<string, unknown>;
    const accountId = Number(candidate.accountId);
    const quantity = Number(candidate.quantity);

    if (!Number.isInteger(accountId) || accountId <= 0) {
      continue;
    }

    if (!Number.isInteger(quantity) || quantity < MIN_ITEM_QUANTITY) {
      continue;
    }

    lines.push({
      accountId,
      quantity: Math.min(quantity, MAX_ITEM_QUANTITY),
    });
  }

  return lines.slice(0, MAX_CART_LINES);
}

export async function readCartLines(): Promise<CartLine[]> {
  const cookieStore = await cookies();
  return parseCartCookie(cookieStore.get(CART_COOKIE)?.value);
}

export async function writeCartLines(lines: CartLine[]): Promise<void> {
  const cookieStore = await cookies();

  if (lines.length === 0) {
    cookieStore.delete(CART_COOKIE);
    return;
  }

  cookieStore.set(CART_COOKIE, JSON.stringify(lines), {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge: CART_COOKIE_MAX_AGE_SECONDS,
  });
}

export async function clearCart(): Promise<void> {
  await writeCartLines([]);
}

/**
 * Resolves cookie lines against live catalog data. Quantities above the
 * available stock are trimmed and sold-out products are dropped, mirroring the
 * legacy checkout which silently skipped unavailable rows.
 */
export async function getCart(): Promise<Cart> {
  const lines = await readCartLines();

  if (lines.length === 0) {
    return EMPTY_CART;
  }

  const rows = await prisma.account.findMany({
    where: { id: { in: lines.map((line) => line.accountId) }, status: "active" },
    select: {
      id: true,
      title: true,
      seoSlug: true,
      platform: true,
      price: true,
      category: { select: { name: true } },
      _count: { select: { stock: { where: { isSold: false } } } },
    },
  });

  const byId = new Map(rows.map((row) => [row.id, row]));
  const items: CartItem[] = [];
  const removedTitles: string[] = [];
  const keptLines: CartLine[] = [];
  let totalQuantity = 0;
  let totalPrice = 0;

  for (const line of lines) {
    const row = byId.get(line.accountId);

    if (row === undefined) {
      continue;
    }

    const availableStock = row._count.stock;
    const quantity = clampQuantity(line.quantity, availableStock);

    if (quantity === 0) {
      removedTitles.push(row.title);
      continue;
    }

    const unitPrice = Number(row.price);
    const lineTotal = unitPrice * quantity;

    items.push({
      accountId: row.id,
      quantity,
      title: row.title,
      seoSlug: row.seoSlug,
      categoryName: row.category.name,
      platform: row.platform,
      unitPrice: unitPrice.toFixed(2),
      availableStock,
      lineTotal: lineTotal.toFixed(2),
    });

    keptLines.push({ accountId: row.id, quantity });
    totalQuantity += quantity;
    totalPrice += lineTotal;
  }

  if (
    keptLines.length !== lines.length ||
    keptLines.some(
      (line, index) => line.quantity !== lines[index]?.quantity,
    )
  ) {
    await writeCartLines(keptLines);
  }

  return {
    items,
    totalQuantity,
    totalPrice: totalPrice.toFixed(2),
    removedTitles,
  };
}

export async function getCartQuantity(): Promise<number> {
  const lines = await readCartLines();
  return lines.reduce((total, line) => total + line.quantity, 0);
}

export type CartMutationResult = {
  error: string | null;
  success: string | null;
};

export async function addToCart(
  accountId: number,
  quantity: number,
): Promise<CartMutationResult> {
  const account = await prisma.account.findFirst({
    where: { id: accountId, status: "active" },
    select: {
      title: true,
      _count: { select: { stock: { where: { isSold: false } } } },
    },
  });

  if (account === null) {
    return { error: "Ürün bulunamadı.", success: null };
  }

  const availableStock = account._count.stock;

  if (availableStock < MIN_ITEM_QUANTITY) {
    return { error: "Bu ürün şu anda stokta yok.", success: null };
  }

  const lines = await readCartLines();
  const existing = lines.find((line) => line.accountId === accountId);

  if (existing === undefined && lines.length >= MAX_CART_LINES) {
    return {
      error: `Sepete en fazla ${MAX_CART_LINES} farklı ürün ekleyebilirsiniz.`,
      success: null,
    };
  }

  const requested = (existing?.quantity ?? 0) + quantity;
  const finalQuantity = clampQuantity(requested, availableStock);

  if (existing === undefined) {
    lines.push({ accountId, quantity: finalQuantity });
  } else {
    existing.quantity = finalQuantity;
  }

  await writeCartLines(lines);

  const trimmed = finalQuantity < requested;

  return {
    error: null,
    success: trimmed
      ? `${account.title} sepete eklendi. Stok sınırı nedeniyle adet ${finalQuantity} olarak ayarlandı.`
      : `${account.title} sepete eklendi.`,
  };
}

export async function setCartQuantity(
  accountId: number,
  quantity: number,
): Promise<void> {
  const lines = await readCartLines();
  const next = lines.filter((line) => line.accountId !== accountId);

  if (quantity >= MIN_ITEM_QUANTITY) {
    next.push({
      accountId,
      quantity: Math.min(quantity, MAX_ITEM_QUANTITY),
    });
  }

  await writeCartLines(next);
}

export async function removeFromCart(accountId: number): Promise<void> {
  const lines = await readCartLines();
  await writeCartLines(lines.filter((line) => line.accountId !== accountId));
}
