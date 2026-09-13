-- CreateEnum
CREATE TYPE "AccountStatus" AS ENUM ('active', 'inactive');

-- CreateEnum
CREATE TYPE "DeliveryType" AS ENUM ('instant', 'manual');

-- CreateEnum
CREATE TYPE "OrderStatus" AS ENUM ('pending', 'processing', 'completed', 'cancelled');

-- CreateEnum
CREATE TYPE "DeliveryStatus" AS ENUM ('pending', 'partial', 'delivered', 'failed');

-- CreateEnum
CREATE TYPE "CryptoPaymentStatus" AS ENUM ('pending', 'paid', 'failed', 'expired', 'cancelled');

-- CreateEnum
CREATE TYPE "TicketStatus" AS ENUM ('open', 'in_progress', 'waiting_customer', 'resolved', 'closed');

-- CreateEnum
CREATE TYPE "TicketPriority" AS ENUM ('low', 'medium', 'high', 'urgent');

-- CreateEnum
CREATE TYPE "TicketReplyBy" AS ENUM ('customer', 'admin');

-- CreateEnum
CREATE TYPE "SessionInvalidatedBy" AS ENUM ('user', 'admin', 'security', 'expired');

-- CreateEnum
CREATE TYPE "BanType" AS ENUM ('permanent', 'temporary');

-- CreateTable
CREATE TABLE "users" (
    "id" SERIAL NOT NULL,
    "username" VARCHAR(50) NOT NULL,
    "email" VARCHAR(100) NOT NULL,
    "password_hash" VARCHAR(255) NOT NULL,
    "first_name" VARCHAR(50) NOT NULL,
    "last_name" VARCHAR(50) NOT NULL,
    "phone" VARCHAR(20),
    "registration_ip" VARCHAR(45),
    "balance" DECIMAL(10,2) NOT NULL DEFAULT 0,
    "is_active" BOOLEAN NOT NULL DEFAULT true,
    "is_admin" BOOLEAN NOT NULL DEFAULT false,
    "email_verified" BOOLEAN NOT NULL DEFAULT false,
    "verification_token" VARCHAR(64),
    "reset_token" VARCHAR(64),
    "reset_token_expires" TIMESTAMP(3),
    "login_attempts" INTEGER NOT NULL DEFAULT 0,
    "last_login_attempt" TIMESTAMP(3),
    "last_login" TIMESTAMP(3),
    "deactivation_reason" TEXT,
    "deactivated_until" TIMESTAMP(3),
    "deactivated_by" INTEGER,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "users_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "categories" (
    "id" SERIAL NOT NULL,
    "name" VARCHAR(100) NOT NULL,
    "slug" VARCHAR(100) NOT NULL,
    "seo_slug" VARCHAR(255),
    "description" TEXT,
    "icon" VARCHAR(50) NOT NULL,
    "image_url" VARCHAR(500),
    "is_active" BOOLEAN NOT NULL DEFAULT true,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "categories_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "accounts" (
    "id" SERIAL NOT NULL,
    "category_id" INTEGER NOT NULL,
    "title" VARCHAR(255) NOT NULL,
    "seo_slug" VARCHAR(500),
    "description" TEXT,
    "price" DECIMAL(10,2) NOT NULL,
    "old_price" DECIMAL(10,2),
    "stock_quantity" INTEGER NOT NULL DEFAULT 0,
    "platform" VARCHAR(50) NOT NULL,
    "account_type" VARCHAR(100) NOT NULL,
    "limit_info" VARCHAR(255),
    "features" TEXT,
    "technical_info" TEXT,
    "status" "AccountStatus" NOT NULL DEFAULT 'active',
    "is_verified" BOOLEAN NOT NULL DEFAULT false,
    "is_premium" BOOLEAN NOT NULL DEFAULT false,
    "is_featured" BOOLEAN NOT NULL DEFAULT false,
    "is_secure" BOOLEAN NOT NULL DEFAULT false,
    "instant_delivery" BOOLEAN NOT NULL DEFAULT true,
    "support_24_7" BOOLEAN NOT NULL DEFAULT true,
    "guarantee_30_days" BOOLEAN NOT NULL DEFAULT true,
    "warranty_days" INTEGER NOT NULL DEFAULT 30,
    "delivery_type" "DeliveryType" NOT NULL DEFAULT 'instant',
    "rating" DECIMAL(2,1) NOT NULL DEFAULT 0,
    "views" INTEGER NOT NULL DEFAULT 0,
    "sales_count" INTEGER NOT NULL DEFAULT 0,
    "location" VARCHAR(100),
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "accounts_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "account_features" (
    "id" SERIAL NOT NULL,
    "account_id" INTEGER NOT NULL,
    "feature_name" VARCHAR(255) NOT NULL,
    "feature_value" VARCHAR(255) NOT NULL,

    CONSTRAINT "account_features_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "account_images" (
    "id" SERIAL NOT NULL,
    "account_id" INTEGER NOT NULL,
    "image_path" VARCHAR(500) NOT NULL,
    "is_primary" BOOLEAN NOT NULL DEFAULT false,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "account_images_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "account_stock" (
    "id" SERIAL NOT NULL,
    "account_id" INTEGER NOT NULL,
    "username" VARCHAR(255) NOT NULL,
    "password" VARCHAR(255) NOT NULL,
    "email" VARCHAR(255),
    "additional_info" TEXT,
    "is_sold" BOOLEAN NOT NULL DEFAULT false,
    "sold_to_user_id" INTEGER,
    "order_id" INTEGER,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "sold_at" TIMESTAMP(3),

    CONSTRAINT "account_stock_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "active_sessions" (
    "id" SERIAL NOT NULL,
    "session_token" VARCHAR(128) NOT NULL,
    "user_id" INTEGER NOT NULL,
    "ip_address" VARCHAR(45) NOT NULL,
    "user_agent" TEXT NOT NULL,
    "device_fingerprint" VARCHAR(64) NOT NULL,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "last_activity" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "expires_at" TIMESTAMP(3) NOT NULL,
    "is_active" BOOLEAN NOT NULL DEFAULT true,
    "invalidated_by" "SessionInvalidatedBy",
    "invalidated_at" TIMESTAMP(3),
    "login_location" VARCHAR(100),
    "device_info" JSONB,

    CONSTRAINT "active_sessions_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "contact_settings" (
    "id" SERIAL NOT NULL,
    "setting_key" VARCHAR(100) NOT NULL,
    "setting_value" TEXT NOT NULL,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "contact_settings_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "crypto_payments" (
    "id" SERIAL NOT NULL,
    "user_id" INTEGER NOT NULL,
    "order_id" VARCHAR(100) NOT NULL,
    "cryptomus_uuid" VARCHAR(36),
    "amount" DECIMAL(10,2) NOT NULL,
    "currency" VARCHAR(10) NOT NULL DEFAULT 'USD',
    "network" VARCHAR(20),
    "to_currency" VARCHAR(10),
    "payment_amount" DECIMAL(18,8),
    "address" VARCHAR(255),
    "payment_url" TEXT,
    "status" "CryptoPaymentStatus" NOT NULL DEFAULT 'pending',
    "payment_status" VARCHAR(20),
    "txid" VARCHAR(255),
    "expired_at" TIMESTAMP(3),
    "callback_data" TEXT,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "crypto_payments_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "crypto_settings" (
    "id" SERIAL NOT NULL,
    "setting_key" VARCHAR(100) NOT NULL,
    "setting_value" TEXT,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "crypto_settings_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "crypto_webhook_logs" (
    "id" SERIAL NOT NULL,
    "payload" TEXT NOT NULL,
    "headers" TEXT,
    "status" VARCHAR(50),
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "crypto_webhook_logs_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "faqs" (
    "id" SERIAL NOT NULL,
    "question" VARCHAR(500) NOT NULL,
    "answer" TEXT NOT NULL,
    "order_index" INTEGER NOT NULL DEFAULT 0,
    "is_active" BOOLEAN NOT NULL DEFAULT true,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "faqs_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "ip_bans" (
    "id" SERIAL NOT NULL,
    "ip_address" VARCHAR(45) NOT NULL,
    "reason" TEXT NOT NULL,
    "ban_type" "BanType" NOT NULL DEFAULT 'temporary',
    "banned_until" TIMESTAMP(3),
    "banned_by" INTEGER NOT NULL,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,
    "is_active" BOOLEAN NOT NULL DEFAULT true,

    CONSTRAINT "ip_bans_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "ip_limit_resets" (
    "id" SERIAL NOT NULL,
    "ip_address" VARCHAR(45) NOT NULL,
    "reset_by" INTEGER NOT NULL,
    "accounts_before_reset" INTEGER NOT NULL DEFAULT 0,
    "reason" TEXT,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "ip_limit_resets_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "legal_pages" (
    "id" SERIAL NOT NULL,
    "page_type" VARCHAR(50) NOT NULL,
    "title" VARCHAR(255) NOT NULL,
    "content" TEXT NOT NULL,
    "meta_description" TEXT,
    "is_active" BOOLEAN NOT NULL DEFAULT true,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "legal_pages_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "orders" (
    "id" SERIAL NOT NULL,
    "order_id" VARCHAR(20) NOT NULL,
    "user_id" INTEGER NOT NULL,
    "product_name" VARCHAR(255) NOT NULL,
    "category" VARCHAR(100) NOT NULL,
    "quantity" INTEGER NOT NULL,
    "unit_price" DECIMAL(10,2) NOT NULL,
    "total_price" DECIMAL(10,2) NOT NULL,
    "order_date" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "status" "OrderStatus" NOT NULL DEFAULT 'pending',
    "delivery_status" "DeliveryStatus" NOT NULL DEFAULT 'pending',
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "orders_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "order_accounts" (
    "id" SERIAL NOT NULL,
    "order_id" VARCHAR(20) NOT NULL,
    "username" VARCHAR(255) NOT NULL,
    "password" VARCHAR(255) NOT NULL,
    "email" VARCHAR(255) NOT NULL,
    "email_password" VARCHAR(255) NOT NULL,
    "totp_secret" VARCHAR(255),
    "account_created_date" DATE NOT NULL,
    "is_active" BOOLEAN NOT NULL DEFAULT true,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "order_accounts_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "payment_items" (
    "id" SERIAL NOT NULL,
    "payment_id" INTEGER NOT NULL,
    "account_id" INTEGER NOT NULL,
    "quantity" INTEGER NOT NULL DEFAULT 1,
    "unit_price" DECIMAL(10,2) NOT NULL,
    "total_price" DECIMAL(10,2) NOT NULL,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "payment_items_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "popup_ad_views" (
    "id" SERIAL NOT NULL,
    "ip_address" VARCHAR(45) NOT NULL,
    "user_id" INTEGER,
    "view_date" DATE NOT NULL,
    "view_count" INTEGER NOT NULL DEFAULT 1,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,
    "content_hash" VARCHAR(64) DEFAULT '',
    "visitor_id" VARCHAR(128),

    CONSTRAINT "popup_ad_views_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "site_settings" (
    "id" SERIAL NOT NULL,
    "setting_key" VARCHAR(100) NOT NULL,
    "setting_value" TEXT,
    "setting_type" VARCHAR(255),
    "description" TEXT,
    "category" VARCHAR(50) NOT NULL DEFAULT 'general',
    "order_index" INTEGER NOT NULL DEFAULT 0,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "site_settings_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "support_tickets" (
    "id" SERIAL NOT NULL,
    "ticket_id" VARCHAR(20) NOT NULL,
    "user_id" INTEGER NOT NULL,
    "order_id" VARCHAR(20) NOT NULL,
    "subject" VARCHAR(255) NOT NULL,
    "message" TEXT NOT NULL,
    "status" "TicketStatus" NOT NULL DEFAULT 'open',
    "priority" "TicketPriority" NOT NULL DEFAULT 'medium',
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,
    "last_reply_at" TIMESTAMP(3),
    "last_reply_by" "TicketReplyBy",

    CONSTRAINT "support_tickets_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "ticket_replies" (
    "id" SERIAL NOT NULL,
    "ticket_id" VARCHAR(20) NOT NULL,
    "user_id" INTEGER,
    "admin_id" INTEGER,
    "message" TEXT NOT NULL,
    "is_admin_reply" BOOLEAN NOT NULL DEFAULT false,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "ticket_replies_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "user_activity_log" (
    "id" SERIAL NOT NULL,
    "user_id" INTEGER,
    "activity_type" VARCHAR(50) NOT NULL,
    "description" TEXT,
    "ip_address" VARCHAR(45),
    "user_agent" TEXT,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "user_activity_log_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "user_status_history" (
    "id" SERIAL NOT NULL,
    "user_id" INTEGER NOT NULL,
    "old_status" VARCHAR(50),
    "new_status" VARCHAR(50) NOT NULL,
    "reason" TEXT,
    "changed_by" INTEGER,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "user_status_history_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "users_username_key" ON "users"("username");

-- CreateIndex
CREATE UNIQUE INDEX "users_email_key" ON "users"("email");

-- CreateIndex
CREATE UNIQUE INDEX "categories_slug_key" ON "categories"("slug");

-- CreateIndex
CREATE UNIQUE INDEX "categories_seo_slug_key" ON "categories"("seo_slug");

-- CreateIndex
CREATE UNIQUE INDEX "accounts_seo_slug_key" ON "accounts"("seo_slug");

-- CreateIndex
CREATE INDEX "accounts_category_id_idx" ON "accounts"("category_id");

-- CreateIndex
CREATE INDEX "accounts_status_idx" ON "accounts"("status");

-- CreateIndex
CREATE INDEX "account_features_account_id_idx" ON "account_features"("account_id");

-- CreateIndex
CREATE INDEX "account_images_account_id_idx" ON "account_images"("account_id");

-- CreateIndex
CREATE INDEX "account_stock_account_id_is_sold_idx" ON "account_stock"("account_id", "is_sold");

-- CreateIndex
CREATE UNIQUE INDEX "active_sessions_session_token_key" ON "active_sessions"("session_token");

-- CreateIndex
CREATE INDEX "active_sessions_user_id_idx" ON "active_sessions"("user_id");

-- CreateIndex
CREATE INDEX "active_sessions_expires_at_idx" ON "active_sessions"("expires_at");

-- CreateIndex
CREATE UNIQUE INDEX "contact_settings_setting_key_key" ON "contact_settings"("setting_key");

-- CreateIndex
CREATE INDEX "crypto_payments_user_id_idx" ON "crypto_payments"("user_id");

-- CreateIndex
CREATE INDEX "crypto_payments_order_id_idx" ON "crypto_payments"("order_id");

-- CreateIndex
CREATE UNIQUE INDEX "crypto_settings_setting_key_key" ON "crypto_settings"("setting_key");

-- CreateIndex
CREATE INDEX "ip_bans_ip_address_is_active_idx" ON "ip_bans"("ip_address", "is_active");

-- CreateIndex
CREATE UNIQUE INDEX "legal_pages_page_type_key" ON "legal_pages"("page_type");

-- CreateIndex
CREATE UNIQUE INDEX "orders_order_id_key" ON "orders"("order_id");

-- CreateIndex
CREATE INDEX "orders_user_id_idx" ON "orders"("user_id");

-- CreateIndex
CREATE INDEX "orders_status_idx" ON "orders"("status");

-- CreateIndex
CREATE INDEX "order_accounts_order_id_idx" ON "order_accounts"("order_id");

-- CreateIndex
CREATE INDEX "payment_items_payment_id_idx" ON "payment_items"("payment_id");

-- CreateIndex
CREATE INDEX "popup_ad_views_view_date_idx" ON "popup_ad_views"("view_date");

-- CreateIndex
CREATE UNIQUE INDEX "site_settings_setting_key_key" ON "site_settings"("setting_key");

-- CreateIndex
CREATE UNIQUE INDEX "support_tickets_ticket_id_key" ON "support_tickets"("ticket_id");

-- CreateIndex
CREATE INDEX "support_tickets_user_id_idx" ON "support_tickets"("user_id");

-- CreateIndex
CREATE INDEX "support_tickets_status_idx" ON "support_tickets"("status");

-- CreateIndex
CREATE INDEX "ticket_replies_ticket_id_idx" ON "ticket_replies"("ticket_id");

-- CreateIndex
CREATE INDEX "user_activity_log_user_id_idx" ON "user_activity_log"("user_id");

-- CreateIndex
CREATE INDEX "user_status_history_user_id_idx" ON "user_status_history"("user_id");

-- AddForeignKey
ALTER TABLE "accounts" ADD CONSTRAINT "accounts_category_id_fkey" FOREIGN KEY ("category_id") REFERENCES "categories"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "account_features" ADD CONSTRAINT "account_features_account_id_fkey" FOREIGN KEY ("account_id") REFERENCES "accounts"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "account_images" ADD CONSTRAINT "account_images_account_id_fkey" FOREIGN KEY ("account_id") REFERENCES "accounts"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "account_stock" ADD CONSTRAINT "account_stock_account_id_fkey" FOREIGN KEY ("account_id") REFERENCES "accounts"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "active_sessions" ADD CONSTRAINT "active_sessions_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "crypto_payments" ADD CONSTRAINT "crypto_payments_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "orders" ADD CONSTRAINT "orders_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "order_accounts" ADD CONSTRAINT "order_accounts_order_id_fkey" FOREIGN KEY ("order_id") REFERENCES "orders"("order_id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "payment_items" ADD CONSTRAINT "payment_items_account_id_fkey" FOREIGN KEY ("account_id") REFERENCES "accounts"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "popup_ad_views" ADD CONSTRAINT "popup_ad_views_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "support_tickets" ADD CONSTRAINT "support_tickets_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "ticket_replies" ADD CONSTRAINT "ticket_replies_ticket_id_fkey" FOREIGN KEY ("ticket_id") REFERENCES "support_tickets"("ticket_id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "user_activity_log" ADD CONSTRAINT "user_activity_log_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "user_status_history" ADD CONSTRAINT "user_status_history_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE;
