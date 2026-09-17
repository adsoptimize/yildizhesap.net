/**
 * Delivery e-mail sent to the buyer once a payment is confirmed. Credentials
 * ride along as one .txt attachment per account, matching what the site offers
 * as a download, so the customer ends up with the same files either way.
 *
 * Guests are the main audience here: they have no panel to come back to, only
 * the order code and the tracking page.
 */

import { SITE_NAME, SITE_URL } from "@/lib/seo/slugs";
import {
  accountFileName,
  buildAccountFile,
} from "@/lib/shop/credentials-file";
import { readSupportTelegramUsername } from "@/lib/shop/support-contact";
import { sendCustomerEmail } from "./email";
import type { MailAttachment } from "./email";
import { prisma } from "@/lib/prisma";

type DeliveryEmailParams = {
  orderCode: string;
  productName: string;
  to: string | null;
  customerName: string | null;
  /** False when stock ran short: payment is in, accounts follow later. */
  fullyDelivered: boolean;
  /** Registered buyers get a panel link on top of the tracking page. */
  isMember: boolean;
};

function supportBlock(telegramUsername: string | null): string[] {
  if (telegramUsername === null || telegramUsername.trim() === "") {
    return [];
  }

  const handle = telegramUsername.trim().replace(/^@/, "");

  return [
    "",
    `Sorularınız için Telegram: @${handle} (https://t.me/${handle})`,
  ];
}

/** Never throws; mirrors the no-op-on-failure contract of notify/email.ts. */
export async function sendOrderDeliveryEmail(
  params: DeliveryEmailParams,
): Promise<void> {
  if (params.to === null || params.to.trim() === "") {
    return;
  }

  const accounts = await prisma.orderAccount.findMany({
    where: { orderCode: params.orderCode },
    orderBy: { id: "asc" },
    select: {
      username: true,
      password: true,
      email: true,
      emailPassword: true,
      totpSecret: true,
      accountCreatedDate: true,
    },
  });

  const telegramUsername = await readSupportTelegramUsername();
  const fileContext = {
    orderCode: params.orderCode,
    productName: params.productName,
    telegramUsername,
  };

  const attachments: MailAttachment[] = accounts.map((row, index) => ({
    filename: accountFileName(params.orderCode, index),
    content: buildAccountFile(row, index, accounts.length, fileContext),
  }));

  const greeting =
    params.customerName === null || params.customerName.trim() === ""
      ? "Merhaba,"
      : `Merhaba ${params.customerName.trim()},`;

  const lines = [
    greeting,
    "",
    `${params.orderCode} numaralı siparişiniz için ödemeniz alındı.`,
    "",
    `Ürün: ${params.productName}`,
    `Sipariş Kodu: ${params.orderCode}`,
    "",
  ];

  if (accounts.length === 0) {
    lines.push(
      "Hesaplarınız hazırlanıyor. Hazır olduğunda bu e-postanın devamı olarak",
      "size tekrar yazacağız.",
    );
  } else {
    lines.push(
      accounts.length === 1
        ? "Hesap bilgileriniz bu e-postaya .txt dosyası olarak eklenmiştir."
        : `${accounts.length} hesabınızın bilgileri bu e-postaya ayrı ayrı .txt dosyaları olarak eklenmiştir.`,
      "",
      "Önemli: Teslimattan sonra şifreyi ve kurtarma bilgilerini değiştirin,",
      "2FA anahtarı varsa iki adımlı doğrulamayı açın.",
    );

    if (!params.fullyDelivered) {
      lines.push(
        "",
        "Siparişinizin kalan kısmı stok tamamlandığında iletilecektir.",
      );
    }
  }

  lines.push(
    "",
    "Siparişinizi her zaman şu adresten görebilirsiniz:",
    `${SITE_URL}/siparis-takip`,
    `(Sipariş kodu: ${params.orderCode} ve bu e-posta adresi ile)`,
  );

  if (params.isMember) {
    lines.push("", `Hesabım: ${SITE_URL}/hesabim/siparis/${params.orderCode}`);
  }

  lines.push(...supportBlock(telegramUsername), "", SITE_NAME);

  await sendCustomerEmail({
    to: params.to.trim(),
    subject: accounts.length === 0
      ? `Ödemeniz alındı - ${params.orderCode}`
      : `Siparişiniz teslim edildi - ${params.orderCode}`,
    text: lines.join("\n"),
    attachments,
  });
}
