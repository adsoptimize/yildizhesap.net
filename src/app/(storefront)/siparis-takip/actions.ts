"use server";

import { getClientIp } from "@/lib/auth/session";
import { consumeRateLimit, ORDER_TRACK_RULE } from "@/lib/shop/rate-limit";
import type { TrackState } from "@/lib/shop/form-state";
import { readSupportTelegramUsername } from "@/lib/shop/support-contact";
import { prisma } from "@/lib/prisma";

/** Order code + e-mail, the same pair legacy guest_order_track.php required. */
export async function trackOrderAction(
  _previousState: TrackState,
  formData: FormData,
): Promise<TrackState> {
  const orderCode = String(formData.get("orderCode") ?? "").trim().toUpperCase();
  const email = String(formData.get("email") ?? "").trim().toLowerCase();

  if (orderCode === "" || email === "") {
    return { error: "Sipariş numarası ve e-posta adresi gereklidir.", order: null };
  }

  const ip = await getClientIp();
  const limit = await consumeRateLimit(ip, ORDER_TRACK_RULE);

  if (!limit.allowed) {
    return { error: limit.message ?? "Çok fazla deneme yaptınız.", order: null };
  }

  const order = await prisma.order.findFirst({
    where: {
      orderCode,
      OR: [{ email }, { user: { email } }],
    },
    select: {
      orderCode: true,
      productName: true,
      quantity: true,
      totalPrice: true,
      status: true,
      deliveryStatus: true,
      createdAt: true,
      payment: { select: { status: true } },
      deliveredAccounts: {
        orderBy: { id: "asc" },
        select: {
          id: true,
          username: true,
          password: true,
          email: true,
          emailPassword: true,
          totpSecret: true,
        },
      },
    },
  });

  if (order === null) {
    return {
      error:
        "Bu bilgilerle sipariş bulunamadı. Sipariş numarasını ve e-posta adresini kontrol edin.",
      order: null,
    };
  }

  // Only needed for the support footer of the credential files, so skip the
  // query until there is actually something to download.
  const telegramUsername =
    order.deliveredAccounts.length === 0
      ? null
      : await readSupportTelegramUsername();

  return {
    error: null,
    order: {
      orderCode: order.orderCode,
      productName: order.productName,
      quantity: order.quantity,
      totalPrice: order.totalPrice.toString(),
      status: order.status,
      deliveryStatus: order.deliveryStatus,
      paymentStatus: order.payment?.status ?? null,
      createdAt: order.createdAt.toISOString(),
      credentials: order.deliveredAccounts,
      telegramUsername,
    },
  };
}
