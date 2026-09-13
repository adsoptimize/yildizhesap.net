"use server";

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
