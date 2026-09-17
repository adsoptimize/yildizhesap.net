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
  // 12-16 were five pages sharing two titles, so Google clustered them and
  // indexed only one per group. The distinguishing year/country already lives
  // in each slug; these titles now surface it.
  12: "Kimlik Doğrulanmış Süper Eski Facebook Hesabı Satın Al | 2015",
  13: "Kimlik Doğrulanmış Eski Facebook Hesabı Satın Al | 2010",
  14: "Kimlik Doğrulanmış Süper Eski Facebook Hesabı Satın Al | 2010-2020",
  15: "Kimlik Doğrulanmış Eski Facebook Hesabı Satın Al | 2010-2023",
  16: "Vietnam Kimliği Doğrulanmış Eski Facebook Hesabı Satın Al | 2024",
  17: "Vietnam Kimliği Doğrulanmış Eski Facebook Hesabı Satın Al | 2023-2024",
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

/** accountId → meta description (hardcoded in legacy view.php) */
export const PRODUCT_META_DESCRIPTIONS: Readonly<Record<number, string>> = {
  12: "2007-2015 yılları arasında açılmış kimlik doğrulamalı süper eski Facebook hesabını güvenli kullanım ve hızlı teslimat avantajıyla satın alın.",
  13: "2010-2023 arasında açılmış, kimlik kartlı ve 2FA aktif eski Facebook hesabını yüksek arkadaş sayısı, güvenli yapı ve hızlı teslimatla satın alın.",
  14: "2010-2020 arası açılmış kimlik kartlı süper eski Facebook hesabını 2FA aktif yapı ve hızlı teslimat avantajıyla hemen satın alın.",
  15: "2010-2023 arası açılmış kimlik kartlı eski Facebook hesaplarını uygun fiyat avantajı ile hızlı ve güvenli teslimatla hemen satın alın.",
  16: "2024 yılında oluşturulmuş kimlik kartlı eski Facebook hesabını uygun fiyat avantajı ve hızlı teslimat güvencesiyle hemen satın alın.",
  17: "2023-2024 döneminde açılmış Vietnam kimlik kartlı Facebook hesabını uygun fiyat avantajı, UID 6155x ve 2FA aktif yapı ile hemen satın alın.",
  18: "2024 açılış tarihli, Vietnam'da yeniden açılmış eski Facebook hesabı satın alın. Kimlik onaylı, anında teslimat, üstelik 30 gün garanti.",
  19: "2010-2023 açılış tarihli, Vietnam bölgesinde yeniden açılmış eski Facebook hesabı. Kimlik onaylı, UID 100xx ve 2FA destekli, 30 gün garantili.",
  20: "2010-2023 açılış tarihli, yeniden açılmış eski Facebook hesabı hemen satın al. Kimlik onaylı, UID 1000xx ve 2FA destekli, 30 gün garantili.",
  21: "2007-2015 açılış tarihli, yeniden açılmış süper eski Facebook hesap satın al. Kimlik onaylı, 2FA destekli, hızlı teslim, 30 gün garantili.",
  26: "2008-2021 açılış tarihli Polonya eski Facebook hesabı. 0-1000 arkadaşlı, 2FA ve Live Ads destekli. Anında teslimat ve 30 gün garanti.",
  27: "Tayvan bölgesine ait eski Facebook hesabı. Live Ads uyumlu, 30 gün garantili bu hesabı hızlı teslim imkanı ile hemen satın alın.",
  28: "Birleşik Krallık bölgesine ait eski Facebook hesabı. Live Ads uyumlu, No2FA yapı. Hızlı teslimat ve 30 gün garanti ile güvenli satış.",
  29: "2024 tarihli ABD lokasyonlu eski Facebook hesabı. 30+ arkadaşlı, Live Ads aktif. Anında teslimat ve 30 gün garanti ile güvenli satış.",
  30: "Ülke lokasyonlu Facebook hesabı. Sub profil oluşturma destekli, doğrulanmış ve premium yapıdadır. Hızlı teslimat, güvenli satış, 30 gün garantili.",
  31: "Hindistan lokasyonlu eski Facebook hesabı. Rastgele arkadaşlı, 2FA açık, doğrulanmış ve premium yapıdadır. Güvenli satış, 30 gün garantili.",
  35: "5 adet doğrulanmış Business Manager hesabından oluşan paket. Premium altyapı, güvenli kullanım, anında teslimat ve 30 gün garanti ile sunulur.",
  36: "Kısıtı kaldırılmış, yeniden aktif edilmiş, doğrulanmış Business Manager hesabı. Premium kullanım, güvenli altyapı, hızlı teslim ile sunulur.",
  37: "3'lü paket halinde sunulan doğrulanmış Business Manager hesapları. Premium kullanım, güvenli altyapı, hızlı teslimat, 30 gün garantili.",
  38: "Eski ve doğrulanmış Business Manager hesapları hemen satın alın. Premium kullanım, güvenli yapı, anında teslimat, 30 gün garantili.",
  39: "Doğrulanmış Business Manager hesapları hemen satın alın. Premium kullanım, güvenli yapı, hızlı teslimat, 30 gün garanti satış imkanı.",
  40: "Günlük 250 dolar harcama limitine sahip, doğrulanmış tekli kişisel reklam hesabı. Premium yapı, hızlı teslimat, 30 gün garantili.",
  41: "Facebook Marketplace kullanıma hazır, doğrulanmış premium hesaplar. Güvenli yapı, hızlı teslimat, 7/24 destek ve 30 gün garantili.",
  42: "Kimlik onaylı Facebook hesapları ile güvenli ve stabil kullanım sağlayın. Premium yapı, hızlı teslimat, 7/24 destek ve 30 gün garantili.",
  43: "Güçlendirilmiş kimlik onaylı Facebook hesapları ile güvenli ve stabil kullanım elde edin. Premium yapı, hızlı teslimat, 30 gün garanti.",
  44: "Türk Facebook hesapları ile yerel ve güvenli kullanım sağlayın. Doğrulanmış yapı, premium kalite, hızlı teslimat, 7/24 destek, 30 gün garanti.",
  45: "Yabancı Facebook hesapları ile farklı ülkelerden güvenli ve stabil kullanım sağlayın. Doğrulanmış yapı, premium kalite, hızlı teslimat.",
  46: "10.000 takipçili Instagram hesapları ile hızlı ve güçlü bir başlangıç yapın. Doğrulanmış yapı, premium kalite, hızlı teslimat, 30 gün garanti.",
  47: "5.000 takipçili Instagram hesapları ile hızlı bir başlangıç yapın. Doğrulanmış yapı, premium kalite, 7/24 destek, 30 gün garanti.",
  49: "20 adet gönderili Instagram hesapları ile sosyal medya varlığınızı hemen başlatın. Doğrulanmış hesaplar, 7/24 destek, 30 gün garanti.",
  50: "Tanıtım onaylı Instagram hesapları ile sosyal medya kampanyalarınızı güvenle başlatın. Doğrulanmış hesaplar, premium kalite, 30 gün garanti.",
  60: "Doğrulanmış Telegram hesapları ile hızlı ve güvenli iletişime geçin. Premium hesaplar, anında teslimat, 7/24 destek, 30 gün garanti.",
};

/** accountId → meta keywords (hardcoded in legacy view.php) */
export const PRODUCT_META_KEYWORDS: Readonly<Record<number, string>> = {
  12: "kimlik doğrulanmış facebook hesabı satın al, süper eski facebook hesabı, premium facebook hesap satın al, doğrulanmış facebook hesap satışı",
  13: "kimlik doğrulanmış facebook hesabı satışı, eski facebook hesap satın al, 2fa aktif facebook hesap, arkadaşlı facebook hesabı satın al",
  14: "kimlik doğrulanmış facebook hesapları, süper eski facebook hesap satın al, 2fa aktif hesap yönetimi",
  15: "kimlik doğrulanmış facebook hesabı satın al, eski facebook hesap satın al, aktif facebook hesabı uygun fiyatlı",
  16: "kimlik doğrulanmış facebook hesapları, eski facebook hesap satın al, aktif facebook hesap satışı, uid 100xx",
  17: "vietnam facebook hesabı, kimlik doğrulanmış facebook hesabı, eski facebook hesap satın al, 2fa aktif hesap",
  18: "vietnam facebook hesabı, yeniden açılan facebook hesabı, kimlik onaylı hesap",
  19: "vietnamese facebook account, reinstated aged facebook, kimlik onaylı facebook hesap uygun fiyat",
  20: "reinstated facebook account, aged facebook account, kimlik onaylı facebook, 2fa facebook hesabı satın al",
  21: "reinstated aged facebook, super aged facebook account, kimlik onaylı 2fa facebook hesap satın al",
  26: "poland facebook account, old facebook hesaplar, aged fb account yönetimi, live ads fb",
  27: "taiwan facebook account, old fb account, live ads facebook satın al",
  28: "uk facebook account, old fb account, live ads facebook, no2fa facebook, random friends",
  29: "usa facebook account, old fb account 2024, live ads facebook, abd facebook hesabı",
  30: "country facebook account, sub profil facebook, random country fb, premium facebook hesabı",
  31: "india facebook account, aged facebook hesabı, 2fa açık facebook, random friends fb",
  35: "business manager hesapları, doğrulanmış bm hesap, 5'li business manager reklam hesabı",
  36: "kısıttan dönmüş business manager, aktif bm hesabı, doğrulanmış business manager, reklam hesabı satın al",
  37: "doğrulanmış business manager, 3'lü bm paketi, business manager satın al",
  38: "eski business manager hesap satın al, doğrulanmış business manager uygun fiyatlı",
  39: "business manager hesapları, doğrulanmış business manager satın al, bm hesap garantili satış",
  40: "250 dolar limitli reklam hesabı, kişisel reklam hesabı satın al, günlük harcama limitli hesap",
  41: "facebook marketplace hesabı satın al, marketplace hesapları, ürün satış hesabı",
  42: "kimlik onaylı facebook hesabı satın al, doğrulanmış profil hesapları, güvenli facebook hesap",
  43: "güçlendirilmiş kimlik onaylı facebook hesabı satın al, doğrulanmış facebook profil hesapları, güvenli facebook hesap",
  44: "Türk Facebook hesabı satın al, doğrulanmış Türk Facebook hesapları, güvenli Facebook hesabı",
  45: "yabancı facebook hesabı satın al, doğrulanmış yabancı facebook hesapları, güvenli facebook hesabı",
  46: "10.000 takipçili instagram hesabı satın al, doğrulanmış instagram hesapları, takipçili instagram hesabı",
  47: "5.000 takipçili instagram hesabı satın al, doğrulanmış instagram hesapları satışı, takipçili instagram hesabı satın al",
  49: "20 gönderili instagram hesabı satın al, doğrulanmış instagram hesapları, premium instagram profilleri",
  50: "tanıtım onaylı instagram hesabı satın al, doğrulanmış instagram hesapları, premium instagram profilleri satışı",
  60: "telegram hesabı satın al, doğrulanmış telegram hesapları, premium telegram profilleri satın al",
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
  // Not added to STATIC_SEO_ROUTES: both pages are noindex and must stay out
  // of the sitemap.
  "sifremi-unuttum": {
    title: "Şifremi Unuttum | YildizHesap",
    description:
      "Hesabınızın şifresini e-posta adresinizle sıfırlayın.",
  },
  "sifre-sifirla": {
    title: "Yeni Şifre Belirle | YildizHesap",
    description: "Şifre sıfırlama bağlantınızla yeni şifrenizi belirleyin.",
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
