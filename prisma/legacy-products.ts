/**
 * Seed data for the 32 legacy yildizhesap.net product slugs.
 *
 * Titles and slugs are locked to the legacy view.php + rewrite rules so Google
 * keeps ranking these URLs. Prices are placeholders because the live database
 * was lost; adjust them from the admin panel. Categories mirror the legacy
 * navigation.
 */

export type LegacyProductSeed = {
  id: number;
  categoryId: number;
  title: string;
  price: number;
  platform: string;
  accountType: string;
};

export const LEGACY_PRODUCTS: readonly LegacyProductSeed[] = [
  { id: 12, categoryId: 8, title: "Kimlik Doğrulanmış Süper Eski Facebook Hesabı", price: 1000, platform: "Facebook", accountType: "Eski Hesap" },
  { id: 13, categoryId: 8, title: "Kimlik Doğrulanmış Eski Facebook Hesabı", price: 1000, platform: "Facebook", accountType: "Eski Hesap" },
  { id: 14, categoryId: 8, title: "Kimlik Doğrulanmış Süper Eski Facebook Hesabı", price: 1000, platform: "Facebook", accountType: "Eski Hesap" },
  { id: 15, categoryId: 8, title: "Kimlik Doğrulanmış Eski Facebook Hesabı", price: 1000, platform: "Facebook", accountType: "Eski Hesap" },
  { id: 16, categoryId: 8, title: "Kimlik Doğrulanmış Eski Facebook Hesabı", price: 1000, platform: "Facebook", accountType: "Eski Hesap" },
  { id: 17, categoryId: 8, title: "Vietnam Kimliği Doğrulanmış Eski Facebook Hesabı", price: 1000, platform: "Facebook", accountType: "Vietnam Kimlikli" },
  { id: 18, categoryId: 8, title: "Yeniden Açılan Eski Facebook Hesabı | Vietnam 2024", price: 1000, platform: "Facebook", accountType: "Reinstated" },
  { id: 19, categoryId: 8, title: "Vietnamese Reinstated Aged Facebook Account (2010-2023)", price: 1000, platform: "Facebook", accountType: "Reinstated" },
  { id: 20, categoryId: 8, title: "Reinstated Aged Facebook Account (2010-2023)", price: 1000, platform: "Facebook", accountType: "Reinstated" },
  { id: 21, categoryId: 8, title: "Reinstated Supper Aged Facebook Account (2007-2015)", price: 1000, platform: "Facebook", accountType: "Reinstated" },
  { id: 26, categoryId: 12, title: "Poland Old Facebook Account (2008-2021)", price: 500, platform: "Facebook", accountType: "Country Aged" },
  { id: 27, categoryId: 12, title: "Taiwan Old Facebook Account", price: 500, platform: "Facebook", accountType: "Country Aged" },
  { id: 28, categoryId: 12, title: "UK Old Facebook Account", price: 500, platform: "Facebook", accountType: "Country Aged" },
  { id: 29, categoryId: 12, title: "USA Old Facebook Account (2024)", price: 500, platform: "Facebook", accountType: "Country Aged" },
  { id: 30, categoryId: 12, title: "Country Facebook Account | Sub Profile", price: 500, platform: "Facebook", accountType: "Country Aged" },
  { id: 31, categoryId: 12, title: "India Aged Facebook Account | 2FA Açık Eski Hesap", price: 500, platform: "Facebook", accountType: "Country Aged" },
  { id: 35, categoryId: 13, title: "Doğrulanmış Business Manager Hesapları | 5'li Paket", price: 5000, platform: "Facebook", accountType: "Business Manager" },
  { id: 36, categoryId: 13, title: "Kısıttan Dönmüş Doğrulanmış Business Manager", price: 1500, platform: "Facebook", accountType: "Business Manager" },
  { id: 37, categoryId: 13, title: "Doğrulanmış Business Manager Hesapları | 3'lü Paket", price: 3000, platform: "Facebook", accountType: "Business Manager" },
  { id: 38, categoryId: 13, title: "Eski Business Manager Hesap", price: 500, platform: "Facebook", accountType: "Business Manager" },
  { id: 39, categoryId: 13, title: "Business Manager Hesapları", price: 300, platform: "Facebook", accountType: "Business Manager" },
  { id: 40, categoryId: 13, title: "250$ Günlük Limitli Kişisel Reklam Hesabı", price: 1000, platform: "Facebook", accountType: "Ads Account" },
  { id: 41, categoryId: 14, title: "Facebook Marketplace Hesapları", price: 1000, platform: "Facebook", accountType: "Marketplace" },
  { id: 42, categoryId: 7, title: "Kimlik Onaylı Facebook Hesapları", price: 1000, platform: "Facebook", accountType: "Kimlik Onaylı" },
  { id: 43, categoryId: 7, title: "Güçlendirilmiş Kimlik Onaylı Facebook Hesapları", price: 1500, platform: "Facebook", accountType: "Kimlik Onaylı" },
  { id: 44, categoryId: 15, title: "Türk Facebook Hesapları", price: 75, platform: "Facebook", accountType: "Türk" },
  { id: 45, categoryId: 15, title: "Yabancı Facebook Hesapları", price: 100, platform: "Facebook", accountType: "Yabancı" },
  { id: 46, categoryId: 16, title: "10.000 Takipçili Instagram Hesapları", price: 2000, platform: "Instagram", accountType: "10K Takipçili" },
  { id: 47, categoryId: 16, title: "5.000 Takipçili Instagram Hesapları", price: 1000, platform: "Instagram", accountType: "5K Takipçili" },
  { id: 49, categoryId: 16, title: "20 Adet Gönderili Instagram Hesapları", price: 300, platform: "Instagram", accountType: "Gönderili" },
  { id: 50, categoryId: 16, title: "Tanıtım Onaylı Instagram Hesapları", price: 500, platform: "Instagram", accountType: "Tanıtım Onaylı" },
  { id: 60, categoryId: 27, title: "Telegram Hesapları", price: 100, platform: "Telegram", accountType: "Doğrulanmış" },
] as const;

/**
 * businesshesap.com'un 39 kategorisi bir yildizhesap.net kategorisine
 * bağlanmalı. Isim tabanlı map — kullanıcı panelden dilediği gibi değiştirir.
 * key: businesshesap.com source id, value: yildizhesap.net category id.
 */
export const BUSINESSHESAP_CATEGORY_MAP: Readonly<Record<number, number>> = {
  1: 13, //  Reklam Hizmeti Tüm Sektörler → business-manager
  2: 16, //  Eski Instagram → instagram
  3: 13, //  Business Manager → business-manager
  4: 13, //  Eski BM → business-manager
  5: 15, //  Facebook 3.Paket → facebook
  6: 7, //   Kimlik Onaylı Facebook → ana-hesaplar-dogrulanmis
  7: 13, //  BM 3'lü → business-manager
  8: 15, //  Eski Facebook Sayfaları → facebook
  9: 15, //  Organik Yorum → facebook (hizmet)
  10: 15, // Facebook 1.Paket Türk → facebook
  11: 15, // Facebook 2.Paket Türk → facebook
  12: 22, // Onayla.com SMS → mail (yakın kategori)
  13: 15, // Discord → facebook (fallback)
  14: 16, // Instagram 1000 → instagram
  15: 16, // Instagram 5000 → instagram
  16: 16, // Instagram 10000 → instagram
  17: 16, // Tanıtım Onaylı Instagram → instagram
  18: 22, // Gmail → mail
  19: 20, // X → x
  20: 22, // Outlook-Hotmail → mail
  21: 21, // TikTok → tiktok
  22: 27, // Telegram → telegram
  23: 13, // Doğrulanmış BM → business-manager
  24: 13, // 5'li BM → business-manager
  25: 13, // 250$ reklam → business-manager
  26: 13, // 10 reklam hesaplı → business-manager
  27: 14, // Marketplace → facebook-marketplace
  28: 13, // Kısıttan Dönmüş BM → business-manager
  29: 13, // 250 Dolar BM → business-manager
  30: 13, // 30-60 reklam → business-manager
  31: 13, // 5'li Doğrulanmış BM → business-manager
  32: 16, // Gönderili Instagram → instagram
  33: 16, // 2012 Instagram → instagram
  34: 16, // Mavi Tik Instagram → instagram
  35: 16, // 10 gönderili Instagram → instagram
  36: 16, // 1-4 gönderili Instagram → instagram
  37: 15, // Harcama yapmış Facebook → facebook
  38: 15, // Reklam erişimi Facebook → facebook
  39: 15, // Ads aktif mail onaylı → facebook
};

/** Offset added to businesshesap.com source id to avoid collision with legacy ids. */
export const BUSINESSHESAP_ID_OFFSET = 1000;
