/**
 * Credential export, same plain-text layout the legacy hesaplarim.php download
 * produced so customers keep receiving files in a familiar format.
 */

import { getCurrentCustomer } from "@/lib/auth/customer";
import { prisma } from "@/lib/prisma";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

const SEPARATOR = "=====================================";

type RouteContext = {
  params: Promise<{ code: string }>;
};

export async function GET(
  _request: Request,
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
      createdAt: true,
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

  const lines = [
    "=== YILDIZ HESAP - HESAP BİLGİLERİ ===",
    `Sipariş Kodu: ${order.orderCode}`,
    `Müşteri: ${customer.firstName} ${customer.lastName}`,
    `Ürün: ${order.productName}`,
    `Sipariş Tarihi: ${order.createdAt.toLocaleString("tr-TR")}`,
    `Hesap Sayısı: ${order.deliveredAccounts.length}`,
    SEPARATOR,
  ];

  order.deliveredAccounts.forEach((row, index) => {
    const parts = [
      row.username,
      row.password,
      row.email ?? "",
      row.emailPassword ?? "",
      row.totpSecret ?? "",
      row.accountCreatedDate === null
        ? ""
        : row.accountCreatedDate.toISOString().slice(0, 10),
    ];

    while (parts.length > 0 && parts[parts.length - 1] === "") {
      parts.pop();
    }

    lines.push(`HESAP${index + 1} | ${parts.join(":")}`);
  });

  lines.push(
    "",
    "=== ÖNEMLİ NOTLAR ===",
    "1. Bu bilgileri güvenli bir yerde saklayın",
    "2. Şifreleri kimseyle paylaşmayın",
    "3. 2FA aktifse, 2FA kodunu da kullanın",
    "4. Sorun yaşarsanız destek ekibiyle iletişime geçin",
    "====================",
  );

  return new Response(lines.join("\n"), {
    headers: {
      "Content-Type": "text/plain; charset=utf-8",
      "Content-Disposition": `attachment; filename="hesaplar_${order.orderCode}.txt"`,
      "Cache-Control": "no-store",
    },
  });
}
