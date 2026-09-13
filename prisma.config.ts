import { defineConfig } from "prisma/config";

/**
 * Build-time safe config: `prisma generate` must work on Vercel
 * even before Neon DATABASE_URL is connected.
 */
const databaseUrl =
  process.env.DATABASE_URL ??
  "postgresql://placeholder:placeholder@localhost:5432/placeholder?sslmode=require";

export default defineConfig({
  schema: "prisma/schema.prisma",
  migrations: {
    path: "prisma/migrations",
  },
  engine: "classic",
  datasource: {
    url: databaseUrl,
  },
});
