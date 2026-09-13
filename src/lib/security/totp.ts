/**
 * TOTP (RFC 6238) code generation, ported from legacy/2fa.php.
 *
 * Runs in the browser with Web Crypto so the secret of a purchased account
 * never leaves the customer's device.
 */

const BASE32_ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
const BASE32_BITS = 5;
const BYTE_BITS = 8;
const COUNTER_BYTES = 8;
const DIGITS = 6;
const DIGIT_MODULUS = 10 ** DIGITS;
const DYNAMIC_TRUNCATION_MASK = 0x0f;
const HIGH_BIT_MASK = 0x7f;
const BYTE_MASK = 0xff;

export const TOTP_PERIOD_SECONDS = 30;

export function decodeBase32(secret: string): Uint8Array {
  const normalized = secret
    .toUpperCase()
    .replace(/[\s\-_=]/g, "")
    .split("")
    .filter((char) => BASE32_ALPHABET.includes(char))
    .join("");

  const bytes: number[] = [];
  let buffer = 0;
  let bitsFilled = 0;

  for (const char of normalized) {
    buffer = (buffer << BASE32_BITS) | BASE32_ALPHABET.indexOf(char);
    bitsFilled += BASE32_BITS;

    if (bitsFilled >= BYTE_BITS) {
      bitsFilled -= BYTE_BITS;
      bytes.push((buffer >> bitsFilled) & BYTE_MASK);
    }
  }

  return new Uint8Array(bytes);
}

function counterToBytes(counter: number): Uint8Array {
  const bytes = new Uint8Array(COUNTER_BYTES);
  let remaining = counter;

  for (let index = COUNTER_BYTES - 1; index >= 0; index -= 1) {
    bytes[index] = remaining & BYTE_MASK;
    remaining = Math.floor(remaining / (BYTE_MASK + 1));
  }

  return bytes;
}

export async function generateTotp(
  secret: string,
  offsetSeconds = 0,
): Promise<string> {
  const keyBytes = decodeBase32(secret);

  if (keyBytes.length === 0) {
    throw new Error("Geçersiz secret key");
  }

  const counter = Math.floor(
    (Date.now() / 1_000 + offsetSeconds) / TOTP_PERIOD_SECONDS,
  );

  const key = await crypto.subtle.importKey(
    "raw",
    keyBytes as unknown as ArrayBuffer,
    { name: "HMAC", hash: "SHA-1" },
    false,
    ["sign"],
  );

  const signature = new Uint8Array(
    await crypto.subtle.sign(
      "HMAC",
      key,
      counterToBytes(counter) as unknown as ArrayBuffer,
    ),
  );

  const offset = signature[signature.length - 1]! & DYNAMIC_TRUNCATION_MASK;
  const code =
    (((signature[offset]! & HIGH_BIT_MASK) << 24) |
      ((signature[offset + 1]! & BYTE_MASK) << 16) |
      ((signature[offset + 2]! & BYTE_MASK) << 8) |
      (signature[offset + 3]! & BYTE_MASK)) %
    DIGIT_MODULUS;

  return String(code).padStart(DIGITS, "0");
}

export function secondsUntilNextCode(): number {
  return (
    TOTP_PERIOD_SECONDS -
    (Math.floor(Date.now() / 1_000) % TOTP_PERIOD_SECONDS)
  );
}
