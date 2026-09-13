import { CATEGORY_SEO_SLUGS, PRODUCT_SEO_SLUGS } from "@/lib/seo/slugs";
import { CATEGORY_META, PRODUCT_META_TITLES } from "@/lib/seo/meta";

export type SeedCategory = {
  id: number;
  name: string;
  seoSlug: string;
  icon: string;
  description: string;
};

export type SeedProduct = {
  id: number;
  categoryId: number;
  title: string;
  seoSlug: string;
  description: string;
  price: number;
  oldPrice: number | null;
  stock: number;
  platform: string;
  isVerified: boolean;
  isPremium: boolean;
};

export type SeedFaq = {
  id: number;
  question: string;
  answer: string;
};

const CATEGORY_ICONS: Record<number, string> = {
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
};

const CATEGORY_NAMES: Record<number, string> = {
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
};

export const SEED_CATEGORIES: SeedCategory[] = Object.entries(
  CATEGORY_SEO_SLUGS,
).map(([id, seoSlug]) => {
  const numericId = Number(id);
  return {
    id: numericId,
    name: CATEGORY_NAMES[numericId] ?? seoSlug,
    seoSlug,
    icon: CATEGORY_ICONS[numericId] ?? "fas fa-folder",
    description:
      CATEGORY_META[numericId]?.description ??
      "Premium hesap seçenekleri, güvenli teslimat.",
  };
});

export const SEED_PRODUCTS: SeedProduct[] = Object.entries(
  PRODUCT_SEO_SLUGS,
).map(([id, seoSlug]) => {
  const numericId = Number(id);
  const title = PRODUCT_META_TITLES[numericId] ?? seoSlug;
  let categoryId = 15;
  if (numericId >= 35 && numericId <= 40) categoryId = 13;
  else if (numericId === 41) categoryId = 14;
  else if (numericId >= 42 && numericId <= 45) categoryId = 15;
  else if (numericId >= 46 && numericId <= 50) categoryId = 16;
  else if (numericId === 60) categoryId = 27;
  else if (numericId >= 12 && numericId <= 31) categoryId = 12;

  return {
    id: numericId,
    categoryId,
    title,
    seoSlug,
    description: `${title}. Anında teslimat, güvenli ödeme ve destek ile sunulur.`,
    price: 250 + (numericId % 20) * 50,
    oldPrice: numericId % 3 === 0 ? 400 + (numericId % 10) * 40 : null,
    stock: 5 + (numericId % 12),
    platform:
      categoryId === 16
        ? "Instagram"
        : categoryId === 27
          ? "Telegram"
          : "Facebook",
    isVerified: true,
    isPremium: numericId % 2 === 0,
  };
});

export const SEED_FAQS: SeedFaq[] = [
  {
    id: 1,
    question: "Hesaplar ne kadar sürede teslim ediliyor?",
    answer:
      "Tüm hesaplarımız satın alma işlemi tamamlandıktan sonra 5-15 dakika içerisinde otomatik olarak teslim edilmektedir. Bazı özel hesaplar için maksimum 1 saat sürebilir.",
  },
  {
    id: 2,
    question: "Hesaplar güvenli mi? Yasaklanma riski var mı?",
    answer:
      "Tüm hesaplarımız orijinal metotlarla oluşturulmuş olup, telefon ve e-posta doğrulaması yapılmıştır. Yasaklanma riski minimum seviyededir.",
  },
  {
    id: 3,
    question: "Hesap bilgilerini nasıl alacağım?",
    answer:
      "Satın alma işlemi tamamlandıktan sonra hesap bilgileri otomatik olarak panelinize ve e-posta adresinize düşer.",
  },
  {
    id: 4,
    question: "Para iadesi var mı?",
    answer:
      "Hesap tesliminden sonra 24 saat içerisinde hesapta sorun tespit edilirse %100 para iadesi yapılmaktadır.",
  },
  {
    id: 5,
    question: "Hangi ödeme yöntemlerini kabul ediyorsunuz?",
    answer:
      "Kredi kartı, banka kartı, Shopier ve Cryptomus (kripto) ile ödeme yapabilirsiniz.",
  },
  {
    id: 6,
    question: "7/24 destek hizmeti var mı?",
    answer:
      "Evet, 7 gün 24 saat canlı destek hizmeti sunmaktayız. WhatsApp, Telegram ve iletişim formu üzerinden ulaşabilirsiniz.",
  },
];

export const SITE_DEFAULTS = {
  title: "YildizHesap",
  logoSubtext: "PREMIUM HESAP PAZARI",
  heroTitle: "Premium Sosyal Medya Hesapları",
  heroDescription:
    "BusinessHesap güvencesiyle doğrulanmış, yüksek limitli ve güvenli hesapları keşfedin.",
  heroButtonText: "Hemen Satın Al",
  heroButtonUrl: "/tum-hesaplar",
  heroImage: "/images/mockup.png",
  heroStats: [
    { number: "60.620+", label: "Satılan Hesap" },
    { number: "1.300+", label: "Stokta Hesap" },
    { number: "9.281+", label: "Mutlu Müşteri" },
    { number: "99.8%", label: "Memnuniyet Oranı" },
  ],
  contactPhone: "+90 555 333 33 33",
  contactEmail: "info@yildizhesap.net",
  contactWhatsapp: "+905553333333",
  contactAddress: "İstanbul, Türkiye",
};

export function getCategoryBySlug(slug: string): SeedCategory | undefined {
  return SEED_CATEGORIES.find((category) => category.seoSlug === slug);
}

export function getProductBySlug(slug: string): SeedProduct | undefined {
  return SEED_PRODUCTS.find((product) => product.seoSlug === slug);
}

export function getProductsByCategoryId(categoryId: number): SeedProduct[] {
  return SEED_PRODUCTS.filter((product) => product.categoryId === categoryId);
}
