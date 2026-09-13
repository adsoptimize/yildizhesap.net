/**
 * Order and ticket identifiers.
 *
 * The legacy app used `order_{unixTime}_{userId}_{uniqid()}`, which no longer
 * fits the 20 character order_id column and leaked the customer id. Since no
 * legacy orders survived the database loss, orders now use a short code that is
 * safe to read out over WhatsApp. Ticket codes keep the legacy `TK#####` shape
 * so support conversations look the same as before.
 */

import { randomInt } from "node:crypto";
import { prisma } from "@/lib/prisma";

const ORDER_CODE_PREFIX = "YH-";
/** Ambiguous characters (0/O, 1/I) are left out on purpose. */
const ORDER_CODE_ALPHABET = "ABCDEFGHJKLMNPRSTUVWXYZ23456789";
const ORDER_CODE_BODY_LENGTH = 8;
const CODE_ATTEMPT_LIMIT = 10;

const TICKET_CODE_PREFIX = "TK";
const TICKET_CODE_DIGITS = 5;
const TICKET_CODE_MIN = 1;
const TICKET_CODE_MAX = 99_999;

function randomOrderCode(): string {
  let body = "";

  for (let index = 0; index < ORDER_CODE_BODY_LENGTH; index += 1) {
    body += ORDER_CODE_ALPHABET[randomInt(ORDER_CODE_ALPHABET.length)];
  }

  return `${ORDER_CODE_PREFIX}${body}`;
}

export async function generateOrderCode(): Promise<string> {
  for (let attempt = 0; attempt < CODE_ATTEMPT_LIMIT; attempt += 1) {
    const code = randomOrderCode();
    const existing = await prisma.order.findUnique({
      where: { orderCode: code },
      select: { id: true },
    });

    if (existing === null) {
      return code;
    }
  }

  throw new Error("Sipariş kodu üretilemedi, lütfen tekrar deneyin.");
}

export async function generateTicketCode(): Promise<string> {
  for (let attempt = 0; attempt < CODE_ATTEMPT_LIMIT; attempt += 1) {
    const code = `${TICKET_CODE_PREFIX}${String(
      randomInt(TICKET_CODE_MIN, TICKET_CODE_MAX + 1),
    ).padStart(TICKET_CODE_DIGITS, "0")}`;

    const existing = await prisma.supportTicket.findUnique({
      where: { ticketCode: code },
      select: { id: true },
    });

    if (existing === null) {
      return code;
    }
  }

  throw new Error("Destek talebi numarası üretilemedi, lütfen tekrar deneyin.");
}
