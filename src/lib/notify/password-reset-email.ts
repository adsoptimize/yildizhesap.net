/**
 * Password reset link. Kept separate from the order mail because the audience
 * and the risk are different: this one must make an unrequested reset easy to
 * recognise and safe to ignore.
 */

import { RESET_TOKEN_TTL_MINUTES } from "@/lib/auth/password-reset";
import {
  button,
  escapeHtml,
  noticeBox,
  paragraph,
  renderEmailLayout,
} from "./email-layout";
import { sendCustomerEmail } from "./email";
import { SITE_NAME, SITE_URL } from "@/lib/seo/slugs";

const RESET_PATH = "/sifre-sifirla";

export type PasswordResetEmailParams = {
  to: string;
  firstName: string;
  token: string;
};

function resetUrl(token: string): string {
  return `${SITE_URL}${RESET_PATH}?token=${encodeURIComponent(token)}`;
}

/** Never throws; the caller reports success either way. */
export async function sendPasswordResetEmail(
  params: PasswordResetEmailParams,
): Promise<boolean> {
  const url = resetUrl(params.token);
  const greeting =
    params.firstName.trim() === ""
      ? "Merhaba,"
      : `Merhaba ${params.firstName.trim()},`;

  const bodyHtml = [
    paragraph(
      `${escapeHtml(greeting)}<br />Hesabınız için şifre sıfırlama talebi aldık. Yeni şifrenizi belirlemek için aşağıdaki düğmeye tıklayın.`,
    ),
    button(url, "Yeni Şifre Belirle"),
    noticeBox(
      "#FEFCE8",
      "#FDE68A",
      `Bağlantı ${RESET_TOKEN_TTL_MINUTES} dakika geçerli`,
      "Süre dolarsa endişelenmeyin, giriş sayfasından yeni bir bağlantı isteyebilirsiniz. Bağlantı yalnızca bir kez kullanılabilir.",
    ),
    noticeBox(
      "#EFF6FF",
      "#BFDBFE",
      "Bu talebi siz yapmadıysanız",
      "Bu e-postayı yok sayabilirsiniz; şifreniz değişmez. Bağlantıya tıklanmadığı sürece hesabınızda hiçbir şey olmaz.",
    ),
    paragraph(
      `Düğme çalışmazsa bu adresi tarayıcınıza kopyalayın:<br /><span style="word-break:break-all;">${escapeHtml(url)}</span>`,
    ),
  ].join("");

  return sendCustomerEmail({
    to: params.to,
    subject: "Şifre sıfırlama bağlantınız",
    text: [
      greeting,
      "",
      "Hesabınız için şifre sıfırlama talebi aldık. Yeni şifrenizi belirlemek",
      "için aşağıdaki bağlantıyı açın:",
      "",
      url,
      "",
      `Bu bağlantı ${RESET_TOKEN_TTL_MINUTES} dakika geçerlidir ve yalnızca bir kez kullanılabilir.`,
      "",
      "Bu talebi siz yapmadıysanız bu e-postayı yok sayabilirsiniz; şifreniz",
      "değişmez.",
      "",
      SITE_NAME,
    ].join("\n"),
    html: renderEmailLayout({
      heading: "Şifrenizi sıfırlayın",
      previewText:
        "Yeni şifrenizi belirlemek için gönderdiğimiz bağlantıyı kullanın.",
      recipientEmail: params.to,
      bodyHtml,
      footerNote: "Bu e-posta şifre sıfırlama talebiniz üzerine gönderildi.",
    }),
  });
}
