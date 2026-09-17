/**
 * HTML body for the order delivery e-mail.
 *
 * Credentials are deliberately absent from the body — they travel only as
 * attachments, so a forwarded or screenshotted e-mail does not leak them.
 */

import {
  BRAND_ORANGE_TEXT,
  button,
  escapeHtml,
  noticeBox,
  paragraph,
  renderEmailLayout,
  summaryRow,
  TEXT_MUTED,
} from "./email-layout";
import { SITE_URL } from "@/lib/seo/slugs";

export type OrderEmailTemplateParams = {
  orderCode: string;
  productName: string;
  accountCount: number;
  greeting: string;
  /** False when stock ran short: payment is in, accounts follow later. */
  fullyDelivered: boolean;
  /** Registered buyers are linked to the panel instead of the tracking page. */
  isMember: boolean;
  recipientEmail: string;
  telegramHandle: string | null;
};

export function renderOrderEmailHtml(
  params: OrderEmailTemplateParams,
): string {
  const delivered = params.accountCount > 0;

  const heading = delivered
    ? params.accountCount === 1
      ? "Hesap bilginiz hazır"
      : `${params.accountCount} hesap bilginiz hazır`
    : "Ödemeniz alındı";

  const intro = delivered
    ? params.accountCount === 1
      ? "Hesap bilgileriniz bu e-postaya <strong>.txt dosyası olarak eklenmiştir</strong>."
      : `Her hesabın bilgileri bu e-postaya <strong>ayrı ayrı .txt dosyaları olarak eklenmiştir</strong> (${params.accountCount} dosya).`
    : "Ödemeniz başarıyla alındı. Hesaplarınız hazırlanıyor; hazır olduğunda size tekrar yazacağız.";

  const orderUrl = params.isMember
    ? `${SITE_URL}/hesabim/siparis/${encodeURIComponent(params.orderCode)}`
    : `${SITE_URL}/siparis-takip`;

  const trackingHelp = params.isMember
    ? `Siparişinizi <a href="${orderUrl}" style="color:${BRAND_ORANGE_TEXT};font-weight:bold;">hesabım sayfanızdan</a> her zaman görebilirsiniz.`
    : `Siparişinizi <a href="${SITE_URL}/siparis-takip" style="color:${BRAND_ORANGE_TEXT};font-weight:bold;">sipariş takip sayfasından</a> her zaman görebilirsiniz. Sipariş kodunuz <strong>${escapeHtml(params.orderCode)}</strong> ve bu e-posta adresi yeterli.`;

  const support =
    params.telegramHandle === null
      ? ""
      : paragraph(
          `Bir sorun olursa Telegram'dan yazabilirsiniz: <a href="https://t.me/${escapeHtml(params.telegramHandle)}" style="color:${BRAND_ORANGE_TEXT};font-weight:bold;text-decoration:none;">@${escapeHtml(params.telegramHandle)}</a>`,
        );

  const bodyHtml = [
    `
            <p style="margin:0 0 20px 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:24px;color:${TEXT_MUTED};">
              ${escapeHtml(params.greeting)}<br />${intro}
            </p>`,
    `
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
              ${summaryRow("Ürün", params.productName)}
              ${summaryRow("Sipariş Kodu", params.orderCode)}
              ${delivered ? summaryRow("Hesap Sayısı", String(params.accountCount)) : ""}
            </table>`,
    delivered
      ? noticeBox(
          "#FFF7ED",
          "#FED7AA",
          "Dosyaları indirin ve saklayın",
          "Ekteki dosyalarda kullanıcı adı, şifre ve varsa 2FA anahtarı yer alır. Bu e-postayı silmeden önce dosyaları güvenli bir yere kaydettiğinizden emin olun.",
        )
      : "",
    delivered
      ? noticeBox(
          "#FEFCE8",
          "#FDE68A",
          "İlk iş: şifreyi değiştirin",
          "Hesabı teslim aldıktan sonra şifreyi ve kurtarma bilgilerini kendinize göre güncelleyin. 2FA anahtarı verildiyse iki adımlı doğrulamayı açın.",
        )
      : "",
    delivered && !params.fullyDelivered
      ? noticeBox(
          "#EFF6FF",
          "#BFDBFE",
          "Siparişinizin kalan kısmı",
          "Stok tamamlandığında kalan hesaplar aynı şekilde e-posta ile iletilecektir.",
        )
      : "",
    button(orderUrl, params.isMember ? "Siparişimi Görüntüle" : "Siparişimi Takip Et"),
    paragraph(trackingHelp),
    support,
  ].join("");

  return renderEmailLayout({
    heading,
    previewText: delivered
      ? `${params.orderCode} numaralı siparişinizin hesap bilgileri ekte.`
      : `${params.orderCode} numaralı siparişiniz için ödemeniz alındı.`,
    recipientEmail: params.recipientEmail,
    bodyHtml,
    footerNote: "Bu e-posta verdiğiniz sipariş için otomatik gönderildi.",
  });
}
