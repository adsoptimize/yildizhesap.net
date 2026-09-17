/**
 * Credential export. Without `?hesap=` the whole order is returned in one file;
 * with it, a single account is returned so a customer who bought several gets
 * one file per account. The layout lives in lib/shop/credentials-file so the
 * guest tracking page produces the same thing.
 */

import { getCurrentCustomer } from "@/lib/auth/customer";
import {
  accountFileName,
  buildAccountFile,
  buildOrderFile,
  orderFileName,
} from "@/lib/shop/credentials-file";
import { readSupportTelegramUsername } from "@/lib/shop/support-contact";
import { prisma } from "@/lib/prisma";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

type RouteContext = {
  params: Promise<{ code: string }>;
};

export async function GET(
  request: Request,
  context: RouteContext,
): Promise<Response> {
  const customer = await getCurrentCustomer();

  if (customer === null) {
    return new Response("Oturum bulunamadı", { status: 401 });
  }

  const { code } = await context.params;

  const order = await prisma.order.findFirst({
    where: { orderCode: code, userId: customer.id },
    select: {
      orderCode: true,
      productName: true,
      deliveredAccounts: {
        orderBy: { id: "asc" },
        select: {
          username: true,
          password: true,
          email: true,
          emailPassword: true,
          totpSecret: true,
          accountCreatedDate: true,
        },
      },
    },
  });

  if (order === null) {
    return new Response("Sipariş bulunamadı", { status: 404 });
  }

  if (order.deliveredAccounts.length === 0) {
    return new Response("Bu sipariş için hesap bilgisi bulunamadı", {
      status: 404,
    });
  }

  const fileContext = {
    orderCode: order.orderCode,
    productName: order.productName,
    telegramUsername: await readSupportTelegramUsername(),
  };

  const requested = new URL(request.url).searchParams.get("hesap");
  let body: string;
  let fileName: string;

  if (requested === null) {
    body = buildOrderFile(order.deliveredAccounts, fileContext);
    fileName = orderFileName(order.orderCode);
  } else {
    // 1-based in the URL so it matches the row numbers shown on the page.
    const position = Number(requested);

    if (
      !Number.isInteger(position) ||
      position < 1 ||
      position > order.deliveredAccounts.length
    ) {
      return new Response("Geçersiz hesap numarası", { status: 400 });
    }

    const index = position - 1;
    body = buildAccountFile(
      order.deliveredAccounts[index],
      index,
      order.deliveredAccounts.length,
      fileContext,
    );
    fileName = accountFileName(order.orderCode, index);
  }

  return new Response(body, {
    headers: {
      "Content-Type": "text/plain; charset=utf-8",
      "Content-Disposition": `attachment; filename="${fileName}"`,
      "Cache-Control": "no-store",
    },
  });
}
