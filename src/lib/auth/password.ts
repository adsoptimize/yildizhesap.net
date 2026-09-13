/**
 * Password hashing with the Node runtime's scrypt — no external dependency.
 * Format: scrypt:<saltHex>:<hashHex>
 */

import {
  randomBytes,
  scrypt as scryptCallback,
  timingSafeEqual,
} from "node:crypto";
import { promisify } from "node:util";

const scrypt = promisify(scryptCallback) as (
  password: string,
  salt: Buffer,
  keylen: number,
) => Promise<Buffer>;

const ALGORITHM = "scrypt";
const SALT_BYTES = 16;
const KEY_BYTES = 64;

export async function hashPassword(password: string): Promise<string> {
  const salt = randomBytes(SALT_BYTES);
  const derived = await scrypt(password, salt, KEY_BYTES);

  return `${ALGORITHM}:${salt.toString("hex")}:${derived.toString("hex")}`;
}

export async function verifyPassword(
  password: string,
  stored: string,
): Promise<boolean> {
  const [algorithm, saltHex, hashHex] = stored.split(":");

  if (algorithm !== ALGORITHM || saltHex === undefined || hashHex === undefined) {
    return false;
  }

  const expected = Buffer.from(hashHex, "hex");
  const derived = await scrypt(password, Buffer.from(saltHex, "hex"), KEY_BYTES);

  return derived.length === expected.length && timingSafeEqual(derived, expected);
}
