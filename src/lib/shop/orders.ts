/**
 * Order creation and automatic delivery.
 *
 * The legacy app had three delivery paths (StockManager + PaymentProcessor,
 * AutoAccountAssignment, and the Shopier guest branch) writing to three
 * different table sets, which is why cart orders frequently never got delivered.
 * There is a single path here: checkout writes `orders` + `crypto_payments` +
 * `payment_items`, and a confirmed payment reserves stock FIFO and copies the
 * credentials into `order_accounts`.
 */

import { prisma } from "@/lib/prisma";
import { sendAdminTelegramMessage } from "@/lib/notify/telegram";
import { formatCurrency } from "@/lib/admin/format";
import { generateOrderCode } from "./codes";
import type { Cart } from "./cart";
import type { PaymentMethod } from "./constants";

export type BuyerInfo = {
  userId: number | null;
  fullName: string;
  email: string;
  phone: string | null;
};

export type PendingOrder = {
  orderCode: string;
  totalPrice: string;
  productName: string;
};

/** Order rows keep one headline product name, like the legacy orders table. */
function buildProductName(cart: Cart): string {
  const [first] = cart.items;

  if (first === undefined) {
    return "Hesap siparişi";
  }

  const extraCount = cart.items.length - 1;

  return extraCount === 0
    ? first.title
    : `${first.title} (+${extraCount} ürün)`;
}

export async function createPendingOrder(input: {
  cart: Cart;
  buyer: BuyerInfo;
  paymentMethod: PaymentMethod;
  currency: string;
}): Promise<PendingOrder> {
  const { cart, buyer, paymentMethod, currency } = input;
  const [firstItem] = cart.items;

  if (firstItem === undefined) {
    throw new Error("Sepet boş.");
  }

  const orderCode = await generateOrderCode();
  const isGuestOrder = buyer.userId === null;

  await prisma.$transaction(async (tx) => {
    await tx.order.create({
      data: {
        orderCode,
        userId: buyer.userId,
        productName: buildProductName(cart),
        category: firstItem.categoryName,
        quantity: cart.totalQuantity,
        unitPrice: firstItem.unitPrice,
        totalPrice: cart.totalPrice,
        status: "pending",
        deliveryStatus: "pending",
        paymentMethod,
        isGuestOrder,
        email: buyer.email,
        customerName: buyer.fullName,
        phone: buyer.phone,
      },
    });

    const payment = await tx.cryptoPayment.create({
      data: {
        orderCode,
        userId: buyer.userId,
        paymentMethod,
        isGuestOrder,
        email: buyer.email,
        customerName: buyer.fullName,
        phone: buyer.phone,
        amount: cart.totalPrice,
        currency,
        status: "pending",
      },
      select: { id: true },
    });

    await tx.paymentItem.createMany({
      data: cart.items.map((item) => ({
        paymentId: payment.id,
        accountId: item.accountId,
        quantity: item.quantity,
        unitPrice: item.unitPrice,
        totalPrice: item.lineTotal,
      })),
    });
  });

  return {
    orderCode,
    totalPrice: cart.totalPrice,
    productName: buildProductName(cart),
  };
}

export async function attachPaymentProviderData(
  orderCode: string,
  data: { cryptomusUuid?: string; paymentUrl?: string },
): Promise<void> {
  await prisma.cryptoPayment.update({
    where: { orderCode },
    data: {
      cryptomusUuid: data.cryptomusUuid,
      paymentUrl: data.paymentUrl,
    },
  });
}

export type PaymentConfirmation = {
  orderCode: string;
  paidAmount?: string | null;
  txid?: string | null;
  cryptomusUuid?: string | null;
  shopierPaymentId?: string | null;
  email?: string | null;
  customerName?: string | null;
};

export type ConfirmationResult =
  | "delivered"
  | "partially_delivered"
  | "out_of_stock"
  | "already_processed"
  | "not_found";

type DeliveryOutcome = {
  deliveredCount: number;
  missingCount: number;
};

/**
 * Marks a payment as paid and delivers the purchased credentials. Safe to call
 * twice: a webhook retry finds the order already delivered and returns early.
 */
export async function confirmPayment(
  confirmation: PaymentConfirmation,
): Promise<ConfirmationResult> {
  const payment = await prisma.cryptoPayment.findUnique({
    where: { orderCode: confirmation.orderCode },
    select: {
      id: true,
      status: true,
      amount: true,
      currency: true,
      email: true,
      customerName: true,
      userId: true,
      items: {
        select: { accountId: true, quantity: true },
        orderBy: { id: "asc" },
      },
      order: {
        select: {
          id: true,
          orderCode: true,
          deliveryStatus: true,
          productName: true,
          userId: true,
        },
      },
    },
  });

  if (payment === null) {
    return "not_found";
  }

  if (payment.status === "paid" && payment.order.deliveryStatus === "delivered") {
    return "already_processed";
  }

  await prisma.cryptoPayment.update({
    where: { id: payment.id },
    data: {
      status: "paid",
      paymentStatus: "paid",
      paymentAmount: confirmation.paidAmount ?? undefined,
      txid: confirmation.txid ?? undefined,
      cryptomusUuid: confirmation.cryptomusUuid ?? undefined,
      shopierPaymentId: confirmation.shopierPaymentId ?? undefined,
      email: confirmation.email ?? undefined,
      customerName: confirmation.customerName ?? undefined,
    },
  });

  const outcome = await deliverOrder({
    orderId: payment.order.id,
    orderCode: payment.order.orderCode,
    userId: payment.order.userId,
    items: payment.items,
  });

  const fullyDelivered = outcome.missingCount === 0;
  const deliveryStatus = fullyDelivered
    ? "delivered"
    : outcome.deliveredCount > 0
      ? "partial"
      : "pending";

  await prisma.order.update({
    where: { id: payment.order.id },
    data: {
      status: fullyDelivered ? "completed" : "processing",
      deliveryStatus,
      email: confirmation.email ?? undefined,
      customerName: confirmation.customerName ?? undefined,
    },
  });

  if (payment.order.userId !== null) {
    await prisma.userActivityLog.create({
      data: {
        userId: payment.order.userId,
        activityType: "order_paid",
        description: `${payment.order.orderCode} numaralı sipariş ödendi`,
      },
    });
  }

  const amountText =
    payment.currency === "TRY"
      ? formatCurrency(payment.amount.toString())
      : `${payment.amount.toString()} ${payment.currency}`;

  if (fullyDelivered) {
    await sendAdminTelegramMessage(
      [
        "✅ *BAŞARILI ÖDEME*",
        "",
        `💰 *Tutar:* ${amountText}`,
        `🆔 *Sipariş Kodu:* \`${payment.order.orderCode}\``,
        `👤 *Müşteri:* ${payment.customerName ?? "-"} (${payment.email ?? "-"})`,
        `📦 *Ürün:* ${payment.order.productName}`,
        `📊 *Teslim Edilen:* ${outcome.deliveredCount}`,
      ].join("\n"),
    );
  } else {
    await sendAdminTelegramMessage(
      [
        "🚨 *STOK YETERSİZLİĞİ*",
        "",
        `🆔 *Sipariş Kodu:* \`${payment.order.orderCode}\``,
        `📦 *Ürün:* ${payment.order.productName}`,
        `👤 *Müşteri:* ${payment.customerName ?? "-"} (${payment.email ?? "-"})`,
        `📊 *Teslim Edilen:* ${outcome.deliveredCount}`,
        `📉 *Eksik:* ${outcome.missingCount}`,
        "",
        "⚠️ Ödeme alındı, stok girildikten sonra teslimat tamamlanmalı.",
      ].join("\n"),
    );
  }

  if (fullyDelivered) {
    return "delivered";
  }

  return outcome.deliveredCount > 0 ? "partially_delivered" : "out_of_stock";
}

/**
 * Reserves unsold stock rows oldest-first and copies them into order_accounts.
 * Claiming is guarded by `isSold: false`, so two concurrent webhooks cannot sell
 * the same credentials twice.
 */
async function deliverOrder(input: {
  orderId: number;
  orderCode: string;
  userId: number | null;
  items: { accountId: number; quantity: number }[];
}): Promise<DeliveryOutcome> {
  let deliveredCount = 0;
  let missingCount = 0;

  for (const item of input.items) {
    const alreadyDelivered = await prisma.orderAccount.count({
      where: { orderCode: input.orderCode, accountId: item.accountId },
    });
    const remaining = item.quantity - alreadyDelivered;

    deliveredCount += alreadyDelivered;

    if (remaining <= 0) {
      continue;
    }

    const claimed = await prisma.$transaction(async (tx) => {
      const candidates = await tx.accountStock.findMany({
        where: { accountId: item.accountId, isSold: false },
        select: { id: true },
        orderBy: { createdAt: "asc" },
        take: remaining,
      });

      if (candidates.length === 0) {
        return [];
      }

      const candidateIds = candidates.map((row) => row.id);

      await tx.accountStock.updateMany({
        where: { id: { in: candidateIds }, isSold: false },
        data: {
          isSold: true,
          soldToUserId: input.userId,
          orderId: input.orderId,
          soldAt: new Date(),
        },
      });

      return tx.accountStock.findMany({
        where: { id: { in: candidateIds }, orderId: input.orderId },
        select: {
          id: true,
          username: true,
          password: true,
          email: true,
          emailPassword: true,
          totpSecret: true,
          accountCreatedDate: true,
        },
      });
    });

    if (claimed.length > 0) {
      await prisma.orderAccount.createMany({
        data: claimed.map((row) => ({
          orderCode: input.orderCode,
          accountId: item.accountId,
          username: row.username,
          password: row.password,
          email: row.email,
          emailPassword: row.emailPassword,
          totpSecret: row.totpSecret,
          accountCreatedDate: row.accountCreatedDate,
        })),
      });

      await prisma.account.update({
        where: { id: item.accountId },
        data: { salesCount: { increment: claimed.length } },
      });
    }

    await syncAccountStockQuantity(item.accountId);

    deliveredCount += claimed.length;
    missingCount += remaining - claimed.length;
  }

  return { deliveredCount, missingCount };
}

export async function syncAccountStockQuantity(accountId: number): Promise<void> {
  const available = await prisma.accountStock.count({
    where: { accountId, isSold: false },
  });

  await prisma.account.update({
    where: { id: accountId },
    data: { stockQuantity: available },
  });
}

export async function markPaymentFailed(
  orderCode: string,
  status: "failed" | "expired" | "cancelled",
): Promise<void> {
  const payment = await prisma.cryptoPayment.findUnique({
    where: { orderCode },
    select: { id: true, status: true, order: { select: { id: true } } },
  });

  if (payment === null || payment.status === "paid") {
    return;
  }

  await prisma.cryptoPayment.update({
    where: { id: payment.id },
    data: { status, paymentStatus: status },
  });

  await prisma.order.update({
    where: { id: payment.order.id },
    data: { status: "cancelled" },
  });
}
