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
import { renderOrderEmailHtml } from "./order-email-template";
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

/** Stored with or without the leading "@"; normalised once for both bodies. */
function normaliseHandle(telegramUsername: string | null): string | null {
  if (telegramUsername === null || telegramUsername.trim() === "") {
    return null;
  }

  return telegramUsername.trim().replace(/^@/, "");
}

function supportBlock(handle: string | null): string[] {
  if (handle === null) {
    return [];
  }

  return [
    "",
    `Sorularınız için Telegram: @${handle} (https://t.me/${handle})`,
  ];
}

/**
 * Leads with what happened rather than the brand name: the sender column
 * already shows who wrote, so the subject is better spent on the outcome and
 * the order code the customer will search for later.
 */
function buildSubject(orderCode: string, accountCount: number): string {
  if (accountCount === 0) {
    return `Ödemeniz alındı — ${orderCode} hazırlanıyor`;
  }

  if (accountCount === 1) {
    return `Hesap bilginiz hazır — ${orderCode}`;
  }

  return `${accountCount} hesap bilginiz hazır — ${orderCode}`;
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
  const telegramHandle = normaliseHandle(telegramUsername);
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

  lines.push(...supportBlock(telegramHandle), "", SITE_NAME);

  const recipient = params.to.trim();

  await sendCustomerEmail({
    to: recipient,
    subject: buildSubject(params.orderCode, accounts.length),
    text: lines.join("\n"),
    html: renderOrderEmailHtml({
      orderCode: params.orderCode,
      productName: params.productName,
      accountCount: accounts.length,
      greeting,
      fullyDelivered: params.fullyDelivered,
      isMember: params.isMember,
      recipientEmail: recipient,
      telegramHandle,
    }),
    attachments,
  });
}

const PREVIEW_ORDER_CODE = "TEST0000";
const PREVIEW_ACCOUNT_COUNT = 2;

/**
 * Admin test-send. Renders the real delivery template with sample data and a
 * sample attachment, so it verifies the SMTP credentials and shows the operator
 * exactly what a buyer receives in one go.
 */
export async function sendTemplatePreviewEmail(to: string): Promise<boolean> {
  const recipient = to.trim();
  const telegramHandle = normaliseHandle(await readSupportTelegramUsername());
  const productName = "Örnek Ürün — Eski Facebook Hesabı";
  const fileContext = {
    orderCode: PREVIEW_ORDER_CODE,
    productName,
    telegramUsername: telegramHandle,
  };

  const sampleRows = [
    {
      username: "ornek.kullanici1",
      password: "OrnekSifre123",
      email: "ornek1@mail.com",
      emailPassword: "OrnekMailSifre1",
      totpSecret: "ORNEK2FAANAHTARI",
      accountCreatedDate: new Date("2014-03-02"),
    },
    {
      username: "ornek.kullanici2",
      password: "OrnekSifre456",
      email: "ornek2@mail.com",
      emailPassword: "OrnekMailSifre2",
      totpSecret: null,
      accountCreatedDate: null,
    },
  ];

  return sendCustomerEmail({
    to: recipient,
    subject: `[TEST] ${buildSubject(PREVIEW_ORDER_CODE, PREVIEW_ACCOUNT_COUNT)}`,
    text: [
      "Bu bir test e-postasıdır; gerçek bir sipariş değildir.",
      "",
      "Bu mesajı aldıysanız SMTP ayarlarınız çalışıyor ve sipariş teslimat",
      "e-postaları müşterilerinize ulaşacak. Ekteki dosyalar da müşterinin",
      "alacağı biçimin birebir örneğidir.",
      "",
      `Örnek sipariş kodu: ${PREVIEW_ORDER_CODE}`,
    ].join("\n"),
    html: renderOrderEmailHtml({
      orderCode: PREVIEW_ORDER_CODE,
      productName,
      accountCount: PREVIEW_ACCOUNT_COUNT,
      greeting: "Merhaba, bu bir test gönderimidir.",
      fullyDelivered: true,
      isMember: false,
      recipientEmail: recipient,
      telegramHandle,
    }),
    attachments: sampleRows.map((row, index) => ({
      filename: accountFileName(PREVIEW_ORDER_CODE, index),
      content: buildAccountFile(
        row,
        index,
        sampleRows.length,
        fileContext,
      ),
    })),
  });
}
