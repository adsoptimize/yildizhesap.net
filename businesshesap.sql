-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 30 Tem 2025, 17:41:23
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `businesshesap`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `platform` varchar(50) NOT NULL,
  `account_type` varchar(100) NOT NULL,
  `limit_info` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `is_verified` tinyint(1) DEFAULT 0,
  `is_premium` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `warranty_days` int(11) DEFAULT 30,
  `delivery_type` enum('instant','manual') DEFAULT 'instant',
  `rating` decimal(2,1) DEFAULT 0.0,
  `views` int(11) DEFAULT 0,
  `sales_count` int(11) DEFAULT 0,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `accounts`
--

INSERT INTO `accounts` (`id`, `category_id`, `title`, `description`, `price`, `old_price`, `stock_quantity`, `platform`, `account_type`, `limit_info`, `status`, `is_verified`, `is_premium`, `is_featured`, `warranty_days`, `delivery_type`, `rating`, `views`, `sales_count`, `location`, `created_at`, `updated_at`) VALUES
(1, 1, 'Business Manager #001', 'Tam doğrulanmış Facebook Business Manager hesabı. Reklam verme yetkisi aktif, yüksek limit kapasitesi mevcut. Anında teslimat garantisi ile birlikte satın alabilirsiniz.', 5000.00, 6000.00, 12, 'Facebook', 'Business Manager', 'Yüksek Limit', 'active', 1, 1, 1, 30, 'instant', 4.9, 244, 0, 'Premium, Doğrulanmış', '2025-07-24 08:27:41', '2025-07-25 14:28:02'),
(2, 1, '250$ Limitli BM', 'Günlük 250$ harcama limitli Business Manager hesabı. Küçük ve orta ölçekli kampanyalar için idealdir.', 1000.00, NULL, 0, 'Facebook', 'Business Manager', '250$ Limit', 'active', 1, 0, 0, 30, 'instant', 4.5, 45, 0, 'Limitli, Güvenilir', '2025-07-24 08:27:41', '2025-07-24 11:32:40'),
(3, 2, 'Instagram 5K+', 'Organik takipçili Instagram hesabı. Yüksek etkileşim oranı ile işletmeniz için ideal.', 500.00, NULL, 3, 'Instagram', 'Business Account', 'Organik Takipçi', 'active', 0, 0, 1, 15, 'instant', 4.8, 87, 0, 'Organik, Takipçili', '2025-07-24 08:27:41', '2025-07-25 19:12:28'),
(4, 3, 'Onaylı Twitter', 'Mavi tik ile onaylanmış Twitter hesabı. Premium özellikler aktif.', 150.00, NULL, 2, 'Twitter', 'Verified Account', 'Mavi Tik', 'active', 1, 1, 0, 30, 'instant', 4.7, 44, 0, 'Mavi Tik, Premium', '2025-07-24 08:27:41', '2025-07-25 18:43:59'),
(5, 4, 'YouTube Premium', 'Reklamsız YouTube Premium hesabı. Müzik ve videolar sınırsız.', 80.00, NULL, 15, 'YouTube', 'Premium Account', 'Reklamsız', 'active', 0, 1, 0, 30, 'instant', 4.6, 22, 0, 'Premium, Reklamsız', '2025-07-24 08:27:41', '2025-07-24 10:35:10'),
(6, 5, 'TikTok Hesabı', 'Viral içerikler ile yüksek etkileşimli TikTok hesabı.', 300.00, NULL, 7, 'TikTok', 'Creator Account', 'Viral İçerik', 'active', 1, 0, 0, 20, 'instant', 4.5, 47, 0, 'Viral, Etkileşimli', '2025-07-24 08:27:41', '2025-07-24 11:43:26'),
(7, 5, 'TikTok Hesabı', 'Viral içerikler ile yüksek etkileşimli TikTok hesabı.', 300.00, NULL, 7, 'TikTok', 'Creator Account', 'Viral İçerik', 'active', 1, 0, 0, 20, 'instant', 4.5, 9, 0, 'Viral, Etkileşimli', '2025-07-24 08:27:41', '2025-07-25 18:51:07'),
(8, 5, 'TikTok Hesabı', 'Viral içerikler ile yüksek etkileşimli TikTok hesabı.', 300.00, NULL, 7, 'TikTok', 'Creator Account', 'Viral İçerik', 'active', 1, 0, 0, 20, 'instant', 4.5, 4, 0, 'Viral, Etkileşimli', '2025-07-24 08:27:41', '2025-07-24 10:41:06'),
(9, 5, 'TikTok Hesabı', 'Viral içerikler ile yüksek etkileşimli TikTok hesabı.', 300.00, NULL, 7, 'TikTok', 'Creator Account', 'Viral İçerik', 'active', 1, 0, 0, 20, 'instant', 4.5, 2, 0, 'Viral, Etkileşimli', '2025-07-24 08:27:41', '2025-07-24 08:38:25'),
(10, 5, 'TikTok Hesabı', 'Viral içerikler ile yüksek etkileşimli TikTok hesabı.', 300.00, NULL, 7, 'TikTok', 'Creator Account', 'Viral İçerik', 'active', 1, 0, 0, 20, 'instant', 4.5, 6, 0, 'Viral, Etkileşimli', '2025-07-24 08:27:41', '2025-07-24 09:01:37');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `account_features`
--

CREATE TABLE `account_features` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `feature_name` varchar(255) NOT NULL,
  `feature_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `account_features`
--

INSERT INTO `account_features` (`id`, `account_id`, `feature_name`, `feature_value`) VALUES
(1, 1, 'Platform', 'Facebook'),
(2, 1, 'Hesap Türü', 'Business Manager'),
(3, 1, 'Limit', 'Yüksek Limit'),
(4, 1, 'Durum', 'Aktif'),
(5, 1, 'Garanti', '30 Gün'),
(6, 1, 'Teslimat', 'Anında'),
(7, 2, 'Platform', 'Facebook'),
(8, 2, 'Hesap Türü', 'Business Manager'),
(9, 2, 'Limit', '250$ Günlük'),
(10, 2, 'Durum', 'Aktif'),
(11, 2, 'Garanti', '30 Gün'),
(12, 2, 'Teslimat', 'Anında');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `account_images`
--

CREATE TABLE `account_images` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `account_stock`
--

CREATE TABLE `account_stock` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `is_sold` tinyint(1) DEFAULT 0,
  `sold_to_user_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sold_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `account_stock`
--

INSERT INTO `account_stock` (`id`, `account_id`, `username`, `password`, `email`, `additional_info`, `is_sold`, `sold_to_user_id`, `order_id`, `created_at`, `sold_at`) VALUES
(1, 1, 'saasasd', 'asdasd', NULL, NULL, 0, NULL, NULL, '2025-07-25 12:35:56', NULL),
(2, 1, 'asdasd', 'asdasdas:asdasdas:Asdasdas', NULL, NULL, 0, NULL, NULL, '2025-07-25 12:36:03', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `active_sessions`
--

CREATE TABLE `active_sessions` (
  `id` int(11) NOT NULL,
  `session_token` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `device_fingerprint` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 2 hour),
  `is_active` tinyint(1) DEFAULT 1,
  `invalidated_by` enum('user','admin','security','expired') DEFAULT NULL,
  `invalidated_at` timestamp NULL DEFAULT NULL,
  `login_location` varchar(100) DEFAULT NULL,
  `device_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`device_info`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `active_sessions`
--

INSERT INTO `active_sessions` (`id`, `session_token`, `user_id`, `ip_address`, `user_agent`, `device_fingerprint`, `created_at`, `last_activity`, `expires_at`, `is_active`, `invalidated_by`, `invalidated_at`, `login_location`, `device_info`) VALUES
(1, 'e00ee9c7990cb90336644c5a45c468371b8e8859a454c3b563ab3ff0372675b7f7b4c62e3289185eb691ef98d91f4bae84284f71de2a2e6cb20d72a346696f4c', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '0b69b95e2dd45769f3f85af101219f4995059d2a1cf81e7f8e36539fa0f7c193', '2025-07-25 19:04:06', '2025-07-25 19:04:06', '2025-07-25 21:04:06', 1, NULL, NULL, 'Localhost', '{\"browser\":\"Chrome\",\"os\":\"Windows\",\"screen_resolution\":null,\"timezone\":null,\"language\":\"en-US,en;q=0.9\"}'),
(2, '251581b6c395f2996920a714d1ca0ad7973dc0878f1618931e8b45ecebe78c301b012bbedcdf54d03893ad123e469a830cac69028d0959ad5ed42ce7e4e32205', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'abe59dc79b0747581e9243ad54ae3294fe4c1076be3ae9ac0659664d07f0fe8e', '2025-07-25 19:08:27', '2025-07-25 19:08:27', '2025-07-25 21:08:27', 1, NULL, NULL, 'Localhost', '{\"browser\":\"Chrome\",\"os\":\"Windows\",\"screen_resolution\":null,\"timezone\":null,\"language\":\"en-US,en;q=0.9\"}'),
(3, 'd6e11469d65246778ebe1f5995bd323708e5ed250a5440521c3fe5985731760f5024feaa133e6aa9b2be2761af9ea26ffc70117f69ce1b182a9501d303f59baf', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'a978741ee26a1622b844a6ec60ab5ad47c9a19d867f8ab586e28b35749590a98', '2025-07-25 19:12:05', '2025-07-25 19:47:08', '2025-07-25 21:12:05', 1, NULL, NULL, 'Localhost', '{\"browser\":\"Chrome\",\"os\":\"Windows\",\"screen_resolution\":null,\"timezone\":null,\"language\":\"en-US,en;q=0.9\"}'),
(4, 'e3466cb78b3e078efe6c18113193551ca2c6fe2ff8f1bf4457d9de208e92db3828d2fa8f6c482fd040e3ce565015038bc86c4b1ba232db245b0af68411600e77', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '93867ba91997da61fa693b5f98186d6efbd6c64ffc41668305161ff48e7b2448', '2025-07-25 19:14:52', '2025-07-25 19:14:53', '2025-07-25 21:14:52', 1, NULL, NULL, 'Localhost', '{\"browser\":\"Chrome\",\"os\":\"Windows\",\"screen_resolution\":null,\"timezone\":null,\"language\":\"en-US,en;q=0.9\"}'),
(5, '4bc0b723ca987ecc2ba695ef96eaa24c252303dd6998c16f052b31976df299089e392f974be3da36264478d921cf51673a5fc07cd9517fbbf7ee2359dcd1627b', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '74fea32ba524ef141e6fc2c8f3844bb67bb6f93315b25756b5c029f0cde62d26', '2025-07-25 19:44:47', '2025-07-25 19:46:26', '2025-07-25 21:44:47', 1, NULL, NULL, 'Localhost', '{\"browser\":\"Chrome\",\"os\":\"Windows\",\"screen_resolution\":null,\"timezone\":null,\"language\":\"en-US,en;q=0.9\"}');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `created_at`) VALUES
(1, 'Facebook Business Manager', 'facebook-bm', 'fab fa-facebook-f', '2025-07-24 08:27:41'),
(2, 'Instagram Hesapları', 'instagram', 'fab fa-instagram', '2025-07-24 08:27:41'),
(3, 'Twitter Hesapları', 'twitter', 'fab fa-twitter', '2025-07-24 08:27:41'),
(4, 'YouTube Hesapları', 'youtube', 'fab fa-youtube', '2025-07-24 08:27:41'),
(5, 'TikTok Hesapları', 'tiktok', 'fab fa-tiktok', '2025-07-24 08:27:41'),
(6, 'Gmail Hesapları', 'gmail', 'fas fa-envelope', '2025-07-24 08:27:41');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `contact_settings`
--

CREATE TABLE `contact_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `contact_settings`
--

INSERT INTO `contact_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'page_title', 'İletişim', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(2, 'page_subtitle', 'Bizimle iletişime geçin', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(3, 'page_description', 'Sorularınız, önerileriniz veya destek talepleriniz için bize ulaşabilirsiniz. En kısa sürede size geri dönüş yapacağız.', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(4, 'whatsapp_number', '+905065455654', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(5, 'whatsapp_text', 'WhatsApp', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(6, 'whatsapp_description', 'Hızlı destek için WhatsApp üzerinden ulaşın', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(7, 'email_address', 'info@example.com', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(8, 'email_text', 'E-posta', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(9, 'email_description', 'E-posta ile iletişime geçin', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(10, 'telegram_username', 'exampleusername', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(11, 'telegram_text', 'Telegram', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(12, 'telegram_description', 'Telegram üzerinden mesaj gönderin', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(13, 'total_customers', '10,000+', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(14, 'response_time', '5 dk', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(15, 'satisfaction_rate', '98%', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(16, 'contact_form_title', 'Mesaj Gönder', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(17, 'contact_form_description', 'Aşağıdaki formu doldurarak bize mesaj gönderebilirsiniz.', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(18, 'office_hours_title', 'Çalışma Saatleri', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(19, 'office_hours_content', 'Pazartesi - Pazar: 09:00 - 23:00', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(20, 'support_title', 'Destek', '2025-07-25 02:02:08', '2025-07-25 02:02:08'),
(21, 'support_content', '7/24 canlı destek hizmeti sunuyoruz.', '2025-07-25 02:02:08', '2025-07-25 02:02:08');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `crypto_payments`
--

CREATE TABLE `crypto_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `cryptomus_uuid` varchar(36) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `network` varchar(20) DEFAULT NULL,
  `to_currency` varchar(10) DEFAULT NULL,
  `payment_amount` decimal(18,8) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `payment_url` text DEFAULT NULL,
  `status` enum('pending','paid','failed','expired','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` varchar(20) DEFAULT NULL,
  `txid` varchar(255) DEFAULT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `callback_data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `crypto_settings`
--

CREATE TABLE `crypto_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `crypto_settings`
--

INSERT INTO `crypto_settings` (`id`, `setting_key`, `setting_value`, `is_encrypted`, `description`, `updated_at`) VALUES
(1, 'cryptomus_merchant_uuid', '', 1, 'Cryptomus Merchant UUID (şifreli)', '2025-07-25 06:56:54'),
(2, 'cryptomus_payment_key', '', 1, 'Cryptomus Payment API Key (şifreli)', '2025-07-25 06:56:54'),
(3, 'cryptomus_payout_key', '', 1, 'Cryptomus Payout API Key (şifreli)', '2025-07-25 06:56:54'),
(4, 'cryptomus_webhook_secret', '', 1, 'Webhook doğrulama için secret key (şifreli)', '2025-07-25 06:56:54'),
(5, 'cryptomus_enabled', '0', 0, 'Cryptomus ödemeleri aktif mi? (1=aktif, 0=pasif)', '2025-07-25 06:56:54'),
(6, 'cryptomus_test_mode', '1', 0, 'Test modu aktif mi? (1=test, 0=canlı)', '2025-07-25 06:56:54'),
(7, 'default_currency', 'USD', 0, 'Varsayılan para birimi', '2025-07-25 06:56:54'),
(8, 'supported_networks', 'BTC,ETH,TRON,LTC', 0, 'Desteklenen ağlar (virgülle ayrılmış)', '2025-07-25 06:56:54');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `crypto_webhook_logs`
--

CREATE TABLE `crypto_webhook_logs` (
  `id` int(11) NOT NULL,
  `order_id` varchar(100) DEFAULT NULL,
  `cryptomus_uuid` varchar(36) DEFAULT NULL,
  `webhook_data` text NOT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `status` enum('valid','invalid','processed','error') NOT NULL DEFAULT 'valid',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question` varchar(500) NOT NULL,
  `answer` text NOT NULL,
  `order_index` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `faqs`
--

INSERT INTO `faqs` (`id`, `question`, `answer`, `order_index`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Hesaplar ne kadar sürede teslim ediliyor?', 'Tüm hesaplarımız satın alma işlemi tamamlandıktan sonra 5-15 dakika içerisinde otomatik olarak teslim edilmektedir. Bazı özel hesaplar için maksimum 1 saat sürebilir.', 1, 1, '2025-07-24 12:12:19', '2025-07-24 12:12:19'),
(2, 'Hesaplar güvenli mi? Yasaklanma riski var mı?', 'Tüm hesaplarımız orijinal metotlarla oluşturulmuş olup, telefon ve e-posta doğrulaması yapılmıştır. Yasaklanma riski minimum seviyededir. Ancak hesapları kurallara uygun kullanmanız önemlidir.', 2, 1, '2025-07-24 12:12:19', '2025-07-24 12:12:19'),
(3, 'Hesap bilgilerini nasıl alacağım?', 'Satın alma işlemi tamamlandıktan sonra hesap bilgileri (kullanıcı adı, şifre, e-posta vb.) otomatik olarak e-posta adresinize gönderilecektir. Ayrıca hesabınızdan da erişebilirsiniz.', 3, 1, '2025-07-24 12:12:19', '2025-07-24 12:12:19'),
(4, 'Para iadesi var mı?', 'Hesap tesliminden sonra 24 saat içerisinde hesapta sorun tespit edilirse %100 para iadesi yapılmaktadır. İade talebinizi destek ekibimize bildirmeniz yeterlidir.', 4, 1, '2025-07-24 12:12:19', '2025-07-24 12:12:19'),
(5, 'Hangi ödeme yöntemlerini kabul ediyorsunuz?', 'Kredi kartı, banka kartı, havale/EFT, Papara, Bitcoin ve diğer kripto paralar ile ödeme yapabilirsiniz. Tüm ödemeler SSL ile güvence altındadır.', 5, 1, '2025-07-24 12:12:19', '2025-07-24 12:12:19'),
(6, 'Hesaplar hangi ülkelerden?', 'Hesaplarımız çoğunlukla Türkiye, ABD, İngiltere, Almanya ve diğer Avrupa ülkelerinden oluşturulmuştur. Ürün açıklamasında hesabın ülke bilgisi belirtilmiştir.', 6, 1, '2025-07-24 12:12:19', '2025-07-24 12:12:19'),
(7, 'Toplu hesap alımında indirim var mı?', 'Evet! 10 ve üzeri hesap alımlarında %10, 50 ve üzeri alımlarda %20 indirim uygulanmaktadır. Toplu alım için destek ekibimizle iletişime geçebilirsiniz.', 7, 1, '2025-07-24 12:12:19', '2025-07-24 12:12:19'),
(8, '7/24 destek hizmeti var mı?', 'Evet, 7 gün 24 saat canlı destek hizmeti sunmaktayız. WhatsApp, Telegram ve canlı chat üzerinden bizlere ulaşabilirsiniz.', 8, 1, '2025-07-24 12:12:19', '2025-07-24 12:12:19'),
(9, 'Hesap değişimi yapılıyor mu?', 'Eğer teslim edilen hesapta teknik bir sorun varsa, 24 saat içerisinde ücretsiz hesap değişimi yapılmaktadır. Kullanıcı hatasından kaynaklı sorunlarda değişim yapılmaz.', 9, 1, '2025-07-24 12:12:19', '2025-07-24 12:12:19'),
(10, 'Hesapların yaşı ne kadar?', 'Hesaplarımız 1 ay ile 2 yıl arasında değişmektedir. Eski hesaplar daha güvenilir olup, fiyatları da buna göre belirlenmektedir. Hesap yaşı ürün açıklamasında belirtilir.', 10, 1, '2025-07-24 12:12:19', '2025-07-24 12:12:19');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ip_bans`
--

CREATE TABLE `ip_bans` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `reason` text NOT NULL,
  `ban_type` enum('permanent','temporary') DEFAULT 'temporary',
  `banned_until` timestamp NULL DEFAULT NULL,
  `banned_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ip_limit_resets`
--

CREATE TABLE `ip_limit_resets` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `reset_by` int(11) NOT NULL,
  `accounts_before_reset` int(11) DEFAULT 0,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `legal_pages`
--

CREATE TABLE `legal_pages` (
  `id` int(11) NOT NULL,
  `page_type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `meta_description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `legal_pages`
--

INSERT INTO `legal_pages` (`id`, `page_type`, `title`, `content`, `meta_description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'kvvk', 'KVKK AYDINLATMA METNİ', '<h3><strong>KİŞİSEL VERİLERİN KORUNMASI KANUNU (KVKK) AYDINLATMA METNİ</strong></h3><p><strong>[Firma Adı / Web Sitesi Adresi]</strong> olarak, kullanıcılarımızın gizliliğine ve kişisel verilerinin güvenliğine en üst düzeyde önem veriyoruz. 6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”) uyarınca, kişisel verilerinizin hangi amaçlarla işlendiğini, kimlerle paylaşıldığını ve haklarınızı içeren bu aydınlatma metnini bilgilerinize sunuyoruz.</p><h3>1. <strong>Veri Sorumlusu</strong></h3><p>Kişisel verileriniz, veri sorumlusu sıfatıyla <strong>[Firma Adı / www.siteadresi.com]</strong> tarafından KVKK\'ya uygun şekilde işlenmektedir.</p><h3>2. <strong>İşlenen Kişisel Veriler</strong></h3><p>Sitemiz üzerinden sunulan hizmetler kapsamında, aşağıdaki kişisel verileriniz toplanabilir:</p><p>Ad, soyad, kullanıcı adı</p><p>E-posta adresi, telefon numarası</p><p>IP adresi, işlem geçmişi, cihaz bilgileri</p><p>Ödeme/İşlem bilgileri (kart bilgileri saklanmaz)</p><p>Giriş-çıkış zamanları, destek talepleri</p><h3>3. <strong>Kişisel Verilerin İşlenme Amaçları</strong></h3><p>Toplanan kişisel verileriniz;</p><p>Hizmetlerin sunulması ve kullanıcı hesaplarının yönetimi,</p><p>Satış işlemlerinin gerçekleştirilmesi,</p><p>Müşteri destek süreçlerinin yürütülmesi,</p><p>Yasal yükümlülüklerin yerine getirilmesi,</p><p>Güvenlik önlemlerinin alınması,</p><p>Kullanıcı deneyiminin iyileştirilmesi</p><p>amaçlarıyla, KVKK’nın 5. ve 6. maddelerinde belirtilen şartlara uygun olarak işlenmektedir.</p><h3>4. <strong>Kişisel Verilerin Aktarımı</strong></h3><p>Kişisel verileriniz, yukarıda belirtilen amaçlarla sınırlı olmak kaydıyla;</p><p>İlgili hizmet sağlayıcı firmalar (ödeme sistemleri, barındırma hizmetleri vb.)</p><p>Hukuken yetkili kamu kurum ve kuruluşları</p><p>Hukuki danışmanlar ve teknik destek hizmeti veren üçüncü taraflarla</p><p>paylaşılabilir. Üçüncü taraflarla yapılan tüm paylaşımlar, veri güvenliği sözleşmeleri ile koruma altına alınmıştır.</p><h3>5. <strong>Veri Toplama Yöntemleri ve Hukuki Sebepler</strong></h3><p>Verileriniz; web sitemiz üzerinden üyelik oluşturmanız, hizmet satın almanız, destek talebi oluşturmanız veya bizimle iletişime geçmeniz yoluyla elektronik ortamda toplanmaktadır.</p><p>Hukuki sebepler şunlardır:</p><p>Bir sözleşmenin kurulması veya ifasıyla doğrudan ilgili olması</p><p>Hukuki yükümlülüklerin yerine getirilmesi</p><p>Açık rızanızın bulunması (gereken durumlarda)</p><p>Meşru menfaatlerimizin korunması</p><h3>6. <strong>Kişisel Veri Sahibi Olarak Haklarınız</strong></h3><p>KVKK’nın 11. maddesi uyarınca, bize başvurarak aşağıdaki haklarınızı kullanabilirsiniz:</p><p>Kişisel verinizin işlenip işlenmediğini öğrenme</p><p>Kişisel veriniz işlenmişse buna ilişkin bilgi talep etme</p><p>İşleme amacını ve bu verilerin amacına uygun kullanılıp kullanılmadığını öğrenme</p><p>Yurt içinde veya yurt dışında aktarıldığı üçüncü kişileri bilme</p><p>Eksik veya yanlış işlenmişse düzeltilmesini isteme</p><p>KVKK’ya uygun olarak silinmesini veya yok edilmesini talep etme</p><p>İşlemenin hukuka aykırı olması halinde zararın giderilmesini isteme</p><p>Bu haklarınızı, [e-posta adresi] veya [iletişim formu/posta adresi] üzerinden bize ulaşarak kullanabilirsiniz.</p><h3>7. <strong>Veri Güvenliği</strong></h3><p>Kişisel verilerinizin güvenliği bizim için önceliklidir. Bu nedenle, yetkisiz erişimi önlemek ve bilgilerinizi korumak için teknik ve idari güvenlik önlemleri almaktayız.</p><h3>8. <strong>İletişim</strong></h3><p>Kişisel verilerinizle ilgili her türlü soru, görüş ve talepleriniz için bizimle aşağıdaki kanallar üzerinden iletişime geçebilirsiniz:</p><p>📧 E-posta: [destek@siteadresi.com]<br>📞 Telefon: [0 (XXX) XXX XX XX]<br>📍 Adres: [Firma Adresi (varsa)]</p>', 'KVKK Aydınlatma metni - Kişisel verilerinizin korunması hakkında aydınlatma metni.', 1, '2025-07-25 14:32:18', '2025-07-25 15:10:44'),
(2, 'privacy', 'Gizlilik PolitikasÄ±', '<h2><strong>Gizlilik Politikası</strong></h2><p><strong>[Site Adı / Firma Adı]</strong> olarak, kullanıcılarımızın kişisel bilgilerinin gizliliğini ve güvenliğini en öncelikli değerlerimizden biri olarak görüyoruz. Bu gizlilik politikası, web sitemizi ziyaret eden ya da hizmetlerimizden faydalanan kullanıcıların bilgilerinin nasıl toplandığını, kullanıldığını, korunduğunu ve paylaşıldığını açıklamak amacıyla hazırlanmıştır.</p><h3>1. <strong>Topladığımız Bilgiler</strong></h3><p>Sitemizi kullandığınızda veya hizmetlerimizden faydalandığınızda sizden bazı kişisel bilgiler talep edebiliriz. Bunlar aşağıdakileri içerebilir:</p><p>Ad, soyad, kullanıcı adı</p><p>E-posta adresi, telefon numarası</p><p>IP adresi, cihaz bilgileri, tarayıcı türü</p><p>Satın alma işlemleriyle ilgili bilgiler (ödeme sağlayıcılar aracılığıyla)</p><p>Destek talepleriniz, mesajlarınız</p><p>Kredi kartı bilgileri hiçbir şekilde tarafımızca saklanmaz. Tüm ödeme işlemleri güvenli ödeme altyapıları üzerinden gerçekleştirilir.</p><h3>2. <strong>Bilgilerin Kullanım Amaçları</strong></h3><p>Topladığımız kişisel veriler, aşağıdaki amaçlarla kullanılmaktadır:</p><p>Hizmetlerin sağlanması ve satın alma işlemlerinin tamamlanması</p><p>Kullanıcı hesabınızın oluşturulması ve yönetilmesi</p><p>Müşteri destek süreçlerinin yürütülmesi</p><p>Hizmet kalitesinin artırılması ve kullanıcı deneyiminin iyileştirilmesi</p><p>Güvenlik, dolandırıcılık önleme ve yasal yükümlülüklerin yerine getirilmesi</p><h3>3. <strong>Bilgilerin Paylaşımı</strong></h3><p>Kişisel bilgileriniz, açık rızanız olmaksızın üçüncü kişilerle paylaşılmaz. Ancak aşağıdaki durumlarda bazı bilgilerin paylaşılması gerekebilir:</p><p>Yasal zorunluluklar doğrultusunda resmi mercilerle</p><p>Ödeme altyapısı ve sunucu hizmeti sağlayan güvenilir üçüncü taraflarla</p><p>Hukuki danışmanlık ve teknik destek amaçlı sınırlı hizmet sağlayıcılarla</p><p>Tüm üçüncü taraf hizmet sağlayıcılarla, veri güvenliği sözleşmeleri yapılmakta ve bilgileriniz yalnızca belirtilen amaçlarla kullanılmaktadır.</p><h3>4. <strong>Çerezler (Cookies)</strong></h3><p>Web sitemiz, kullanıcı deneyimini geliştirmek ve bazı teknik işlemleri gerçekleştirebilmek adına çerezlerden faydalanır. Çerezleri dilediğiniz zaman tarayıcı ayarlarınızdan silebilir veya engelleyebilirsiniz. Ancak bazı çerezlerin devre dışı bırakılması, site işlevlerinde aksamalara neden olabilir.</p><h3>5. <strong>Veri Güvenliği</strong></h3><p>Kişisel verilerinizin güvenliği için hem teknik hem de idari tedbirler almaktayız. Tüm veri iletimleri SSL (Secure Socket Layer) protokolü ile şifrelenmekte, sunucularımız düzenli olarak güvenlik testlerinden geçirilmektedir. Yetkisiz erişimlere karşı önlemler alınmaktadır.</p><h3>6. <strong>Kullanıcı Hakları</strong></h3><p>Kullanıcı olarak, KVKK kapsamında;</p><p>Hakkınızda hangi verilerin toplandığını öğrenme,</p><p>Bu verilerin hangi amaçla kullanıldığını öğrenme,</p><p>Yanlış ya da eksik bilgilerin düzeltilmesini isteme,</p><p>Verilerin silinmesini veya anonim hale getirilmesini talep etme,</p><p>İşlemenin sınırlandırılmasını isteme</p><p>haklarına sahipsiniz. Bu haklarınızı kullanmak için bizimle [e-posta adresi] üzerinden iletişime geçebilirsiniz.</p><h3>7. <strong>Politika Değişiklikleri</strong></h3><p>Gizlilik politikamız, yasal değişiklikler veya hizmet kapsamımıza göre zaman zaman güncellenebilir. Güncellemeler web sitemizde yayımlandığı andan itibaren geçerli olur. Bu nedenle, politikamızı periyodik olarak gözden geçirmenizi öneririz.</p><h3>8. <strong>İletişim</strong></h3><p>Gizliliğinizle ilgili her türlü soru, görüş veya talebiniz için bizimle iletişime geçebilirsiniz:</p><p>📧 <strong>E-posta:</strong> [destek@siteadresi.com]<br>📞 <strong>Telefon:</strong> [0 (XXX) XXX XX XX]<br>📍 <strong>Adres:</strong> [Firma fizikî adresi (varsa)]</p><p><strong>[Firma Adı / Site Adresi] olarak sizlerin güvenine layık olmak için tüm süreçlerimizi şeffaflık ve yasal sorumluluk çerçevesinde yürütmeye devam ediyoruz.</strong></p>', 'Gizlilik PolitikasÄ± - KiÅŸisel bilgilerinizin nasÄ±l korunduÄŸu hakkÄ±nda detaylÄ± bilgiler', 1, '2025-07-25 14:32:18', '2025-07-25 15:11:40'),
(3, 'terms', 'Kullanım Şartları', '<h2><strong>KULLANIM ŞARTLARI</strong></h2><p>Lütfen <strong>[Site Adı - www.siteadresi.com]</strong> adresinden hizmet almadan önce bu <strong>Kullanım Şartları</strong> metnini dikkatlice okuyunuz. Bu siteyi kullanan her kullanıcı, aşağıda belirtilen şartları kabul etmiş sayılır.</p><h3>1. <strong>Taraflar ve Tanımlar</strong></h3><p>Bu metin; <strong>[Firma Adı]</strong> (“Hizmet Sağlayıcı”) ile siteyi ziyaret eden veya hizmet alan kişi (“Kullanıcı”) arasında dijital ortamda kabul edilmiş bir sözleşmedir.</p><p>“Site”: www.siteadresi.com alan adı ve alt sayfalarından oluşan web sitesini ifade eder.</p><p>“Hesap”: Satışı yapılan dijital içerik veya erişim ürününü ifade eder.</p><h3>2. <strong>Hizmetin Konusu</strong></h3><p>Site, belirli dijital içeriklerin veya hesapların, kullanım koşulları çerçevesinde kullanıcıya sunulmasını sağlar. Sitede yer alan hiçbir içerik, <strong>resmî iş ortaklığı</strong> veya <strong>yetkili bayi</strong> ilişkisi anlamına gelmez; ürünler üçüncü taraf platformlara ait olabilir ve bu platformların kendi kullanım şartlarına tabidir.</p><h3>3. <strong>Kayıt ve Üyelik</strong></h3><p>Üyelik işlemleri sırasında beyan edilen tüm bilgilerin doğru, eksiksiz ve güncel olması gerekmektedir.</p><p>Kullanıcı, hesabını üçüncü kişilere devredemez ya da başkasının adına işlem yapamaz.</p><p>Sistemde tespit edilen sahte, yanıltıcı veya yasa dışı bilgiler nedeniyle doğabilecek tüm sorumluluk kullanıcıya aittir.</p><h3>4. <strong>Kullanıcı Yükümlülükleri</strong></h3><p>Kullanıcı;</p><p>Sitenin sunduğu hizmetleri yalnızca yasal amaçlarla kullanacağını,</p><p>Hiçbir şekilde siteye zarar verici, sunucuları zorlayıcı ya da diğer kullanıcıları mağdur edici eylemlerde bulunmayacağını,</p><p>Satın aldığı dijital içeriği yalnızca kendi adına ve kendi kullanımı için kullanacağını,</p><p>kabul ve taahhüt eder.</p><h3>5. <strong>Satın Alma ve Teslimat</strong></h3><p>Satın alınan ürünler (hesap bilgisi, lisans kodu vb.), sistem tarafından tanımlanan şekilde kullanıcıya teslim edilir.</p><p>Teslimat, e-posta veya kullanıcı paneli üzerinden sağlanabilir.</p><p>Dijital içeriklerde “iade” veya “iptal” hakkı, içeriğin niteliğine ve sunum şekline göre sınırlı olabilir. Kullanıcı, sipariş vermeden önce ürün açıklamalarını dikkatlice okumakla yükümlüdür.</p><h3>6. <strong>Fikri Mülkiyet Hakları</strong></h3><p>Sitede yer alan tüm içerik (metin, görsel, logo, yazılım vs.) <strong>[Firma Adı]</strong>’na aittir veya kullanım hakkı alınmıştır. İzinsiz kopyalanması, çoğaltılması veya başka platformlarda paylaşılması yasaktır.</p><h3>7. <strong>Hizmette Değişiklik ve Erişim</strong></h3><p><strong>[Firma Adı]</strong>, sunduğu hizmetlerde, fiyatlarda veya sistemde dilediği zaman değişiklik yapma hakkını saklı tutar. Bu değişiklikler, yayım anında geçerli olur. Siteye erişim zaman zaman bakım, güncelleme veya teknik nedenlerle kısıtlanabilir.</p><h3>8. <strong>Sorumluluğun Sınırlandırılması</strong></h3><p>Site, sunulan hizmetlerin sürekli, kesintisiz ve hatasız olacağını garanti etmez.</p><p>Üçüncü taraf platformlardan kaynaklanan erişim engeli, hesap kapatma veya teknik sorunlardan dolayı <strong>[Firma Adı]</strong> sorumlu tutulamaz.</p><p>Kullanıcı, bu riskleri kabul ederek hizmet almaktadır.</p><h3>9. <strong>Veri Güvenliği ve Gizlilik</strong></h3><p>Kullanıcının kişisel verileri, <strong>KVKK</strong> kapsamında korunmakta ve yalnızca Aydınlatma Metni’nde belirtilen amaçlarla işlenmektedir. Detaylı bilgi için <strong>[Gizlilik Politikası]</strong> sayfasını inceleyebilirsiniz.</p><h3>10. <strong>Uygulanacak Hukuk ve Yetki</strong></h3><p>Bu kullanım şartları Türkiye Cumhuriyeti yasalarına tabidir. Taraflar arasında doğabilecek ihtilaflarda, <strong>[Firma merkezinin bulunduğu il/ilçe]</strong> mahkemeleri ve icra daireleri yetkilidir.</p><h3>11. <strong>Yürürlük ve Kabul</strong></h3><p>Bu sayfada yer alan şartlar, siteye erişim sağlayan herkes için geçerli kabul edilir. Kullanıcı, siteyi kullanmakla birlikte tüm koşulları okumuş, anlamış ve kabul etmiş sayılır.</p><p>📅 <strong>Son Güncelleme Tarihi:</strong> [Tarih]</p><p>📧 <strong>İletişim:</strong> [destek@siteadresi.com]<br>📍 <strong>Adres:</strong> [Firma adresi (varsa)]</p>', 'Kullanım Şartları ile ilgili bilgilendirme.', 1, '2025-07-25 15:12:30', '2025-07-25 15:12:30'),
(4, 'cookies', 'Çerez Politikası', '<h2><strong>Çerez Politikası</strong></h2><p>Bu Çerez Politikası, <strong>[Site Adı – www.siteadresi.com]</strong> web sitesi üzerinden sunulan hizmetlerde kullanılan çerezlerin türlerini, kullanım amaçlarını ve kullanıcıların tercih haklarını açıklamaktadır.</p><p>Web sitemizi kullanarak, çerezlerin bu politika doğrultusunda kullanılmasını kabul etmiş sayılırsınız.</p><h3>1. <strong>Çerez Nedir?</strong></h3><p>Çerezler (cookies), bir web sitesini ziyaret ettiğinizde tarayıcınız aracılığıyla cihazınıza kaydedilen küçük veri dosyalarıdır. Bu dosyalar, site kullanımınızı kolaylaştırmak, tercihlerinizi hatırlamak ve size daha iyi bir kullanıcı deneyimi sunmak amacıyla kullanılır.</p><h3>2. <strong>Çerez Türleri</strong></h3><p>Web sitemizde kullanılan çerezler aşağıdaki kategorilere ayrılmaktadır:</p><h4>a) <strong>Zorunlu (Temel) Çerezler</strong></h4><p>Sitenin doğru bir şekilde çalışması için gereklidir. Oturum açma, sepet işlemleri gibi temel işlevleri sağlar.</p><h4>b) <strong>İşlevsel Çerezler</strong></h4><p>Kullanıcının tercihlerini (dil, bölge, tema vb.) hatırlamak için kullanılır. Kullanıcı deneyimini kişiselleştirir.</p><h4>c) <strong>Analitik ve Performans Çerezleri</strong></h4><p>Ziyaretçilerin siteyi nasıl kullandığını anlamamıza yardımcı olur. Bu veriler tamamen anonimdir ve istatistiksel amaçlarla kullanılır.</p><h4>d) <strong>Reklam ve Hedefleme Çerezleri</strong></h4><p>Üçüncü taraf reklam sağlayıcılar tarafından yerleştirilebilir. İlgi alanlarınıza uygun reklam gösterimini destekler.</p><h3>3. <strong>Çerezlerin Kullanım Amaçları</strong></h3><p>Çerezler şu amaçlarla kullanılmaktadır:</p><p>Site trafiğini analiz etmek ve performansı artırmak</p><p>Kullanıcı tercihlerini hatırlamak</p><p>Geliştirilmiş içerik sunmak</p><p>İlgi alanlarınıza göre reklam/pazarlama faaliyetlerini yürütmek</p><h3>4. <strong>Çerezleri Yönetme ve Reddetme</strong></h3><p>Tarayıcı ayarlarınızı kullanarak çerezleri kontrol edebilir, silebilir veya engelleyebilirsiniz. Ancak bazı çerezleri devre dışı bırakmanız, sitenin bazı işlevlerinin düzgün çalışmamasına neden olabilir.</p><p>Tarayıcı bazlı ayarlar için rehber bağlantılar:</p><p><strong>Google Chrome:</strong> chrome://settings/cookies</p><p><strong>Mozilla Firefox:</strong> about:preferences#privacy</p><p><strong>Safari:</strong> Ayarlar &gt; Gizlilik &gt; Çerezleri Yönet</p><p><strong>Microsoft Edge:</strong> Ayarlar &gt; Çerezler ve site izinleri</p><h3>5. <strong>Üçüncü Taraf Çerezleri</strong></h3><p>Sitemiz, Google Analytics gibi hizmet sağlayıcıların çerezlerini kullanabilir. Bu tür çerezler hakkında detaylı bilgi almak için ilgili üçüncü tarafların gizlilik ve çerez politikalarını incelemenizi tavsiye ederiz.</p><h3>6. <strong>KVKK Kapsamında Haklarınız</strong></h3><p>6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında;</p><p>Hangi verilerin toplandığını öğrenme</p><p>Verilerin işlenme amaçlarını öğrenme</p><p>Hatalı/verimsiz verilerin düzeltilmesini veya silinmesini isteme</p><p>İşleme faaliyetlerine itiraz etme gibi haklara sahipsiniz.</p><p>Bu haklarınızı kullanmak için bizimle [destek@siteadresi.com] adresinden iletişime geçebilirsiniz.</p><h3>7. <strong>Güncellemeler</strong></h3><p>Çerez politikamız, yasal düzenlemelere ve teknik gelişmelere göre zaman zaman güncellenebilir. Güncellemeler sitemizde yayımlanır ve yayımlandığı tarihten itibaren geçerli olur.</p><p>📅 <strong>Son Güncelleme Tarihi:</strong> [Güncel tarih]<br>📧 <strong>İletişim:</strong> [destek@siteadresi.com]</p><p><strong>[Firma Adı] olarak sizlere daha güvenli, verimli ve kişisel bir deneyim sunmak için çerezleri sadece yasal sınırlar çerçevesinde kullanıyoruz.</strong></p>', 'Çerez Politikamız hakkında bilgilendirme', 1, '2025-07-25 15:14:30', '2025-07-25 15:14:30'),
(5, 'refund', 'İade ve İptal Politikası', '<h2><strong>İade ve İptal Politikası</strong></h2><p>Bu İade ve İptal Politikası, <strong>[Site Adı – www.siteadresi.com]</strong> üzerinden sunulan dijital ürün ve hizmetlerin satın alma, iptal ve iade koşullarını düzenler. Siteyi kullanan her kullanıcı, aşağıda belirtilen şartları kabul etmiş sayılır.</p><h3>1. <strong>Dijital Ürün Niteliği ve İade Koşulları</strong></h3><p>Sitemiz üzerinden sunulan tüm ürünler <strong>dijital ürün</strong> niteliğindedir. Bu kapsamda, kullanıcıya <strong>elektronik ortamda anında teslim edilen</strong> ürünlerde, <strong>mesafeli satış sözleşmeleri yönetmeliği</strong> gereği <strong>cayma hakkı bulunmamaktadır.</strong></p><p>Ancak istisnai durumlarda aşağıdaki koşullarda iade talebi değerlendirilebilir:</p><p>Teslim edilen dijital içerik <strong>çalışmıyor</strong> veya <strong>eksik bilgi içeriyorsa</strong></p><p>Satın alınan ürün, açıklamada belirtilen özellikleri <strong>taşımıyorsa</strong></p><p>Ürün <strong>hiç teslim edilmemişse</strong> (sistemsel hata durumları)</p><p>Bu gibi durumlarda kullanıcı, teslimden itibaren <strong>24 saat içinde</strong> destek ekibimizle iletişime geçmelidir.</p><h3>2. <strong>İptal Politikası</strong></h3><p>Sipariş, <strong>ödeme tamamlanmadan önce</strong> iptal edilebilir. Ödeme tamamlandıktan sonra ürün otomatik olarak teslim edildiği için iptal mümkün değildir.</p><p>Ödeme sonrası yapılan iptal talepleri yalnızca teknik sorun veya sistemsel hata durumlarında incelenir.</p><h3>3. <strong>İade Süreci</strong></h3><p>İade talepleriniz aşağıdaki süreçten geçer:</p><p>Kullanıcı, <strong>[destek@siteadresi.com]</strong> adresine e-posta göndererek talebini bildirir.</p><p>Teknik ekibimiz, ürün teslimatı ve geçerliliğini sistem üzerinden kontrol eder.</p><p>İade gerekçesi uygun görülürse, ödeme <strong>3 ila 10 iş günü içinde</strong> aynı yöntemle iade edilir.</p><p>İade işlemi yalnızca ürün ilk sahibi olan kullanıcıya yapılır.</p><h3>4. <strong>Kabul Edilmeyen İade Durumları</strong></h3><p>Aşağıdaki durumlarda iade talepleri kabul edilmez:</p><p>Kullanıcı ürünü teslim aldıktan sonra <strong>kişisel sebeplerle</strong> vazgeçerse</p><p>Hesap bilgileri üçüncü kişilerle <strong>paylaşıldıysa veya suistimal edildiyse</strong></p><p>Ürün kullanım hatası nedeniyle erişilemez hale geldiyse</p><p>Ürünün iade edilemeyeceği, satın alma sırasında <strong>açıkça belirtildiyse</strong></p><h3>5. <strong>Destek ve İletişim</strong></h3><p>İade ve iptal süreçleriyle ilgili tüm sorularınız için bizimle iletişime geçebilirsiniz:</p><p>📧 <strong>E-posta:</strong> [destek@siteadresi.com]<br>📞 <strong>Telefon:</strong> [0 (XXX) XXX XX XX]<br>📍 <strong>Adres:</strong> [Varsa şirket fizikî adresi]</p><p>📅 <strong>Son Güncelleme Tarihi:</strong> [Tarih]</p><p><strong>[Firma Adı] olarak amacımız, size sorunsuz ve güvenilir bir dijital alışveriş deneyimi sunmaktır. Her zaman şeffaf, adil ve kullanıcı odaklı hareket etmeyi ilke ediniyoruz.</strong></p>', 'İade & İptal Politikamız hakkında bilgilendirme.', 1, '2025-07-25 15:15:19', '2025-07-25 15:15:19'),
(6, 'about', 'Hakkımızda', '<h2><strong>Hakkımızda</strong></h2><p><strong>[Site Adı – www.siteadresi.com]</strong> olarak dijital dünyanın hızla geliştiği günümüzde, kullanıcılarımızın <strong>güvenli, hızlı ve sorunsuz</strong> bir şekilde dijital ürün ve hesaplara ulaşabilmesini sağlamak amacıyla yola çıktık.</p><p>Kurulduğumuz günden bu yana önceliğimiz, <strong>müşteri memnuniyetini</strong> esas alarak şeffaf, dürüst ve kaliteli hizmet sunmak oldu. Sadece bir ürün satışı değil, aynı zamanda güven temelli bir alışveriş deneyimi sunmayı hedefliyoruz.</p><h3>💡 Ne Yapıyoruz?</h3><p>Platformumuzda; oyun, yazılım, uygulama ve diğer dijital servisler için çeşitli <strong>kullanıcı hesapları</strong>, lisanslar ve erişim ürünleri sunuyoruz. Tüm ürünler, sistemimiz tarafından doğrulanarak teslim edilir ve her işlem, kullanıcı hakları gözetilerek kayıt altına alınır.</p><h3>🛡️ Neden Biz?</h3><p>✅ <strong>%100 Güvenli Alışveriş</strong>: Tüm siparişleriniz sistemsel olarak izlenir ve güvenlik önlemleriyle korunur.</p><p>📞 <strong>7/24 Destek Hizmeti</strong>: Her zaman ulaşabileceğiniz bir destek ekibimiz var.</p><p>⏱️ <strong>Anında Teslimat</strong>: Çoğu ürün saniyeler içinde teslim edilir.</p><p>📃 <strong>Yasal Uyum</strong>: KVKK ve dijital ürün satışına dair tüm yasal sorumluluklara uygun çalışıyoruz.</p><h3>👥 Kime Hizmet Veriyoruz?</h3><p>Bireysel kullanıcılar başta olmak üzere, dijital içeriklere kolay, hızlı ve uygun maliyetle ulaşmak isteyen herkese hitap ediyoruz. Türkiye’nin dört bir yanından binlerce kullanıcıya dijital çözümler sunmaya devam ediyoruz.</p><h3>🎯 Vizyonumuz</h3><p>Dijital ürün ve hizmet satışında <strong>sektörün en güvenilir isimlerinden biri olmak</strong>, kullanıcıların aklına ilk gelen platform haline gelmektir.</p><h3>🌱 Misyonumuz</h3><p>Kullanıcılarımıza hızlı, pratik ve güvenli bir alışveriş ortamı sağlamak; tüm süreçlerde şeffaflık ve dürüstlük ilkesiyle hareket etmektir.</p><h3>📞 Bize Ulaşın</h3><p>Görüş, öneri ve her türlü destek talebiniz için bize ulaşabilirsiniz:<br>📧 <strong>E-posta:</strong> [destek@siteadresi.com]<br>📍 <strong>Adres:</strong> [Varsa fiziksel adres]<br>📱 <strong>Sosyal Medya:</strong> [@kullaniciadiniz]</p><p><strong>[Site Adı]</strong> ailesi olarak sizlere sadece ürün değil; güven, hız ve kalite sunmaya devam ediyoruz.<br><strong>Bizi tercih ettiğiniz için teşekkür ederiz.</strong></p>', '', 1, '2025-07-25 15:16:08', '2025-07-25 15:16:08');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `delivery_status` enum('pending','partial','delivered','failed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `orders`
--

INSERT INTO `orders` (`id`, `order_id`, `user_id`, `product_name`, `category`, `quantity`, `unit_price`, `total_price`, `order_date`, `status`, `delivery_status`, `created_at`, `updated_at`) VALUES
(1, 'SP001', 1, 'Instagram Business Hesapları', 'Instagram', 5, 12.50, 62.50, '2024-01-15 14:30:00', 'completed', 'delivered', '2025-07-25 03:16:05', '2025-07-25 03:16:05'),
(2, 'SP002', 1, 'TikTok Creator Hesapları', 'TikTok', 3, 8.99, 26.97, '2024-01-10 09:15:00', 'completed', 'delivered', '2025-07-25 03:16:05', '2025-07-25 03:16:05'),
(3, 'SP003', 1, 'YouTube Premium Hesapları', 'YouTube', 2, 45.00, 90.00, '2024-01-08 16:45:00', 'completed', 'delivered', '2025-07-25 03:16:05', '2025-07-25 13:12:47'),
(4, 'SP004', 1, 'Facebook Business Manager', 'Facebook', 3, 125.00, 375.00, '2024-01-05 11:20:00', 'completed', 'delivered', '2025-07-25 03:16:05', '2025-07-25 03:16:05'),
(5, 'SP005', 2, 'Instagram Influencer Hesapları', 'Instagram', 10, 15.00, 150.00, '2024-01-12 10:00:00', 'completed', 'delivered', '2025-07-25 03:16:05', '2025-07-25 03:16:05'),
(6, 'SP006', 3, 'TikTok Business Hesapları', 'TikTok', 7, 12.99, 90.93, '2024-01-14 15:30:00', 'completed', 'delivered', '2025-07-25 03:16:05', '2025-07-25 13:12:39');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `order_accounts`
--

CREATE TABLE `order_accounts` (
  `id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_password` varchar(255) NOT NULL,
  `totp_secret` varchar(255) DEFAULT NULL,
  `account_created_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `order_accounts`
--

INSERT INTO `order_accounts` (`id`, `order_id`, `username`, `password`, `email`, `email_password`, `totp_secret`, `account_created_date`, `is_active`, `created_at`) VALUES
(1, 'SP001', 'lte37c38', 'hsbrLkYt', 'danakidmanngdda3951@gmx.com', 'EmailPass123', 'JBSWY3DPEHPK3PXP', '2024-01-15', 1, '2025-07-25 03:16:05'),
(2, 'SP001', 'sunshined2003', 'ErL6yPZ3', 'francegadsdenzcexmqzx5159@gmx.cn', 'EmailPass456', 'HXDMVJECJJWSRB3HWIZR4IFUGFTMXBOZ', '2024-01-15', 1, '2025-07-25 03:16:05'),
(3, 'SP001', 'ethylf1994', 'skdGFQHj', 'margaretwadhamdwnt7619@gmx.ru', 'EmailPass789', 'MFRGG43FMJSSA3DMNA4XIMDBMNQWKZDR', '2024-01-15', 1, '2025-07-25 03:16:05'),
(4, 'SP001', 'talishaj1987513', 'QWo1Wtck', 'tyishathurstonhzpbjg9494@gmx.es', 'EmailPass101', 'NF2W433JNVQXK3DJNZQXIYLUNFXHG3LF', '2024-01-15', 1, '2025-07-25 03:16:05'),
(5, 'SP001', 'contessay107', 'jQTri0V8', 'loraleekibblewhitetfhjp6761@gmx.co.in', 'EmailPass202', 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '2024-01-15', 1, '2025-07-25 03:16:05'),
(6, 'SP002', 'fledae_y43', 'pm3aMFDk', 'olimpiadriffieldue5472@gmx.pt', 'TikPass321', 'MFZXG3DJORQXG3RANBSXG3RANBSXG3RA', '2024-01-10', 1, '2025-07-25 03:16:05'),
(7, 'SP002', 'eskinshm73', 'hvaZ5Lnj', 'jeanetteferrinsgm8974@gmx.li', 'TikPass654', 'NFZW45DFNR2W4Y3UNFZW45DFNR2W4Y3U', '2024-01-10', 1, '2025-07-25 03:16:05'),
(8, 'SP002', 'allenar393', 'JSPjj50w', 'billyeamordejduxc5324@gmx.ru', 'TikPass987', 'OJZW45DFNRUW45DFOJZW45DFNRUW45DF', '2024-01-10', 1, '2025-07-25 03:16:05'),
(9, 'SP003', 'umneyk_72', 'iHZdaG3', 'robbinrichk3182@gmx.es', 'YtPass111', 'KFZG64LVMR2W4ZTCKFZG64LVMR2W4ZTC', '2024-01-08', 1, '2025-07-25 03:16:05'),
(10, 'SP003', 'gaming_pro23', 'Kf9mXz2L', 'proaccount23@gmail.com', 'YtPass222', 'MFZWS3LCNRUW4Y3DMFZWS3LCNRUW4Y3D', '2024-01-08', 1, '2025-07-25 03:16:05'),
(11, 'SP004', 'business_manager1', 'BmPass123', 'manager1@business.com', 'FbPass333', 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJR', '2024-01-05', 1, '2025-07-25 03:16:05'),
(12, 'SP004', 'business_manager2', 'BmPass456', 'manager2@business.com', 'FbPass444', 'MFRGG43FMJSSA4DMNA4XIMDBMNQWKZDS', '2024-01-05', 1, '2025-07-25 03:16:05'),
(13, 'SP004', 'business_manager3', 'BmPass789', 'manager3@business.com', 'FbPass555', 'NFZW45DFNR2W4Y3UNFZW45DFNR2W4Y3V', '2024-01-05', 1, '2025-07-25 03:16:05'),
(14, 'SP001', 'lte37c38', 'hsbrLkYt', 'danakidmanngdda3951@gmx.com', 'EmailPass123', 'JBSWY3DPEHPK3PXP', '2024-01-15', 1, '2025-07-25 03:18:22'),
(15, 'SP001', 'sunshined2003', 'ErL6yPZ3', 'francegadsdenzcexmqzx5159@gmx.cn', 'EmailPass456', 'HXDMVJECJJWSRB3HWIZR4IFUGFTMXBOZ', '2024-01-15', 1, '2025-07-25 03:18:22'),
(16, 'SP001', 'ethylf1994', 'skdGFQHj', 'margaretwadhamdwnt7619@gmx.ru', 'EmailPass789', 'MFRGG43FMJSSA3DMNA4XIMDBMNQWKZDR', '2024-01-15', 1, '2025-07-25 03:18:22'),
(17, 'SP001', 'talishaj1987513', 'QWo1Wtck', 'tyishathurstonhzpbjg9494@gmx.es', 'EmailPass101', 'NF2W433JNVQXK3DJNZQXIYLUNFXHG3LF', '2024-01-15', 1, '2025-07-25 03:18:22'),
(18, 'SP001', 'contessay107', 'jQTri0V8', 'loraleekibblewhitetfhjp6761@gmx.co.in', 'EmailPass202', 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '2024-01-15', 1, '2025-07-25 03:18:22'),
(19, 'SP002', 'fledae_y43', 'pm3aMFDk', 'olimpiadriffieldue5472@gmx.pt', 'TikPass321', 'MFZXG3DJORQXG3RANBSXG3RANBSXG3RA', '2024-01-10', 1, '2025-07-25 03:18:22'),
(20, 'SP002', 'eskinshm73', 'hvaZ5Lnj', 'jeanetteferrinsgm8974@gmx.li', 'TikPass654', 'NFZW45DFNR2W4Y3UNFZW45DFNR2W4Y3U', '2024-01-10', 1, '2025-07-25 03:18:22'),
(21, 'SP002', 'allenar393', 'JSPjj50w', 'billyeamordejduxc5324@gmx.ru', 'TikPass987', 'OJZW45DFNRUW45DFOJZW45DFNRUW45DF', '2024-01-10', 1, '2025-07-25 03:18:22'),
(22, 'SP003', 'umneyk_72', 'iHZdaG3', 'robbinrichk3182@gmx.es', 'YtPass111', 'KFZG64LVMR2W4ZTCKFZG64LVMR2W4ZTC', '2024-01-08', 1, '2025-07-25 03:18:22'),
(23, 'SP003', 'gaming_pro23', 'Kf9mXz2L', 'proaccount23@gmail.com', 'YtPass222', 'MFZWS3LCNRUW4Y3DMFZWS3LCNRUW4Y3D', '2024-01-08', 1, '2025-07-25 03:18:22'),
(24, 'SP004', 'business_manager1', 'BmPass123', 'manager1@business.com', 'FbPass333', 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJR', '2024-01-05', 1, '2025-07-25 03:18:22'),
(25, 'SP004', 'business_manager2', 'BmPass456', 'manager2@business.com', 'FbPass444', 'MFRGG43FMJSSA4DMNA4XIMDBMNQWKZDS', '2024-01-05', 1, '2025-07-25 03:18:22'),
(26, 'SP004', 'business_manager3', 'BmPass789', 'manager3@business.com', 'FbPass555', 'NFZW45DFNR2W4Y3UNFZW45DFNR2W4Y3V', '2024-01-05', 1, '2025-07-25 03:18:22');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payment_items`
--

CREATE TABLE `payment_items` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `popup_ad_views`
--

CREATE TABLE `popup_ad_views` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `view_date` date NOT NULL,
  `view_count` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `content_hash` varchar(64) DEFAULT '',
  `visitor_id` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `popup_ad_views`
--

INSERT INTO `popup_ad_views` (`id`, `ip_address`, `user_id`, `view_date`, `view_count`, `created_at`, `updated_at`, `content_hash`, `visitor_id`) VALUES
(40, '::1', 1, '2025-07-25', 1, '2025-07-25 15:53:30', '2025-07-25 15:53:30', '7f85d00d364e25f582e30c5afeabfad6120b8065e395d4673099c0b34dc7ccae', '1'),
(41, '::1', NULL, '2025-07-25', 1, '2025-07-25 16:03:08', '2025-07-25 16:03:08', '7f85d00d364e25f582e30c5afeabfad6120b8065e395d4673099c0b34dc7ccae', 'visitor_cf5572e2476ee06f9d4508d1cb7171ca_1753458055'),
(42, '::1', 8, '2025-07-25', 1, '2025-07-25 16:11:39', '2025-07-25 16:11:39', '7f85d00d364e25f582e30c5afeabfad6120b8065e395d4673099c0b34dc7ccae', '8'),
(43, '::1', NULL, '2025-07-25', 1, '2025-07-25 16:50:16', '2025-07-25 16:50:16', '7f85d00d364e25f582e30c5afeabfad6120b8065e395d4673099c0b34dc7ccae', 'visitor_4bd398278d529805f7b5044787b28e84_1753462216'),
(44, '::1', NULL, '2025-07-25', 1, '2025-07-25 18:43:20', '2025-07-25 18:43:20', '7f85d00d364e25f582e30c5afeabfad6120b8065e395d4673099c0b34dc7ccae', 'visitor_70bcdb3babe7048b682d0783c413e693_1753468999');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `order_index` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `category`, `order_index`, `created_at`, `updated_at`) VALUES
(1, 'site_title', 'BusinessHesap', NULL, NULL, 'header', 1, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(2, 'site_logo', 'images/logo.png', NULL, NULL, 'header', 2, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(3, 'header_menu', '[\r\n  {\r\n    \"name\": \"Home\",\r\n    \"url\": \"index.php\",\r\n    \"icon\": \"fas fa-home\"\r\n  },\r\n  {\r\n    \"name\": \"Accounts\",\r\n    \"url\": \"hesaplar.php\",\r\n    \"icon\": \"fas fa-shopping-cart\"\r\n  },\r\n  {\r\n    \"name\": \"Services\",\r\n    \"url\": \"hizmetler.php\",\r\n    \"icon\": \"fas fa-cogs\"\r\n  },\r\n  {\r\n    \"name\": \"Faq\",\r\n    \"url\": \"sss.php\",\r\n    \"icon\": \"fas fa-question-circle\"\r\n  },\r\n  {\r\n    \"name\": \"Contact\",\r\n    \"url\": \"iletisim.php\",\r\n    \"icon\": \"fas fa-phone-alt\"\r\n  }\r\n]', NULL, NULL, 'header', 3, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(4, 'hero_title', 'Premium Kalite Hesaplar', NULL, NULL, 'hero', 1, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(5, 'hero_subtitle', 'En Kaliteli Sosyal Medya Hesapları', NULL, NULL, 'hero', 2, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(6, 'hero_description', 'Premium kalitede hesaplar sadece burada!', NULL, NULL, 'hero', 3, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(7, 'hero_background', 'images/mockup.png', NULL, NULL, 'hero', 4, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(8, 'hero_button_text', 'Satın Al', NULL, NULL, 'hero', 5, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(9, 'hero_button_url', 'hesaplar.php', NULL, NULL, 'hero', 6, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(10, 'hero_stats', '[\r\n  {\r\n    \"number\": \"3000+\",\r\n    \"label\": \"Memnun Müşteri\"\r\n  },\r\n  {\r\n    \"number\": \"5000+\",\r\n    \"label\": \"Satılan Hesap\"\r\n  },\r\n  {\r\n    \"number\": \"24/7\",\r\n    \"label\": \"Destek\"\r\n  }\r\n]', NULL, NULL, 'hero', 7, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(11, 'footer_description', 'Güvenilir ve kaliteli sosyal medya hesapları.', NULL, NULL, 'footer', 1, '2025-07-25 10:16:51', '2025-07-25 11:44:07'),
(12, 'footer_copyright', '© 2025 BusinessHesap. Tüm hakları saklıdır.', NULL, NULL, 'footer', 2, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(13, 'footer_social', '[{\"platform\":\"Twitter\",\"url\":\"asdasdasdsadasd\",\"icon\":\"fab fa-twitter\"},{\"platform\":\"Instagram\",\"url\":\"#\",\"icon\":\"fab fa-instagram\"},{\"platform\":\"Telegram\",\"url\":\"#\",\"icon\":\"fab fa-telegram\"}]', NULL, NULL, 'footer', 3, '2025-07-25 10:16:51', '2025-07-25 11:44:07'),
(14, 'footer_links', '[{\"title\":\"Hızlı Linkler\",\"links\":[{\"name\":\"Ana Sayfa\",\"url\":\"index.php\"},{\"name\":\"Hesaplar\",\"url\":\"hesaplar.php\"},{\"name\":\"İletişim\",\"url\":\"iletisim.php\"}]}]', NULL, NULL, 'footer', 4, '2025-07-25 10:16:51', '2025-07-25 11:44:07'),
(15, 'contact_email', 'info@businesshesap.com', NULL, NULL, 'general', 1, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(16, 'contact_phone', '+90 555 333 33 33', NULL, NULL, 'general', 2, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(17, 'contact_whatsapp', '+90 555 333 33 33', NULL, NULL, 'general', 3, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(18, 'contact_telegram', '@businesshesap', NULL, NULL, 'general', 4, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(19, 'theme_primary_color', '#dc143c', NULL, NULL, 'theme', 1, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(20, 'theme_accent_color', '#ffd700', NULL, NULL, 'theme', 2, '2025-07-25 10:16:51', '2025-07-25 16:11:35'),
(164, 'logo_type', 'text', NULL, NULL, 'general', 0, '2025-07-25 10:43:14', '2025-07-25 16:11:35'),
(165, 'logo_width', '200', NULL, NULL, 'general', 0, '2025-07-25 10:43:14', '2025-07-25 16:11:35'),
(166, 'logo_height', '100', NULL, NULL, 'general', 0, '2025-07-25 10:43:14', '2025-07-25 16:11:35'),
(167, 'logo_icon', 'fas fa-users', NULL, NULL, 'general', 0, '2025-07-25 10:43:14', '2025-07-25 16:11:35'),
(168, 'logo_subtext', 'Facebook Businnes Accounts', NULL, NULL, 'general', 0, '2025-07-25 10:43:14', '2025-07-25 16:11:35'),
(634, 'favicon_ico', '/favicon.ico', 'text', 'ICO formatında favicon (16x16, 32x32)', 'general', 10, '2025-07-25 11:11:01', '2025-07-25 16:11:35'),
(635, 'favicon_png_16', '/assets/icons/favicon-16x16.png', 'text', 'PNG favicon 16x16', 'general', 11, '2025-07-25 11:11:01', '2025-07-25 16:11:35'),
(636, 'favicon_png_32', '/assets/icons/favicon-32x32.png', 'text', 'PNG favicon 32x32', 'general', 12, '2025-07-25 11:11:01', '2025-07-25 16:11:35'),
(637, 'apple_touch_icon', '/assets/icons/apple-touch-icon.png', 'text', 'Apple Touch Icon (180x180)', 'general', 13, '2025-07-25 11:11:01', '2025-07-25 16:11:35'),
(638, 'android_chrome_192', '/assets/icons/android-chrome-192x192.png', 'text', 'Android Chrome Icon (192x192)', 'general', 14, '2025-07-25 11:11:01', '2025-07-25 16:11:35'),
(639, 'android_chrome_512', '/assets/icons/android-chrome-512x512.png', 'text', 'Android Chrome Icon (512x512)', 'general', 15, '2025-07-25 11:11:01', '2025-07-25 16:11:35'),
(640, 'manifest_json', '/site.webmanifest', 'text', 'Web App Manifest dosyası', 'general', 16, '2025-07-25 11:11:01', '2025-07-25 16:11:35'),
(641, 'site_theme_color', '#6c63ff', 'color', 'Site tema rengi (tarayıcı UI)', 'general', 17, '2025-07-25 11:11:02', '2025-07-25 16:11:35'),
(675, 'contact_address', 'Adana, Türkiye', 'text', 'İletişim Adresi', 'general', 18, '2025-07-25 11:37:59', '2025-07-25 16:11:35'),
(845, 'footer_business_title', 'BusinessHesap', 'text', 'Footer iş başlığı', 'footer', 1, '2025-07-25 11:46:17', '2025-07-25 16:11:35'),
(846, 'footer_business_description', 'Kaliteli sosyal medya hesapları ile işinizi büyütmeniz için buradayız. Güvenli alışverişin premium adresi.', 'text', 'Footer iş açıklaması', 'footer', 2, '2025-07-25 11:46:17', '2025-07-25 16:11:35'),
(847, 'footer_quick_links_title', 'Hızlı Erişim', 'text', 'Footer hızlı erişim başlığı', 'footer', 3, '2025-07-25 11:46:17', '2025-07-25 16:11:35'),
(848, 'footer_quick_links', '[\r\n  {\r\n    \"name\": \"Anasayfa\",\r\n    \"url\": \"index.php\",\r\n    \"icon\": \"fas fa-chevron-right\"\r\n  },\r\n  {\r\n    \"name\": \"Tüm Hesaplar\",\r\n    \"url\": \"hesaplar.php\",\r\n    \"icon\": \"fas fa-chevron-right\"\r\n  },\r\n  {\r\n    \"name\": \"Hizmetlerimiz\",\r\n    \"url\": \"hizmetler.php\",\r\n    \"icon\": \"fas fa-chevron-right\"\r\n  },\r\n  {\r\n    \"name\": \"Nasıl Çalışır?\",\r\n    \"url\": \"nasil-calisir.php\",\r\n    \"icon\": \"fas fa-chevron-right\"\r\n  },\r\n  {\r\n    \"name\": \"SSS\",\r\n    \"url\": \"sss.php\",\r\n    \"icon\": \"fas fa-chevron-right\"\r\n  }\r\n]', 'json', 'Footer hızlı erişim linkleri', 'footer', 4, '2025-07-25 11:46:17', '2025-07-25 16:11:35'),
(849, 'footer_social_title', 'BusinessHesap', 'text', 'Footer sosyal medya başlığı', 'footer', 5, '2025-07-25 11:46:17', '2025-07-25 11:46:17'),
(850, 'footer_social_icons', '[\r\n  {\r\n    \"platform\": \"Facebook\",\r\n    \"url\": \"https://facebook.com\",\r\n    \"icon\": \"fab fa-facebook-f\"\r\n  },\r\n  {\r\n    \"platform\": \"Instagram\",\r\n    \"url\": \"https://instagram.com\",\r\n    \"icon\": \"fab fa-instagram\"\r\n  },\r\n  {\r\n    \"platform\": \"Twitter\",\r\n    \"url\": \"https://twitter.com\",\r\n    \"icon\": \"fab fa-twitter\"\r\n  },\r\n  {\r\n    \"platform\": \"LinkedIn\",\r\n    \"url\": \"https://linkedin.com\",\r\n    \"icon\": \"fab fa-linkedin-in\"\r\n  },\r\n  {\r\n    \"platform\": \"YouTube\",\r\n    \"url\": \"https://youtube.com\",\r\n    \"icon\": \"fab fa-youtube\"\r\n  }\r\n]', 'json', 'Footer sosyal medya ikonları', 'footer', 6, '2025-07-25 11:46:17', '2025-07-25 16:11:35'),
(1121, 'adsense_code', '', 'textarea', 'Google AdSense Head Kodu', 'general', 0, '2025-07-25 14:04:44', '2025-07-25 15:53:27'),
(1122, 'popup_ad_enabled', '1', 'boolean', 'Popup Reklam Aktif/Pasif', 'general', 0, '2025-07-25 14:04:44', '2025-07-25 15:53:27'),
(1123, 'popup_ad_content', '<h2>MERHABA <strong>ARKADAŞLAR NASSINIZ İYİMİSİNİZ ?</strong></h2><ol><li><strong>BENDE İYİYİM TEŞŞEKKÜRLER</strong></li><li>&nbsp;</li></ol><p>&nbsp;</p><p><strong>HOSTİNG ALIMLARINDA BAYGATOR HOSTİNG %20 İNDİRİM REFERANS KODU: HOST20</strong></p><blockquote><ol><li><strong>SON 3 KİŞİ KOŞ AL KAP GEL.</strong></li></ol></blockquote>', 'textarea', 'Popup Reklam İçeriği (HTML)', 'general', 0, '2025-07-25 14:04:44', '2025-07-25 15:53:27'),
(1124, 'popup_ad_frequency', '1', 'number', 'Günlük Popup Gösterim Sayısı', 'general', 0, '2025-07-25 14:04:44', '2025-07-25 15:53:27'),
(1125, 'popup_ad_target', 'both', 'select', 'Popup Hedef Kitle (visitors|users|both)', 'general', 0, '2025-07-25 14:04:44', '2025-07-25 15:53:27'),
(1126, 'theme_secondary_color', '#b22222', 'color', 'İkincil tema rengi - Gradient ve ikincil öğeler için', 'theme', 2, '2025-07-25 14:11:42', '2025-07-25 16:11:35'),
(1164, 'theme_tertiary_color', '#ff6347', 'color', 'Üçüncül tema rengi - Secondary butonlar ve özel efektler için', 'theme', 3, '2025-07-25 14:14:38', '2025-07-25 16:11:35'),
(1621, 'theme_link_color', '#ffd700', NULL, NULL, 'general', 0, '2025-07-25 14:37:55', '2025-07-25 16:11:35'),
(1951, 'max_accounts_per_ip', '5', NULL, NULL, 'general', 0, '2025-07-25 16:05:59', '2025-07-25 16:11:35'),
(1952, 'ip_limit_days', '365', NULL, NULL, 'general', 0, '2025-07-25 16:05:59', '2025-07-25 16:11:35'),
(1953, 'ip_limit_enabled', '1', NULL, NULL, 'general', 0, '2025-07-25 16:05:59', '2025-07-25 16:11:35'),
(2000, 'ban_page_title', 'Erişim Engellendi!', NULL, 'Ban sayfası başlık', 'general', 0, '2025-07-25 16:19:35', '2025-07-25 18:03:25'),
(2001, 'ban_page_message', 'IP Adresiniz kurallarımızı ihlal ettiğiniz için yasaklandı!', NULL, 'Ban sayfası mesaj', 'general', 0, '2025-07-25 16:19:35', '2025-07-25 18:03:15'),
(2002, 'ban_page_contact', 'Destek için lütfen bizimle iletişime geçiniz.', NULL, 'Ban sayfası iletişim notu', 'general', 0, '2025-07-25 16:19:35', '2025-07-25 18:04:17');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `ticket_id` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','waiting_customer','resolved','closed') NOT NULL DEFAULT 'open',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_reply_at` timestamp NULL DEFAULT NULL,
  `last_reply_by` enum('customer','admin') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `ticket_id`, `user_id`, `order_id`, `subject`, `message`, `status`, `priority`, `created_at`, `updated_at`, `last_reply_at`, `last_reply_by`) VALUES
(1, 'TK001', 1, 'SP001', 'Hesap giriş sorunu', 'SP001 siparişimdeki hesaplardan birine giriş yapamıyorum. Şifre yanlış görünüyor.', 'closed', 'medium', '2025-07-25 03:24:36', '2025-07-25 13:46:20', '2025-07-25 13:45:51', 'customer'),
(2, 'TK002', 1, 'SP003', 'Eksik teslimat', 'SP003 siparişim için sadece 1 hesap teslim edildi, 2 hesap olması gerekiyordu.', 'closed', 'high', '2025-07-25 03:24:36', '2025-07-25 13:47:13', '2025-07-25 07:16:34', 'customer');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','waiting_customer','resolved','closed') DEFAULT 'open',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ticket_replies`
--

CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_admin_reply` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ticket_replies`
--

INSERT INTO `ticket_replies` (`id`, `ticket_id`, `user_id`, `admin_id`, `message`, `is_admin_reply`, `created_at`) VALUES
(1, 'TK001', 1, NULL, 'SP001 siparişimdeki hesaplardan birine giriş yapamıyorum. Şifre yanlış görünüyor.', 0, '2025-07-25 03:24:36'),
(2, 'TK001', NULL, 1, 'Merhaba, sorununuzu inceliyoruz. Hesap bilgilerini kontrol edip en kısa sürede size dönüş yapacağız.', 1, '2025-07-25 03:24:36'),
(3, 'TK002', 1, NULL, 'SP003 siparişim için sadece 1 hesap teslim edildi, 2 hesap olması gerekiyordu.', 0, '2025-07-25 03:24:36'),
(4, 'TK002', NULL, 1, 'Merhaba, siparişinizi kontrol ediyoruz. En kısa sürede geri dönüş yapacağız.', 1, '2025-07-25 03:45:32'),
(5, 'TK002', 1, NULL, 'test test test', 0, '2025-07-25 04:13:47'),
(6, 'TK002', NULL, 1, 'tmm', 1, '2025-07-25 04:13:47'),
(7, 'TK001', 1, NULL, 'teşekkürler sizden yanıt bekliyorum', 0, '2025-07-25 07:16:11'),
(8, 'TK002', 1, NULL, 'tmm ne be kardeşim düzgün hizmet verin bana', 0, '2025-07-25 07:16:34'),
(9, 'TK001', NULL, 1, 'yanıt verdim e noldu şimdi?', 1, '2025-07-25 13:44:13'),
(10, 'TK001', 1, NULL, 'ne olucağı varmı bilader', 0, '2025-07-25 13:44:48'),
(11, 'TK001', NULL, 1, 'Bilader filan adam ol bak banlarım :D', 1, '2025-07-25 13:45:12'),
(12, 'TK001', 1, NULL, 'Hesabımı verin bak ofisinizi basarım :D', 0, '2025-07-25 13:45:51');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `registration_ip` varchar(45) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `last_login_attempt` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deactivation_reason` text DEFAULT NULL,
  `deactivated_until` timestamp NULL DEFAULT NULL,
  `deactivated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `registration_ip`, `balance`, `is_active`, `is_admin`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expires`, `login_attempts`, `last_login_attempt`, `last_login`, `created_at`, `updated_at`, `deactivation_reason`, `deactivated_until`, `deactivated_by`) VALUES
(1, 'testuser', 'test@example.com', '7b797f1a3fd32da35eba00638ff2ec98:4620d2482231ede4f04b07ecb0e92cd038a6fb14619f50710ebcd2da48aeae4f', 'Test', 'User', '05551234567', '127.0.0.1', 0.00, 1, 1, 0, '4ed901d26f47fc7262252fd22421693b51f9f4355fe95330f040daf2afacbf06', NULL, NULL, 0, NULL, '2025-07-25 19:14:52', '2025-07-24 13:26:33', '2025-07-25 19:14:52', NULL, NULL, NULL),
(8, 'root', 'root@root.com', '3c694b19254a438a2cdf21addba4f23c:cb22c645c3c3fe84c72fe54ef1c7e3b604e3c77d847abb25074b2783603d0b5f', 'Root', 'User', '', '::1', 123.00, 1, 0, 0, '509aef2bc8761fa94cba49398824e3604bf28aaa79c1f83388b8805ce0cd124b', NULL, NULL, 0, NULL, '2025-07-25 19:44:47', '2025-07-25 16:11:38', '2025-07-25 19:44:47', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `user_activity_log`
--

INSERT INTO `user_activity_log` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'register', 'Kullanıcı kaydı oluşturuldu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 13:26:33'),
(2, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 13:26:34'),
(3, 1, 'failed_login', 'Hatalı şifre girişimi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 13:41:15'),
(4, NULL, 'register', 'Kullanıcı kaydı oluşturuldu', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; tr-TR) WindowsPowerShell/5.1.26100.4652', '2025-07-24 13:41:30'),
(5, NULL, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; tr-TR) WindowsPowerShell/5.1.26100.4652', '2025-07-24 13:41:30'),
(6, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 13:48:48'),
(7, 1, 'failed_login', 'Hatalı şifre girişimi', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; tr-TR) WindowsPowerShell/5.1.26100.4652', '2025-07-24 13:57:52'),
(8, 1, 'failed_login', 'Hatalı şifre girişimi', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; tr-TR) WindowsPowerShell/5.1.26100.4652', '2025-07-24 13:58:27'),
(9, 1, 'failed_login', 'Hatalı şifre girişimi', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; tr-TR) WindowsPowerShell/5.1.26100.4652', '2025-07-24 13:58:43'),
(10, NULL, 'register', 'Kullanıcı kaydı oluşturuldu', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; tr-TR) WindowsPowerShell/5.1.26100.4652', '2025-07-24 13:59:11'),
(11, NULL, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; tr-TR) WindowsPowerShell/5.1.26100.4652', '2025-07-24 13:59:26'),
(12, 1, 'failed_login', 'Hatalı şifre girişimi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 14:00:01'),
(13, 1, 'failed_login', 'Hatalı şifre girişimi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 14:00:06'),
(14, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 14:00:39'),
(15, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 14:09:39'),
(16, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 14:36:21'),
(17, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 03:29:24'),
(18, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 03:37:50'),
(19, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 03:40:03'),
(20, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 03:58:00'),
(21, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 07:09:46'),
(22, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 07:15:42'),
(23, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:00:42'),
(24, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 11:50:16'),
(25, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 12:35:01'),
(26, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 13:42:17'),
(27, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 14:58:44'),
(28, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 15:07:14'),
(29, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 15:26:45'),
(30, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 15:30:32'),
(31, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 15:38:51'),
(32, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 15:49:15'),
(33, NULL, 'register', 'Kullanıcı kaydı oluşturuldu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 16:07:32'),
(34, NULL, 'register', 'Kullanıcı kaydı oluşturuldu', NULL, NULL, '2025-07-25 16:07:50'),
(35, 8, 'register', 'Kullanıcı kaydı oluşturuldu ve otomatik giriş yapıldı', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 16:11:38'),
(36, 8, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 16:32:23'),
(37, 8, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 16:33:49'),
(38, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 17:55:51'),
(39, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 17:56:17'),
(40, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 18:09:43'),
(41, 8, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 18:38:06'),
(42, 1, 'failed_login', 'Hatalı şifre girişimi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 18:43:40'),
(43, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 18:43:45'),
(44, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 19:02:23'),
(45, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 19:04:06'),
(46, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 19:08:27'),
(47, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 19:12:05'),
(48, 8, 'failed_login', 'Hatalı şifre girişimi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 19:14:44'),
(49, 1, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 19:14:52'),
(50, 8, 'login', 'Başarılı giriş', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 19:44:47');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `expires_at`, `created_at`) VALUES
(29, 1, '5d5f14ab0b694041a5bb7f48eef6402f0aedd1661181d051c80e54355ea12f4fcec8e64dde3a00adb815dd5bbe09cc02abfbc7fb24af6494dd207d27a65e073d', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 18:43:08', '2025-07-25 18:09:43'),
(30, 8, '2bdf06867a34311d7f102634cb3d792cc1a16b945793f621b600c32528eea290b5c8aa63ab94825557d916a87d18cea34fade39ca1572550de4d3cd50122014c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 17:38:06', '2025-07-25 18:38:06'),
(31, 1, 'fb4e6a164c15a8177dd30e93c2ecca4e78dd2e0412c5f58af80a8f3388f86ae40945678b9b176bb6e93cb82f9e1b2aa2c2cf91f2309cd9163e782ab3f7f92f04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 19:02:08', '2025-07-25 18:43:45'),
(32, 1, 'd2585f2f422663019950311590df58db50966f1cf2024329fe39d20fc7eade6f03b80591c1afa00fd42171016ce4f984c9727351af632a6298b6e915de2d2fc5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 19:09:26', '2025-07-25 19:02:23');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_status_history`
--

CREATE TABLE `user_status_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `old_status` tinyint(1) NOT NULL,
  `new_status` tinyint(1) NOT NULL,
  `reason` text DEFAULT NULL,
  `status_type` enum('permanent','temporary') DEFAULT 'permanent',
  `restore_at` timestamp NULL DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `user_status_history`
--

INSERT INTO `user_status_history` (`id`, `user_id`, `old_status`, `new_status`, `reason`, `status_type`, `restore_at`, `changed_by`, `created_at`) VALUES
(1, 8, 1, 0, 'keyfimdeeen', 'temporary', '2025-07-26 17:22:34', 1, '2025-07-25 18:22:34'),
(2, 8, 0, 1, 'Admin tarafından yeniden aktif edildi', 'permanent', NULL, 1, '2025-07-25 19:44:38');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_platform` (`platform`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Tablo için indeksler `account_features`
--
ALTER TABLE `account_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_account` (`account_id`);

--
-- Tablo için indeksler `account_images`
--
ALTER TABLE `account_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_account` (`account_id`);

--
-- Tablo için indeksler `account_stock`
--
ALTER TABLE `account_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_account_id` (`account_id`),
  ADD KEY `idx_is_sold` (`is_sold`);

--
-- Tablo için indeksler `active_sessions`
--
ALTER TABLE `active_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_active` (`is_active`);

--
-- Tablo için indeksler `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Tablo için indeksler `contact_settings`
--
ALTER TABLE `contact_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Tablo için indeksler `crypto_payments`
--
ALTER TABLE `crypto_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `crypto_order_id` (`order_id`),
  ADD KEY `cryptomus_uuid` (`cryptomus_uuid`),
  ADD KEY `status` (`status`);

--
-- Tablo için indeksler `crypto_settings`
--
ALTER TABLE `crypto_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting_key` (`setting_key`);

--
-- Tablo için indeksler `crypto_webhook_logs`
--
ALTER TABLE `crypto_webhook_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `cryptomus_uuid` (`cryptomus_uuid`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Tablo için indeksler `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `ip_bans`
--
ALTER TABLE `ip_bans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_active_bans` (`ip_address`,`is_active`,`banned_until`),
  ADD KEY `banned_by` (`banned_by`);

--
-- Tablo için indeksler `ip_limit_resets`
--
ALTER TABLE `ip_limit_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `reset_by` (`reset_by`);

--
-- Tablo için indeksler `legal_pages`
--
ALTER TABLE `legal_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_type` (`page_type`);

--
-- Tablo için indeksler `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `order_date` (`order_date`);

--
-- Tablo için indeksler `order_accounts`
--
ALTER TABLE `order_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `is_active` (`is_active`);

--
-- Tablo için indeksler `payment_items`
--
ALTER TABLE `payment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Tablo için indeksler `popup_ad_views`
--
ALTER TABLE `popup_ad_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_daily_view` (`ip_address`,`user_id`,`view_date`),
  ADD KEY `idx_view_date` (`view_date`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_visitor_id` (`visitor_id`);

--
-- Tablo için indeksler `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Tablo için indeksler `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_id` (`ticket_id`),
  ADD UNIQUE KEY `unique_order_ticket` (`user_id`,`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Tablo için indeksler `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Tablo için indeksler `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_verification_token` (`verification_token`),
  ADD KEY `idx_reset_token` (`reset_token`),
  ADD KEY `idx_users_registration_ip` (`registration_ip`);

--
-- Tablo için indeksler `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Tablo için indeksler `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Tablo için indeksler `user_status_history`
--
ALTER TABLE `user_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_restore_at` (`restore_at`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `account_features`
--
ALTER TABLE `account_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Tablo için AUTO_INCREMENT değeri `account_images`
--
ALTER TABLE `account_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `account_stock`
--
ALTER TABLE `account_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `active_sessions`
--
ALTER TABLE `active_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `contact_settings`
--
ALTER TABLE `contact_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Tablo için AUTO_INCREMENT değeri `crypto_payments`
--
ALTER TABLE `crypto_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `crypto_settings`
--
ALTER TABLE `crypto_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `crypto_webhook_logs`
--
ALTER TABLE `crypto_webhook_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `ip_bans`
--
ALTER TABLE `ip_bans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `ip_limit_resets`
--
ALTER TABLE `ip_limit_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `legal_pages`
--
ALTER TABLE `legal_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Tablo için AUTO_INCREMENT değeri `order_accounts`
--
ALTER TABLE `order_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Tablo için AUTO_INCREMENT değeri `payment_items`
--
ALTER TABLE `payment_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `popup_ad_views`
--
ALTER TABLE `popup_ad_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- Tablo için AUTO_INCREMENT değeri `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2003;

--
-- Tablo için AUTO_INCREMENT değeri `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `ticket_replies`
--
ALTER TABLE `ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- Tablo için AUTO_INCREMENT değeri `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Tablo için AUTO_INCREMENT değeri `user_status_history`
--
ALTER TABLE `user_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `account_features`
--
ALTER TABLE `account_features`
  ADD CONSTRAINT `account_features_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `account_images`
--
ALTER TABLE `account_images`
  ADD CONSTRAINT `account_images_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `account_stock`
--
ALTER TABLE `account_stock`
  ADD CONSTRAINT `account_stock_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `crypto_payments`
--
ALTER TABLE `crypto_payments`
  ADD CONSTRAINT `crypto_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ip_bans`
--
ALTER TABLE `ip_bans`
  ADD CONSTRAINT `ip_bans_ibfk_1` FOREIGN KEY (`banned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ip_limit_resets`
--
ALTER TABLE `ip_limit_resets`
  ADD CONSTRAINT `ip_limit_resets_ibfk_1` FOREIGN KEY (`reset_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `order_accounts`
--
ALTER TABLE `order_accounts`
  ADD CONSTRAINT `fk_order_accounts_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `payment_items`
--
ALTER TABLE `payment_items`
  ADD CONSTRAINT `payment_items_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `crypto_payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_items_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `fk_tickets_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

--
-- Tablo kısıtlamaları `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD CONSTRAINT `fk_replies_tickets` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`ticket_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_replies_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_status_history`
--
ALTER TABLE `user_status_history`
  ADD CONSTRAINT `user_status_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
