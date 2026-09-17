/**
 * Shared shell and building blocks for customer e-mails.
 *
 * Written for mail clients, not browsers: table layout, inline styles, no
 * shorthand that Outlook drops, and nothing that depends on an external
 * stylesheet. Every colour is a literal because a mail client never sees our
 * CSS variables.
 */

import { SITE_URL } from "@/lib/seo/slugs";

export const BRAND_ORANGE = "#EE5A24";
/** Darker tone for orange text on white, which #EE5A24 fails AA at. */
export const BRAND_ORANGE_TEXT = "#C2410C";
export const TEXT_DARK = "#1E293B";
export const TEXT_MUTED = "#475569";
export const BORDER = "#E5E7EB";
export const PAGE_BG = "#F1F5F9";

const LOGO_WIDTH = 214;
const LOGO_HEIGHT = 40;

export function escapeHtml(value: string): string {
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

export function summaryRow(label: string, value: string): string {
  return `
              <tr>
                <td style="padding:10px 0;border-bottom:1px solid ${BORDER};font-family:Arial,Helvetica,sans-serif;font-size:14px;color:${TEXT_MUTED};">${escapeHtml(label)}</td>
                <td style="padding:10px 0;border-bottom:1px solid ${BORDER};font-family:Arial,Helvetica,sans-serif;font-size:14px;color:${TEXT_DARK};font-weight:bold;text-align:right;">${escapeHtml(value)}</td>
              </tr>`;
}

/** `body` may contain markup, so callers escape their own interpolations. */
export function noticeBox(
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
export function button(href: string, label: string): string {
  return `
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
              <tr>
                <td align="center" bgcolor="${BRAND_ORANGE}" style="border-radius:10px;">
                  <a href="${escapeHtml(href)}" style="display:inline-block;padding:14px 28px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#FFFFFF;text-decoration:none;border-radius:10px;">${escapeHtml(label)}</a>
                </td>
              </tr>
            </table>`;
}

export function paragraph(html: string): string {
  return `
            <p style="margin:0 0 12px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:${TEXT_MUTED};">${html}</p>`;
}

export type EmailLayoutParams = {
  /** Also the <h1> at the top of the card. */
  heading: string;
  previewText: string;
  recipientEmail: string;
  bodyHtml: string;
  footerNote: string;
};

export function renderEmailLayout(params: EmailLayoutParams): string {
  return `<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<meta name="x-apple-disable-message-reformatting" />
<title>${escapeHtml(params.heading)}</title>
</head>
<body style="margin:0;padding:0;background:${PAGE_BG};">
${preheader(params.previewText)}
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
            <h1 style="margin:0 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:24px;line-height:32px;color:${TEXT_DARK};">${escapeHtml(params.heading)}</h1>
${params.bodyHtml}
          </td>
        </tr>

        <tr>
          <td style="padding:20px 28px 8px 28px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:20px;color:#64748B;text-align:center;">
            ${params.footerNote}
            <a href="${SITE_URL}" style="color:#64748B;">yildizhesap.net</a> ·
            ${escapeHtml(params.recipientEmail)}
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>`;
}
