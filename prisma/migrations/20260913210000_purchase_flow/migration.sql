-- DropForeignKey
ALTER TABLE "crypto_payments" DROP CONSTRAINT "crypto_payments_user_id_fkey";

-- DropForeignKey
ALTER TABLE "orders" DROP CONSTRAINT "orders_user_id_fkey";

-- DropIndex
DROP INDEX "crypto_payments_order_id_idx";

-- AlterTable
ALTER TABLE "account_stock" ADD COLUMN     "account_created_date" DATE,
ADD COLUMN     "email_password" VARCHAR(255),
ADD COLUMN     "totp_secret" VARCHAR(255);

-- AlterTable
ALTER TABLE "crypto_payments" ADD COLUMN     "customer_name" VARCHAR(255),
ADD COLUMN     "email" VARCHAR(255),
ADD COLUMN     "is_guest_order" BOOLEAN NOT NULL DEFAULT false,
ADD COLUMN     "payment_method" VARCHAR(30) NOT NULL DEFAULT 'cryptomus',
ADD COLUMN     "phone" VARCHAR(30),
ADD COLUMN     "shopier_payment_id" VARCHAR(100),
ALTER COLUMN "user_id" DROP NOT NULL,
ALTER COLUMN "order_id" SET DATA TYPE VARCHAR(20);

-- AlterTable
ALTER TABLE "order_accounts" ADD COLUMN     "account_id" INTEGER,
ALTER COLUMN "email" DROP NOT NULL,
ALTER COLUMN "email_password" DROP NOT NULL,
ALTER COLUMN "account_created_date" DROP NOT NULL;

-- AlterTable
ALTER TABLE "orders" ADD COLUMN     "customer_name" VARCHAR(255),
ADD COLUMN     "email" VARCHAR(255),
ADD COLUMN     "is_guest_order" BOOLEAN NOT NULL DEFAULT false,
ADD COLUMN     "payment_method" VARCHAR(30),
ADD COLUMN     "phone" VARCHAR(30),
ALTER COLUMN "user_id" DROP NOT NULL;

-- CreateTable
CREATE TABLE "guest_rate_limits" (
    "id" SERIAL NOT NULL,
    "identifier" VARCHAR(255) NOT NULL,
    "action" VARCHAR(50) NOT NULL,
    "attempt_count" INTEGER NOT NULL DEFAULT 1,
    "window_start" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "blocked_until" TIMESTAMP(3),
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "guest_rate_limits_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "guest_rate_limits_identifier_action_key" ON "guest_rate_limits"("identifier", "action");

-- CreateIndex
CREATE UNIQUE INDEX "crypto_payments_order_id_key" ON "crypto_payments"("order_id");

-- CreateIndex
CREATE INDEX "crypto_payments_email_idx" ON "crypto_payments"("email");

-- CreateIndex
CREATE INDEX "orders_email_idx" ON "orders"("email");

-- CreateIndex
CREATE UNIQUE INDEX "support_tickets_order_id_key" ON "support_tickets"("order_id");

-- AddForeignKey
ALTER TABLE "account_stock" ADD CONSTRAINT "account_stock_order_id_fkey" FOREIGN KEY ("order_id") REFERENCES "orders"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "crypto_payments" ADD CONSTRAINT "crypto_payments_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "crypto_payments" ADD CONSTRAINT "crypto_payments_order_id_fkey" FOREIGN KEY ("order_id") REFERENCES "orders"("order_id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "orders" ADD CONSTRAINT "orders_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "payment_items" ADD CONSTRAINT "payment_items_payment_id_fkey" FOREIGN KEY ("payment_id") REFERENCES "crypto_payments"("id") ON DELETE CASCADE ON UPDATE CASCADE;

