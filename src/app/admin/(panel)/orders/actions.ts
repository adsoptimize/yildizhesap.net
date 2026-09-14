"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { OrderStatus, DeliveryStatus } from "@prisma/client";
import { requireAdmin } from "@/lib/auth/admin";
import { prisma } from "@/lib/prisma";

const ADMIN_ORDERS_PATH = "/admin/orders";

function isOrderStatus(value: string): value is OrderStatus {
  return Object.values(OrderStatus).includes(value as OrderStatus);
}

function isDeliveryStatus(value: string): value is DeliveryStatus {
  return Object.values(DeliveryStatus).includes(value as DeliveryStatus);
}

export async function updateOrderStatusAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const id = Number(formData.get("id"));
  const status = String(formData.get("status") ?? "");
  const deliveryStatus = String(formData.get("deliveryStatus") ?? "");

  if (!isOrderStatus(status) || !isDeliveryStatus(deliveryStatus)) {
    return;
  }

  const order = await prisma.order.update({
    where: { id },
    data: { status, deliveryStatus },
    select: { orderCode: true },
  });

  revalidatePath(ADMIN_ORDERS_PATH);
  revalidatePath(`${ADMIN_ORDERS_PATH}/${order.orderCode}`);
}

export async function addOrderAccountAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const orderCode = String(formData.get("orderCode") ?? "").trim();
  const username = String(formData.get("username") ?? "").trim();
  const password = String(formData.get("password") ?? "").trim();
  const email = String(formData.get("email") ?? "").trim();
  const emailPassword = String(formData.get("emailPassword") ?? "").trim();
  const accountCreatedDate = String(formData.get("accountCreatedDate") ?? "").trim();
  const totpSecret = String(formData.get("totpSecret") ?? "").trim();

  if (
    orderCode === "" ||
    username === "" ||
    password === "" ||
    email === "" ||
    emailPassword === "" ||
    accountCreatedDate === ""
  ) {
    return;
  }

  await prisma.orderAccount.create({
    data: {
      orderCode,
      username,
      password,
      email,
      emailPassword,
      totpSecret: totpSecret === "" ? null : totpSecret,
      accountCreatedDate: new Date(accountCreatedDate),
    },
  });

  await prisma.order.update({
    where: { orderCode },
    data: { deliveryStatus: DeliveryStatus.delivered, status: OrderStatus.completed },
  });

  revalidatePath(ADMIN_ORDERS_PATH);
  revalidatePath(`${ADMIN_ORDERS_PATH}/${orderCode}`);
}

/**
 * Permanently deletes an order. Refuses to delete orders that have been
 * completed or partially/fully delivered to preserve financial audit trail.
 * Deletion cascades to `PaymentItem`, `OrderAccount`, `CryptoPayment`
 * relations via Prisma schema.
 */
export async function deleteOrderAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const id = Number(formData.get("id"));
  if (Number.isNaN(id)) return;

  const order = await prisma.order.findUnique({
    where: { id },
    select: {
      status: true,
      deliveryStatus: true,
      _count: { select: { deliveredAccounts: true } },
    },
  });
  if (order === null) return;

  // Refuse to erase completed sales or delivered credentials.
  const isProtected =
    order.status === OrderStatus.completed ||
    order.deliveryStatus === DeliveryStatus.delivered ||
    order.deliveryStatus === DeliveryStatus.partial ||
    order._count.deliveredAccounts > 0;
  if (isProtected) return;

  await prisma.order.delete({ where: { id } });

  revalidatePath(ADMIN_ORDERS_PATH);
  redirect(ADMIN_ORDERS_PATH);
}

export async function deleteOrderAccountAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const id = Number(formData.get("id"));
  const row = await prisma.orderAccount.findUnique({
    where: { id },
    select: { orderCode: true },
  });

  if (row === null) {
    return;
  }

  await prisma.orderAccount.delete({ where: { id } });
  revalidatePath(`${ADMIN_ORDERS_PATH}/${row.orderCode}`);
}
