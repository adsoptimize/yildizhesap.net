/**
 * Creates or updates the admin account. Credentials come from the environment
 * so they never end up in shell history files or the repository.
 *
 * ADMIN_USERNAME=... ADMIN_EMAIL=... ADMIN_PASSWORD=... npm run admin:create
 */

import { PrismaClient } from "@prisma/client";
import { hashPassword } from "../src/lib/auth/password";

const MIN_PASSWORD_LENGTH = 10;

const prisma = new PrismaClient();

async function main(): Promise<void> {
  const username = process.env.ADMIN_USERNAME?.trim();
  const email = process.env.ADMIN_EMAIL?.trim().toLowerCase();
  const password = process.env.ADMIN_PASSWORD;
  const firstName = process.env.ADMIN_FIRST_NAME?.trim() ?? "Admin";
  const lastName = process.env.ADMIN_LAST_NAME?.trim() ?? "Yönetici";

  if (
    username === undefined ||
    username === "" ||
    email === undefined ||
    email === "" ||
    password === undefined
  ) {
    throw new Error(
      "ADMIN_USERNAME, ADMIN_EMAIL ve ADMIN_PASSWORD ortam değişkenleri zorunlu",
    );
  }

  if (password.length < MIN_PASSWORD_LENGTH) {
    throw new Error(`Şifre en az ${MIN_PASSWORD_LENGTH} karakter olmalı`);
  }

  const passwordHash = await hashPassword(password);

  const user = await prisma.user.upsert({
    where: { email },
    update: {
      username,
      passwordHash,
      firstName,
      lastName,
      isAdmin: true,
      isActive: true,
      emailVerified: true,
      loginAttempts: 0,
    },
    create: {
      username,
      email,
      passwordHash,
      firstName,
      lastName,
      isAdmin: true,
      isActive: true,
      emailVerified: true,
    },
    select: { id: true, username: true, email: true },
  });

  console.log(`admin hazır → id: ${user.id}, kullanıcı: ${user.username}`);
}

main()
  .catch((error: unknown) => {
    console.error(error instanceof Error ? error.message : error);
    process.exitCode = 1;
  })
  .finally(() => prisma.$disconnect());
