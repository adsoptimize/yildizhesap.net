/**
 * Editable-in-admin defaults, ported from legacy SiteSettings::getDefaultSettings()
 * and the category list behind the locked SEO slugs.
 */

export const CATEGORY_NAMES: Readonly<Record<number, string>> = {
  7: "Doğrulanmış Ana Hesaplar",
  8: "Dirençli Hesaplar",
  12: "Old Accounts",
  13: "Business Manager",
  14: "Facebook Marketplace",
  15: "Facebook Hesapları",
  16: "Instagram Hesapları",
  20: "Onaylı Twitter Hesapları",
  21: "TikTok Hesapları",
  22: "Mail Hesapları",
  27: "Telegram Hesapları",
} as const;

export const CATEGORY_ICONS: Readonly<Record<number, string>> = {
  7: "fas fa-user-check",
  8: "fas fa-shield-alt",
  12: "fas fa-history",
  13: "fab fa-facebook",
  14: "fas fa-store",
  15: "fab fa-facebook-f",
  16: "fab fa-instagram",
  20: "fab fa-x-twitter",
  21: "fab fa-tiktok",
  22: "fas fa-envelope",
  27: "fab fa-telegram",
} as const;

export type SiteSettingSeed = {
  settingKey: string;
  settingValue: string;
  settingType: string;
  description: string;
  category: string;
  orderIndex: number;
};

const HERO_STATS =
  '[{"number":"60.620+","label":"Satılan Hesap"},{"number":"1.300+","label":"Stokta Hesap"},{"number":"9.281+","label":"Mutlu Müşteri"},{"number":"99.8%","label":"Memnuniyet Oranı"}]';

const HEADER_MENU = JSON.stringify([
  { name: "Anasayfa", url: "/", icon: "fas fa-home" },
  { name: "Hesaplar", url: "/tum-hesaplar", icon: "fas fa-shopping-cart" },
  { name: "Hizmetler", url: "/hizmetler", icon: "fas fa-cogs" },
  { name: "SSS", url: "/sikca-sorulan-sorular", icon: "fas fa-question-circle" },
  { name: "İletişim", url: "/iletisim", icon: "fas fa-envelope" },
]);

const FOOTER_LINKS = JSON.stringify([
  {
    title: "Hızlı Erişim",
    links: [
      { name: "Anasayfa", url: "/" },
      { name: "Tüm Hesaplar", url: "/tum-hesaplar" },
      { name: "Hizmetlerimiz", url: "/hizmetler" },
      { name: "SSS", url: "/sikca-sorulan-sorular" },
      { name: "İletişim", url: "/iletisim" },
    ],
  },
]);

const FOOTER_SOCIAL = JSON.stringify([
  { platform: "Instagram", url: "#", icon: "fab fa-instagram" },
  { platform: "Telegram", url: "#", icon: "fab fa-telegram" },
]);

export const SITE_SETTING_SEEDS: readonly SiteSettingSeed[] = [
  {
    settingKey: "site_title",
    settingValue: "YildizHesap",
    settingType: "text",
    description: "Site adı",
    category: "header",
    orderIndex: 1,
  },
  {
    settingKey: "site_logo",
    settingValue: "/images/logo.png",
    settingType: "text",
    description: "Logo dosya yolu",
    category: "header",
    orderIndex: 2,
  },
  {
    settingKey: "logo_type",
    settingValue: "image",
    settingType: "text",
    description: "Logo tipi (text veya image)",
    category: "header",
    orderIndex: 3,
  },
  {
    settingKey: "logo_subtext",
    settingValue: "PREMIUM HESAP PAZARI",
    settingType: "text",
    description: "Logo altı yazı",
    category: "header",
    orderIndex: 4,
  },
  {
    settingKey: "header_menu",
    settingValue: HEADER_MENU,
    settingType: "json",
    description: "Üst menü bağlantıları",
    category: "header",
    orderIndex: 5,
  },
  {
    settingKey: "hero_title",
    settingValue: "Premium Sosyal Medya Hesapları",
    settingType: "text",
    description: "Anasayfa hero başlığı",
    category: "hero",
    orderIndex: 1,
  },
  {
    settingKey: "hero_subtitle",
    settingValue: "En Kaliteli Sosyal Medya Hesapları",
    settingType: "text",
    description: "Anasayfa hero alt başlığı",
    category: "hero",
    orderIndex: 2,
  },
  {
    settingKey: "hero_description",
    settingValue:
      "YildizHesap güvencesiyle doğrulanmış, yüksek limitli ve güvenli hesapları keşfedin.",
    settingType: "textarea",
    description: "Anasayfa hero açıklaması",
    category: "hero",
    orderIndex: 3,
  },
  {
    settingKey: "hero_background",
    settingValue: "/images/mockup.png",
    settingType: "text",
    description: "Hero görseli",
    category: "hero",
    orderIndex: 4,
  },
  {
    settingKey: "hero_button_text",
    settingValue: "Hemen Satın Al",
    settingType: "text",
    description: "Hero buton metni",
    category: "hero",
    orderIndex: 5,
  },
  {
    settingKey: "hero_button_url",
    settingValue: "/tum-hesaplar",
    settingType: "text",
    description: "Hero buton bağlantısı",
    category: "hero",
    orderIndex: 6,
  },
  {
    settingKey: "hero_stats",
    settingValue: HERO_STATS,
    settingType: "json",
    description: "Hero istatistik kartları",
    category: "hero",
    orderIndex: 7,
  },
  {
    settingKey: "footer_description",
    settingValue:
      "Kaliteli sosyal medya hesapları ile işinizi büyütmeniz için buradayız. Güvenli alışverişin premium adresi.",
    settingType: "textarea",
    description: "Footer açıklaması",
    category: "footer",
    orderIndex: 1,
  },
  {
    settingKey: "footer_copyright",
    settingValue: "© 2025 YildizHesap.net - Tüm Hakları Saklıdır.",
    settingType: "text",
    description: "Footer telif metni",
    category: "footer",
    orderIndex: 2,
  },
  {
    settingKey: "footer_social",
    settingValue: FOOTER_SOCIAL,
    settingType: "json",
    description: "Footer sosyal medya bağlantıları",
    category: "footer",
    orderIndex: 3,
  },
  {
    settingKey: "footer_links",
    settingValue: FOOTER_LINKS,
    settingType: "json",
    description: "Footer bağlantı grupları",
    category: "footer",
    orderIndex: 4,
  },
  {
    settingKey: "contact_email",
    settingValue: "info@yildizhesap.net",
    settingType: "text",
    description: "İletişim e-postası",
    category: "general",
    orderIndex: 1,
  },
  {
    settingKey: "contact_phone",
    settingValue: "+90 506 545 56 54",
    settingType: "text",
    description: "İletişim telefonu",
    category: "general",
    orderIndex: 2,
  },
  {
    settingKey: "contact_whatsapp",
    settingValue: "+905065455654",
    settingType: "text",
    description: "WhatsApp numarası",
    category: "general",
    orderIndex: 3,
  },
  {
    settingKey: "contact_telegram",
    settingValue: "@yildizhesap",
    settingType: "text",
    description: "Telegram kullanıcı adı",
    category: "general",
    orderIndex: 4,
  },
  {
    settingKey: "contact_address",
    settingValue: "İstanbul, Türkiye",
    settingType: "text",
    description: "İletişim adresi",
    category: "general",
    orderIndex: 5,
  },
  {
    settingKey: "theme_primary_color",
    settingValue: "#6c63ff",
    settingType: "color",
    description: "Ana tema rengi",
    category: "theme",
    orderIndex: 1,
  },
  {
    settingKey: "theme_accent_color",
    settingValue: "#42e2b8",
    settingType: "color",
    description: "Vurgu rengi",
    category: "theme",
    orderIndex: 2,
  },
  {
    settingKey: "site_theme_color",
    settingValue: "#6c63ff",
    settingType: "color",
    description: "Tarayıcı arayüz rengi",
    category: "theme",
    orderIndex: 3,
  },
  {
    settingKey: "max_accounts_per_ip",
    settingValue: "1",
    settingType: "number",
    description: "IP başına maksimum hesap alımı",
    category: "security",
    orderIndex: 1,
  },
  {
    settingKey: "ip_limit_days",
    settingValue: "365",
    settingType: "number",
    description: "IP limitinin geçerlilik süresi (gün)",
    category: "security",
    orderIndex: 2,
  },
  {
    settingKey: "ip_limit_enabled",
    settingValue: "1",
    settingType: "boolean",
    description: "IP limiti aktif mi",
    category: "security",
    orderIndex: 3,
  },
  {
    settingKey: "ban_page_title",
    settingValue: "Erişim Engellendi",
    settingType: "text",
    description: "Ban sayfası başlığı",
    category: "security",
    orderIndex: 4,
  },
  {
    settingKey: "ban_page_message",
    settingValue:
      "IP adresiniz sistem yöneticisi tarafından engellenmiştir.",
    settingType: "textarea",
    description: "Ban sayfası mesajı",
    category: "security",
    orderIndex: 5,
  },
  {
    settingKey: "ban_page_contact",
    settingValue:
      "Destek için info@yildizhesap.net adresine yazabilirsiniz.",
    settingType: "textarea",
    description: "Ban sayfası iletişim metni",
    category: "security",
    orderIndex: 6,
  },
  {
    settingKey: "welcome_message_enabled",
    settingValue: "1",
    settingType: "boolean",
    description: "Karşılama mesajı aktif mi",
    category: "general",
    orderIndex: 6,
  },
  {
    settingKey: "welcome_message_title",
    settingValue: "Hoşgeldin!",
    settingType: "text",
    description: "Karşılama mesajı başlığı",
    category: "general",
    orderIndex: 7,
  },
  {
    settingKey: "welcome_message_content",
    settingValue:
      "Hesabın başarıyla oluşturuldu ve otomatik giriş yapıldı. Şimdi premium hesapları inceleyebilirsin!",
    settingType: "textarea",
    description: "Karşılama mesajı içeriği",
    category: "general",
    orderIndex: 8,
  },
] as const;
