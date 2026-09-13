/**
 * Locked SEO URL maps — must match legacy .htaccess + setup_seo_urls.php exactly.
 * Changing these without 301 planning will hurt rankings.
 */

export const SITE_URL = "https://yildizhesap.net";
export const SITE_NAME = "yildizhesap.net - Hesap Satış Platformu";

/** categoryId → seo_slug */
export const CATEGORY_SEO_SLUGS: Readonly<Record<number, string>> = {
  7: "ana-hesaplar-dogrulanmis",
  8: "direncli-hesaplar",
  12: "old-accounts",
  13: "business-manager",
  14: "facebook-marketplace",
  15: "facebook-hesaplari",
  16: "instagram-hesaplari",
  20: "onayli-twitter-hesaplari",
  21: "tiktok-hesaplari",
  22: "mail-gmail-outlook-hotmail-hesaplari",
  27: "telegram-hesaplari",
} as const;

/** accountId → seo_slug */
export const PRODUCT_SEO_SLUGS: Readonly<Record<number, string>> = {
  12: "rastgele-kimlik-dogrulanmis-super-eski-facebook-hesabi-2015",
  13: "rastgele-kimlik-dogrulanmis-eski-facebook-hesabi-2010",
  14: "rastgele-kimlik-dogrulanmis-super-eski-facebook-hesabi-2010-2020",
  15: "rastgele-kimlik-dogrulanmis-eski-facebook-hesabi-2010-2023",
  16: "vietnam-kimligi-dogrulanmis-eski-facebook-hesabi-2024",
  17: "vietnam-kimligi-dogrulanmis-eski-facebook-hesabi-2023-2024",
  18: "vietnamda-yeniden-acilan-eski-facebook-hesabi-2024",
  19: "vietnamese-reinstated-aged-fb-account-2024",
  20: "random-reinstated-aged-fb-account-2010-2023",
  21: "random-reinstated-super-aged-fb-account-2007-2015",
  26: "poland-old-fb-account-2008-2021",
  27: "taiwan-old-fb-account",
  28: "uk-old-fb-account",
  29: "usa-old-fb-account-2024",
  30: "random-country-fb-account-can-create-sub-profile",
  31: "india-aged-fb-account",
  35: "250-dolar-limitli-tekli-business-manager-hesaplari",
  36: "kisittan-donmus-business-manager-hesaplari",
  37: "dogrulanmis-business-manager-hesaplari-3lu",
  38: "eski-business-manager-hesaplari",
  39: "business-manager-hesaplari",
  40: "gunluk-harcama-250-dolar-limitli-tekli-kisisel-reklam-hesabi",
  41: "facebook-marketplace-hesaplari",
  42: "kimlik-onayli-facebook-hesaplari",
  43: "kimlik-onayli-facebook-hesaplari-guclendirilmis",
  44: "facebook-hesaplari-turk",
  45: "facebook-hesaplari-yabanci",
  46: "instagram-hesaplari-10000-takipcili",
  47: "instagram-hesaplari-5000-takipcili",
  49: "20-adet-gonderili-instagram-hesaplari",
  50: "tanitim-onayli-instagram-hesaplari",
  60: "dogrulanmis-telegram-hesap",
} as const;

/** Static SEO routes from router.php */
export const STATIC_SEO_ROUTES = [
  "siparis-takip",
  "tum-hesaplar",
  "sikca-sorulan-sorular",
  "hizmetler",
  "iletisim",
  "kvkk",
  "giris-yap",
  "kayit-ol",
  "gizlilik-politikasi",
] as const;
