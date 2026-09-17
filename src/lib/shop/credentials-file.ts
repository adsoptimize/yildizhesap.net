/**
 * Credential file layout, shared by the member download route and the guest
 * order tracking page so both produce identical files. The colon-separated
 * `HESAPn | ...` line is the format legacy hesaplarim.php emitted, kept so
 * customers with existing tooling are not broken.
 *
 * Deliberately free of server-only imports: the guest tracking page has the
 * credentials in client state already and builds the file in the browser
 * rather than going through an authenticated endpoint.
 */

export type CredentialRow = {
  username: string;
  password: string;
  email: string | null;
  emailPassword: string | null;
  totpSecret: string | null;
  /** Member pages select this column; the guest tracking query does not. */
  accountCreatedDate?: Date | string | null;
};

export type CredentialFileContext = {
  orderCode: string;
  productName: string;
  /** Stored without the leading "@" in contact_settings. */
  telegramUsername: string | null;
};

const SEPARATOR = "=====================================";

function formatCreatedDate(value: Date | string | null | undefined): string {
  if (value === null || value === undefined) {
    return "";
  }

  const date = value instanceof Date ? value : new Date(value);

  return Number.isNaN(date.getTime()) ? "" : date.toISOString().slice(0, 10);
}

/**
 * Trailing empty fields are dropped rather than left as `::::` so a account
 * without a 2FA secret produces `user:pass` instead of `user:pass::::`.
 */
function credentialLine(row: CredentialRow, index: number): string {
  const parts = [
    row.username,
    row.password,
    row.email ?? "",
    row.emailPassword ?? "",
    row.totpSecret ?? "",
    formatCreatedDate(row.accountCreatedDate),
  ];

  while (parts.length > 0 && parts[parts.length - 1] === "") {
    parts.pop();
  }

  return `HESAP${index + 1} | ${parts.join(":")}`;
}

function supportLines(telegramUsername: string | null): string[] {
  const lines = [
    "",
    "=== ÖNEMLİ NOTLAR ===",
    "1. Bu bilgileri güvenli bir yerde saklayın",
    "2. Teslimattan sonra şifreyi ve kurtarma bilgilerini değiştirin",
    "3. 2FA anahtarı varsa iki adımlı doğrulamayı açın",
  ];

  if (telegramUsername !== null && telegramUsername.trim() !== "") {
    const handle = telegramUsername.trim().replace(/^@/, "");
    lines.push(
      "",
      "=== DESTEK ===",
      `Sorun yaşarsanız Telegram üzerinden yazın: @${handle}`,
      `https://t.me/${handle}`,
    );
  }

  lines.push(SEPARATOR);

  return lines;
}

/** One file per purchased account, so buying 2 accounts yields 2 downloads. */
export function buildAccountFile(
  row: CredentialRow,
  index: number,
  total: number,
  context: CredentialFileContext,
): string {
  return [
    "=== YILDIZ HESAP - HESAP BİLGİLERİ ===",
    `Sipariş Kodu: ${context.orderCode}`,
    `Ürün: ${context.productName}`,
    `Hesap: ${index + 1} / ${total}`,
    SEPARATOR,
    credentialLine(row, index),
    ...supportLines(context.telegramUsername),
  ].join("\n");
}

/** Every account of the order in a single file. */
export function buildOrderFile(
  rows: readonly CredentialRow[],
  context: CredentialFileContext,
): string {
  return [
    "=== YILDIZ HESAP - HESAP BİLGİLERİ ===",
    `Sipariş Kodu: ${context.orderCode}`,
    `Ürün: ${context.productName}`,
    `Hesap Sayısı: ${rows.length}`,
    SEPARATOR,
    ...rows.map((row, index) => credentialLine(row, index)),
    ...supportLines(context.telegramUsername),
  ].join("\n");
}

export function accountFileName(orderCode: string, index: number): string {
  return `hesap_${orderCode}_${index + 1}.txt`;
}

export function orderFileName(orderCode: string): string {
  return `hesaplar_${orderCode}.txt`;
}
