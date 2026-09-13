"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { hashPassword, verifyPassword } from "@/lib/auth/password";
import { requireCustomer } from "@/lib/auth/customer";
import { destroySession, logUserActivity } from "@/lib/auth/session";
import { ACCOUNT_PATH } from "@/lib/shop/constants";
import { generateTicketCode } from "@/lib/shop/codes";
import type { CustomerFormState } from "@/lib/shop/form-state";
import { prisma } from "@/lib/prisma";

const MIN_PASSWORD_LENGTH = 6;
const MIN_SUBJECT_LENGTH = 5;
const MAX_SUBJECT_LENGTH = 255;
const MIN_MESSAGE_LENGTH = 10;
const MAX_MESSAGE_LENGTH = 5_000;

const SUPPORT_PATH = `${ACCOUNT_PATH}/destek`;
const SETTINGS_PATH = `${ACCOUNT_PATH}/ayarlar`;

const TICKET_PRIORITIES = ["low", "medium", "high", "urgent"] as const;
type TicketPriority = (typeof TICKET_PRIORITIES)[number];

function readText(formData: FormData, key: string): string {
  return String(formData.get(key) ?? "").trim();
}

function readPriority(formData: FormData): TicketPriority {
  const value = readText(formData, "priority");
  return TICKET_PRIORITIES.includes(value as TicketPriority)
    ? (value as TicketPriority)
    : "medium";
}

export async function logoutCustomerAction(): Promise<void> {
  await destroySession();
  redirect("/");
}

export async function updateProfileAction(
  _previousState: CustomerFormState,
  formData: FormData,
): Promise<CustomerFormState> {
  const customer = await requireCustomer();

  const firstName = readText(formData, "firstName");
  const lastName = readText(formData, "lastName");
  const phone = readText(formData, "phone");

  if (firstName === "") {
    return { error: "Ad alanı gereklidir.", success: null };
  }

  if (lastName === "") {
    return { error: "Soyad alanı gereklidir.", success: null };
  }

  await prisma.user.update({
    where: { id: customer.id },
    data: {
      firstName,
      lastName,
      phone: phone === "" ? null : phone,
    },
  });

  revalidatePath(SETTINGS_PATH);

  return { error: null, success: "Profil bilgileriniz güncellendi." };
}

export async function changePasswordAction(
  _previousState: CustomerFormState,
  formData: FormData,
): Promise<CustomerFormState> {
  const customer = await requireCustomer();

  const currentPassword = String(formData.get("currentPassword") ?? "");
  const newPassword = String(formData.get("newPassword") ?? "");
  const confirmPassword = String(formData.get("confirmPassword") ?? "");

  if (newPassword.length < MIN_PASSWORD_LENGTH) {
    return { error: "Şifre en az 6 karakter olmalıdır.", success: null };
  }

  if (newPassword !== confirmPassword) {
    return { error: "Şifreler eşleşmiyor.", success: null };
  }

  const user = await prisma.user.findUnique({
    where: { id: customer.id },
    select: { passwordHash: true },
  });

  if (user === null) {
    return { error: "Hesap bulunamadı.", success: null };
  }

  const valid = await verifyPassword(currentPassword, user.passwordHash);

  if (!valid) {
    return { error: "Mevcut şifreniz hatalı.", success: null };
  }

  await prisma.user.update({
    where: { id: customer.id },
    data: { passwordHash: await hashPassword(newPassword) },
  });

  await logUserActivity(customer.id, "password_change", "Şifre güncellendi");

  return { error: null, success: "Şifreniz güncellendi." };
}

export async function createTicketAction(
  _previousState: CustomerFormState,
  formData: FormData,
): Promise<CustomerFormState> {
  const customer = await requireCustomer();

  const orderCode = readText(formData, "orderCode");
  const subject = readText(formData, "subject");
  const message = readText(formData, "message");

  if (orderCode === "") {
    return { error: "Sipariş seçmelisiniz.", success: null };
  }

  if (subject.length < MIN_SUBJECT_LENGTH) {
    return { error: "Konu başlığı en az 5 karakter olmalı", success: null };
  }

  if (subject.length > MAX_SUBJECT_LENGTH) {
    return { error: "Konu başlığı en fazla 255 karakter olabilir", success: null };
  }

  if (message.length < MIN_MESSAGE_LENGTH) {
    return { error: "Mesaj en az 10 karakter olmalı", success: null };
  }

  if (message.length > MAX_MESSAGE_LENGTH) {
    return { error: "Mesaj en fazla 5000 karakter olabilir", success: null };
  }

  const order = await prisma.order.findFirst({
    where: { orderCode, userId: customer.id },
    select: { orderCode: true },
  });

  if (order === null) {
    return { error: "Geçersiz sipariş.", success: null };
  }

  const existing = await prisma.supportTicket.findUnique({
    where: { orderCode },
    select: { id: true },
  });

  if (existing !== null) {
    return {
      error: "Bu sipariş için zaten bir destek talebi oluşturulmuş.",
      success: null,
    };
  }

  const ticketCode = await generateTicketCode();

  await prisma.$transaction(async (tx) => {
    await tx.supportTicket.create({
      data: {
        ticketCode,
        userId: customer.id,
        orderCode,
        subject,
        message,
        priority: readPriority(formData),
        status: "open",
        lastReplyAt: new Date(),
        lastReplyBy: "customer",
      },
    });

    await tx.ticketReply.create({
      data: {
        ticketCode,
        userId: customer.id,
        message,
        isAdminReply: false,
      },
    });
  });

  revalidatePath(SUPPORT_PATH);

  return {
    error: null,
    success: `Destek talebi başarıyla oluşturuldu. Talep numaranız: ${ticketCode}`,
  };
}

export async function replyTicketAction(
  _previousState: CustomerFormState,
  formData: FormData,
): Promise<CustomerFormState> {
  const customer = await requireCustomer();

  const ticketCode = readText(formData, "ticketCode");
  const message = readText(formData, "message");

  if (message.length < MIN_MESSAGE_LENGTH) {
    return { error: "Mesaj en az 10 karakter olmalı", success: null };
  }

  const ticket = await prisma.supportTicket.findFirst({
    where: { ticketCode, userId: customer.id },
    select: { status: true, lastReplyBy: true },
  });

  if (ticket === null) {
    return { error: "Ticket bulunamadı.", success: null };
  }

  if (ticket.status === "closed") {
    return { error: "Kapalı ticket'lara yanıt verilemez.", success: null };
  }

  if (ticket.lastReplyBy === "customer") {
    return {
      error: "Destek ekibimizden yanıt gelmeden yeni mesaj gönderemezsiniz.",
      success: null,
    };
  }

  await prisma.$transaction(async (tx) => {
    await tx.ticketReply.create({
      data: {
        ticketCode,
        userId: customer.id,
        message,
        isAdminReply: false,
      },
    });

    await tx.supportTicket.update({
      where: { ticketCode },
      data: {
        lastReplyAt: new Date(),
        lastReplyBy: "customer",
        status: ticket.status === "resolved" ? "open" : ticket.status,
      },
    });
  });

  revalidatePath(SUPPORT_PATH);

  return { error: null, success: "Yanıtınız başarıyla gönderildi." };
}
