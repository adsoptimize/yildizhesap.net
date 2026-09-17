/**
 * HTML body for the order delivery e-mail.
 *
 * Written for mail clients, not browsers: table layout, inline styles, no
 * shorthand that Outlook drops, and nothing that depends on an external
 * stylesheet. Every colour is a literal because a mail client never sees our
 * CSS variables.
 *
 * Credentials are deliberately absent from the body — they travel only as
 * attachments, so a forwarded or screenshotted e-mail does not leak them.
 */

import { SITE_URL } from "@/lib/seo/slugs";

const BRAND_ORANGE = "#EE5A24";
/** Darker tone for orange text on white, which #EE5A24 fails AA at. */
const BRAND_ORANGE_TEXT = "#C2410C";
const TEXT_DARK = "#1E293B";
const TEXT_MUTED = "#475569";
const BORDER = "#E5E7EB";
const PAGE_BG = "#F1F5F9";
const LOGO_WIDTH = 214;
const LOGO_HEIGHT = 40;

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

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

/**
 * Inbox preview text. Hidden in the body, then padded so the client pulls the
 * intended sentence into the preview instead of the first visible words.
 */
function preheader(text: string): string {
  return [
    `<div style="display:none;font-size:1px;color:${PAGE_BG};line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">`,
    escapeHtml(text),
    "&nbsp;&zwnj;".repeat(60),
    "</div>",
  ].join("");
}

function summaryRow(label: string, value: string): string {
  return `
              <tr>
                <td style="padding:10px 0;border-bottom:1px solid ${BORDER};font-family:Arial,Helvetica,sans-serif;font-size:14px;color:${TEXT_MUTED};">${escapeHtml(label)}</td>
                <td style="padding:10px 0;border-bottom:1px solid ${BORDER};font-family:Arial,Helvetica,sans-serif;font-size:14px;color:${TEXT_DARK};font-weight:bold;text-align:right;">${escapeHtml(value)}</td>
              </tr>`;
}

function noticeBox(
  background: string,
  borderColor: string,
  title: string,
  body: string,
): string {
  return `
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:${background};border:1px solid ${borderColor};border-radius:10px;margin:0 0 20px 0;">
              <tr>
                <td style="padding:16px 18px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:${TEXT_DARK};">
                  <strong>${escapeHtml(title)}</strong><br />${body}
                </td>
              </tr>
            </table>`;
}

/** Outlook ignores padding on anchors, so the button is a table cell. */
function button(href: string, label: string): string {
  return `
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
              <tr>
                <td align="center" bgcolor="${BRAND_ORANGE}" style="border-radius:10px;">
                  <a href="${escapeHtml(href)}" style="display:inline-block;padding:14px 28px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#FFFFFF;text-decoration:none;border-radius:10px;">${escapeHtml(label)}</a>
                </td>
              </tr>
            </table>`;
}

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

  const attachmentNotice = delivered
    ? noticeBox(
        "#FFF7ED",
        "#FED7AA",
        "Dosyaları indirin ve saklayın",
        `Ekteki dosyalarda kullanıcı adı, şifre ve varsa 2FA anahtarı yer alır. Bu e-postayı silmeden önce dosyaları güvenli bir yere kaydettiğinizden emin olun.`,
      )
    : "";

  const securityNotice = delivered
    ? noticeBox(
        "#FEFCE8",
        "#FDE68A",
        "İlk iş: şifreyi değiştirin",
        "Hesabı teslim aldıktan sonra şifreyi ve kurtarma bilgilerini kendinize göre güncelleyin. 2FA anahtarı verildiyse iki adımlı doğrulamayı açın.",
      )
    : "";

  const partialNotice =
    delivered && !params.fullyDelivered
      ? noticeBox(
          "#EFF6FF",
          "#BFDBFE",
          "Siparişinizin kalan kısmı",
          "Stok tamamlandığında kalan hesaplar aynı şekilde e-posta ile iletilecektir.",
        )
      : "";

  const trackingHelp = params.isMember
    ? `Siparişinizi <a href="${orderUrl}" style="color:${BRAND_ORANGE_TEXT};font-weight:bold;">hesabım sayfanızdan</a> her zaman görebilirsiniz.`
    : `Siparişinizi <a href="${SITE_URL}/siparis-takip" style="color:${BRAND_ORANGE_TEXT};font-weight:bold;">sipariş takip sayfasından</a> her zaman görebilirsiniz. Sipariş kodunuz <strong>${escapeHtml(params.orderCode)}</strong> ve bu e-posta adresi yeterli.`;

  const support =
    params.telegramHandle === null
      ? ""
      : `
            <p style="margin:0 0 4px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:${TEXT_MUTED};">
              Bir sorun olursa Telegram'dan yazabilirsiniz:
              <a href="https://t.me/${escapeHtml(params.telegramHandle)}" style="color:${BRAND_ORANGE_TEXT};font-weight:bold;text-decoration:none;">@${escapeHtml(params.telegramHandle)}</a>
            </p>`;

  const previewText = delivered
    ? `${params.orderCode} numaralı siparişinizin hesap bilgileri ekte.`
    : `${params.orderCode} numaralı siparişiniz için ödemeniz alındı.`;

  return `<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<meta name="x-apple-disable-message-reformatting" />
<title>${escapeHtml(heading)}</title>
</head>
<body style="margin:0;padding:0;background:${PAGE_BG};">
${preheader(previewText)}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:${PAGE_BG};padding:24px 12px;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;">

        <!-- Logo sits on an explicit white cell so dark-mode clients do not
             swallow the navy wordmark. -->
        <tr>
          <td align="center" bgcolor="#FFFFFF" style="background:#FFFFFF;border:1px solid ${BORDER};border-radius:14px 14px 0 0;padding:24px;">
            <img src="${SITE_URL}/images/logo-adsoptimize.png" width="${LOGO_WIDTH}" height="${LOGO_HEIGHT}" alt="YildizHesap" style="display:block;border:0;width:${LOGO_WIDTH}px;height:${LOGO_HEIGHT}px;" />
          </td>
        </tr>

        <tr>
          <td bgcolor="${BRAND_ORANGE}" style="background:${BRAND_ORANGE};height:4px;line-height:4px;font-size:0;">&nbsp;</td>
        </tr>

        <tr>
          <td bgcolor="#FFFFFF" style="background:#FFFFFF;border:1px solid ${BORDER};border-top:0;border-radius:0 0 14px 14px;padding:32px 28px;">

            <h1 style="margin:0 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:24px;line-height:32px;color:${TEXT_DARK};">${escapeHtml(heading)}</h1>
            <p style="margin:0 0 20px 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:24px;color:${TEXT_MUTED};">
              ${escapeHtml(params.greeting)}<br />${intro}
            </p>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
              ${summaryRow("Ürün", params.productName)}
              ${summaryRow("Sipariş Kodu", params.orderCode)}
              ${delivered ? summaryRow("Hesap Sayısı", String(params.accountCount)) : ""}
            </table>

            ${attachmentNotice}
            ${securityNotice}
            ${partialNotice}

            ${button(orderUrl, params.isMember ? "Siparişimi Görüntüle" : "Siparişimi Takip Et")}

            <p style="margin:0 0 4px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:${TEXT_MUTED};">
              ${trackingHelp}
            </p>
            ${support}
          </td>
        </tr>

        <tr>
          <td style="padding:20px 28px 8px 28px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:20px;color:#64748B;text-align:center;">
            Bu e-posta <a href="${SITE_URL}" style="color:#64748B;">yildizhesap.net</a> üzerinden verdiğiniz sipariş için
            ${escapeHtml(params.recipientEmail)} adresine otomatik olarak gönderildi.
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>`;
}
