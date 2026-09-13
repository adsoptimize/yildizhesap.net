"use server";

import { revalidatePath } from "next/cache";
import { TicketPriority, TicketStatus } from "@prisma/client";
import { requireAdmin } from "@/lib/auth/admin";
import { prisma } from "@/lib/prisma";

const ADMIN_SUPPORT_PATH = "/admin/support";

function isTicketStatus(value: string): value is TicketStatus {
  return Object.values(TicketStatus).includes(value as TicketStatus);
}

function isTicketPriority(value: string): value is TicketPriority {
  return Object.values(TicketPriority).includes(value as TicketPriority);
}

export async function updateTicketAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const ticketCode = String(formData.get("ticketCode") ?? "").trim();
  const status = String(formData.get("status") ?? "");
  const priority = String(formData.get("priority") ?? "");

  if (ticketCode === "" || !isTicketStatus(status) || !isTicketPriority(priority)) {
    return;
  }

  await prisma.supportTicket.update({
    where: { ticketCode },
    data: { status, priority },
  });

  revalidatePath(ADMIN_SUPPORT_PATH);
  revalidatePath(`${ADMIN_SUPPORT_PATH}/${ticketCode}`);
}

export async function replyTicketAction(formData: FormData): Promise<void> {
  const admin = await requireAdmin();

  const ticketCode = String(formData.get("ticketCode") ?? "").trim();
  const message = String(formData.get("message") ?? "").trim();

  if (ticketCode === "" || message === "") {
    return;
  }

  await prisma.$transaction([
    prisma.ticketReply.create({
      data: {
        ticketCode,
        adminId: admin.id,
        message,
        isAdminReply: true,
      },
    }),
    prisma.supportTicket.update({
      where: { ticketCode },
      data: {
        status: TicketStatus.waiting_customer,
        lastReplyAt: new Date(),
        lastReplyBy: "admin",
      },
    }),
  ]);

  revalidatePath(ADMIN_SUPPORT_PATH);
  revalidatePath(`${ADMIN_SUPPORT_PATH}/${ticketCode}`);
}
