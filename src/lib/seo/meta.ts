/**
 * Locked meta titles/descriptions from legacy PHP pages.
 * Preserve character-for-character for ranking continuity.
 */

export { SITE_URL, SITE_NAME } from "./slugs";

export type PageMeta = {
  title: string;
  description: string;
};

export const HOME_META: PageMeta = {
  title: "Facebook Reklam Hesabı Satın Al | Business Manager & Reklam Hesapları",
  description:
    "Facebook reklam hesabı satın al, Business Manager hesap, doğrulanmış reklam hesapları ve kimlik onaylı Facebook hesapları en uygun fiyatlarla!",
};

export const ALL_ACCOUNTS_META: PageMeta = {
  title: "Facebook Premium Hesap Satın Al | Doğrulanmış ve Güvenli",
  description:
    "Premium Facebook hesapları, doğrulanmış ve güvenli seçeneklerle hızlı teslimat. Instagram, TikTok ve Business Manager hesapları da mevcut.",
};

/** categoryId → meta */
export const CATEGORY_META: Readonly<Record<number, PageMeta>> = {
  7: {
    title: "Doğrulanmış Facebook Hesapları | Premium Seçenekler",
    description:
      "Kimlik doğrulaması yapılmış, ana hesap olarak kullanılan ve reklam ile Business Manager uyumlu Facebook hesapları.",
  },
  8: {
    title: "Doğrulanmış Facebook Premium Hesap Satın Al | Hızlı Teslim",
    description:
      "Dirençli ve premium doğrulanmış Facebook hesapları. Hızlı teslimat ve güvenli alışveriş.",
  },
  12: {
    title: "Premium Facebook Eski Hesapları Uygun Fiyata Satın Alın!",
    description:
      "Eski (aged) Facebook hesapları uygun fiyatlarla. Doğrulanmış ve kullanıma hazır seçenekler.",
  },
  13: {
    title: "Eski ve Doğrulanmış Business Manager Hesapları | Limitli",
    description:
      "Doğrulanmış Business Manager hesapları, limitli ve kullanıma hazır seçenekler.",
  },
  14: {
    title: "Facebook Marketplace Hesapları | Satışa Açık ve Aktif",
    description:
      "Satışa açık ve aktif Facebook Marketplace hesapları. Güvenli ve hızlı teslimat.",
  },
  15: {
    title: "Facebook Hesapları Satın Al | Güvenli ve Hızlı Teslim",
    description:
      "Facebook hesap satın al, premium hesaplar, doğrulanmış hesaplar, eski sosyal medya hesapları.",
  },
  16: {
    title: "Instagram Hesapları Satın Al | Güvenli ve Hızlı Teslim",
    description:
      "Doğrulanmış Instagram hesapları, tanıtım onaylı ve takipçi sayısına göre çeşitlendirilmiş seçenekler.",
  },
  20: {
    title: "X (Twitter) Hesapları Satın Al | Aktif Profiller Uygun Fiyat",
    description:
      "Aktif X (Twitter) profilleri uygun fiyatlarla. Güvenli teslimat.",
  },
  21: {
    title: "TikTok Hesapları Satın Al | Aktif Profiller Uygun Fiyat",
    description: "Aktif TikTok profilleri uygun fiyatlarla. Güvenli teslimat.",
  },
  22: {
    title: "Gmail, Outlook ve Hotmail Mail Hesapları | Güvenli ve Ucuz",
    description:
      "Gmail, Outlook ve Hotmail mail hesapları. Güvenli ve uygun fiyatlı seçenekler.",
  },
  27: {
    title: "Telegram Hesapları Satın Al | Hazır ve Kullanıma Uygun",
    description: "Doğrulanmış Telegram hesapları. Hazır ve kullanıma uygun.",
  },
};

/** accountId → meta title (hardcoded in legacy view.php) */
export const PRODUCT_META_TITLES: Readonly<Record<number, string>> = {
  12: "Kimlik Doğrulanmış Süper Eski Facebook Hesabı Satın Al",
  13: "Kimlik Doğrulanmış Eski Facebook Hesabı Satın Al",
  14: "Kimlik Doğrulanmış Süper Eski Facebook Hesabı Satın Al",
  15: "Kimlik Doğrulanmış Eski Facebook Hesabı Satın Al",
  16: "Kimlik Doğrulanmış Eski Facebook Hesabı Satın Al",
  17: "Vietnam Kimliği Doğrulanmış Eski Facebook Hesabı Satın Al",
  18: "Yeniden Açılan Eski Facebook Hesabı Satın Al | Vietnam 2024",
  19: "Vietnamese Reinstated Aged Facebook Account (2010-2023)",
  20: "Reinstated Aged Facebook Account (2010-2023)",
  21: "Reinstated Supper Aged Facebook Account (2007-2015)",
  26: "Poland Old Facebook Account (2008-2021)",
  27: "Taiwan Old Facebook Account",
  28: "UK Old Facebook Account",
  29: "USA Old Facebook Account (2024)",
  30: "Country Facebook Account | Sub Profile",
  31: "India Aged Facebook Account | 2FA Açık Eski Hesap",
  35: "Doğrulanmış Business Manager Hesapları | 5'li Paket Satın Al",
  36: "Kısıttan Dönmüş Doğrulanmış Business Manager Satın Al",
  37: "Doğrulanmış Business Manager Hesapları | 3'lü Paket Satın Al",
  38: "Eski Business Manager Hesap Satın Al | Doğrulanmış Hesaplar",
  39: "Business Manager Hesapları Satın Al | Doğrulanmış ve Hazır",
  40: "250$ Günlük Limitli Kişisel Reklam Hesabı Satın Al",
  41: "Facebook Marketplace Hesapları Satın Al",
  42: "Kimlik Onaylı Facebook Hesapları Satın Al",
  43: "Güçlendirilmiş Kimlik Onaylı Facebook Hesapları Satın Al",
  44: "Türk Facebook Hesapları Satın Al | Doğrulanmış ve Güvenli",
  45: "Yabancı Facebook Hesapları Satın Al | Doğrulanmış ve Güvenli",
  46: "10.000 Takipçili Instagram Hesapları Satın Al",
  47: "5.000 Takipçili Instagram Hesapları Satın Al",
  49: "20 Adet Gönderili Instagram Hesapları Satın Al",
  50: "Tanıtım Onaylı Instagram Hesapları Satın Al",
  60: "Telegram Hesapları Satın Al | Doğrulanmış ve Güvenli",
};

export const STATIC_PAGE_META: Readonly<Record<string, PageMeta>> = {
  "sikca-sorulan-sorular": {
    title: "Sıkça Sorulan Sorular",
    description:
      "Facebook hesap satın alma, teslimat, garanti ve ödeme hakkında sıkça sorulan sorular.",
  },
  hizmetler: {
    title: "Premium Sosyal Medya Hesap Satın Alarak İşlerinizi Büyütün",
    description:
      "Premium sosyal medya hesapları ile işlerinizi büyütün. Doğrulanmış hesaplar, hızlı teslimat.",
  },
  iletisim: {
    title: "Bize Ulaşın | 7/24 Canlı Destek ve Hızlı Yardım",
    description:
      "Facebook hesap satın alma ve diğer konularda herhangi bir sorunuz varsa bize hemen ulaşın. 7/24 canlı destekle hemen yardım alın.",
  },
  "giris-yap": {
    title: "Giriş Yap | YildizHesap",
    description: "YildizHesap hesabınıza giriş yapın.",
  },
  "kayit-ol": {
    title: "Kayıt Ol | YildizHesap",
    description: "YildizHesap'a ücretsiz kayıt olun.",
  },
  "siparis-takip": {
    title: "Sipariş Takip | Misafir Sipariş Sorgulama",
    description: "Misafir siparişinizi takip edin.",
  },
};
