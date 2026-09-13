/**
 * Schema.org JSON-LD helpers. Ports legacy/StructuredDataHelper.php so Google's
 * rich results (product, category, FAQ, breadcrumb, services, contact, legal)
 * behave the same as the old PHP site.
 */

import { SITE_NAME, SITE_URL } from "./slugs";

const LOGO_PATH = "/images/mockup.png";

type BreadcrumbLanguage = "tr" | "en";

type JsonValue =
  | string
  | number
  | boolean
  | null
  | JsonValue[]
  | { [key: string]: JsonValue };

type Breadcrumb = {
  "@type": "BreadcrumbList";
  "@id": string;
  itemListElement: Array<{
    "@type": "ListItem";
    position: number;
    name: string;
    item: string;
  }>;
};

function absoluteUrl(pathname: string): string {
  return `${SITE_URL}${pathname.startsWith("/") ? pathname : `/${pathname}`}`;
}

function buildBreadcrumb(
  pageUrl: string,
  pageName: string,
  language: BreadcrumbLanguage = "tr",
): Breadcrumb {
  const homeLabel = language === "en" ? "Home" : "Ana Sayfa";

  return {
    "@type": "BreadcrumbList",
    "@id": `${pageUrl}#breadcrumb`,
    itemListElement: [
      { "@type": "ListItem", position: 1, name: homeLabel, item: `${SITE_URL}/` },
      { "@type": "ListItem", position: 2, name: pageName, item: pageUrl },
    ],
  };
}

/** Home graph: Organization + WebSite with SearchAction. */
export function homepageStructuredData(): JsonValue {
  return {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Organization",
        "@id": `${SITE_URL}/#organization`,
        name: SITE_NAME,
        url: `${SITE_URL}/`,
        logo: { "@type": "ImageObject", url: absoluteUrl(LOGO_PATH) },
      },
      {
        "@type": "WebSite",
        "@id": `${SITE_URL}/#website`,
        url: `${SITE_URL}/`,
        name: SITE_NAME,
        inLanguage: "tr-TR",
        publisher: { "@id": `${SITE_URL}/#organization` },
      },
    ],
  };
}

export type CategoryProductSummary = {
  title: string;
};

const CATEGORY_DESCRIPTIONS: Readonly<Record<string, string>> = {
  "ana-hesaplar-dogrulanmis":
    "Kimlik doğrulaması yapılmış, ana hesap olarak kullanılan ve reklam ile Business Manager uyumlu Facebook ve sosyal medya hesapları.",
  "direncli-hesaplar":
    "Daha önce kısıt almış, itiraz veya inceleme sonrası yeniden aktif edilmiş doğrulanmış Facebook ana hesapları.",
  "business-manager":
    "Reklam verme, sayfa ve varlık yönetimi için kullanılan, doğrulanmış ve kullanıma hazır Business Manager hesapları.",
  "facebook-marketplace":
    "Doğrulanmış ve güvenli şekilde satılan Facebook Marketplace hesapları.",
  "facebook-hesaplari":
    "Doğrulanmış ve güvenli Facebook hesapları, yabancı ve Türk seçenekleri, kimlik onaylı seçenekler dahil.",
  "instagram-hesaplari":
    "Doğrulanmış Instagram hesapları, tanıtım onaylı ve takipçi sayısına göre çeşitlendirilmiş seçenekler.",
  "telegram-hesaplari": "Doğrulanmış Telegram hesapları.",
};

export function categoryStructuredData(input: {
  slug: string;
  name: string;
  description: string;
  products: CategoryProductSummary[];
}): JsonValue {
  const pageUrl = absoluteUrl(`/${input.slug}`);
  const description =
    CATEGORY_DESCRIPTIONS[input.slug] ?? input.description;

  return {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "WebPage",
        "@id": `${pageUrl}#webpage`,
        url: pageUrl,
        name: input.name,
        description,
        inLanguage: "tr-TR",
        isPartOf: { "@type": "WebSite", "@id": `${SITE_URL}/#website` },
        mainEntity: { "@id": `${pageUrl}#itemlist` },
      },
      {
        "@type": "ItemList",
        "@id": `${pageUrl}#itemlist`,
        name: input.name,
        description,
        itemListOrder: "https://schema.org/ItemListUnordered",
        numberOfItems: input.products.length,
        itemListElement: input.products.map((product, index) => ({
          "@type": "ListItem",
          position: index + 1,
          name: product.title,
        })),
      },
      buildBreadcrumb(pageUrl, input.name),
    ],
  };
}

export type FaqEntry = { question: string; answer: string };

export function faqStructuredData(faqs: FaqEntry[]): JsonValue {
  const pageUrl = absoluteUrl("/sikca-sorulan-sorular");

  return {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "FAQPage",
        "@id": `${pageUrl}#faq`,
        mainEntity: faqs.map((faq) => ({
          "@type": "Question",
          name: faq.question,
          acceptedAnswer: { "@type": "Answer", text: faq.answer },
        })),
      },
      buildBreadcrumb(pageUrl, "Sıkça Sorulan Sorular"),
    ],
  };
}

export function servicesStructuredData(
  categories: Array<{ name: string }>,
): JsonValue {
  const pageUrl = absoluteUrl("/hizmetler");

  return {
    "@context": "https://schema.org",
    "@type": "Service",
    "@id": `${pageUrl}#service`,
    name: "Hizmetlerimiz",
    description:
      "Doğrulanmış Facebook, Instagram, Twitter ve diğer premium hesaplarla işlerinizi büyütün. Güvenli ödeme ve hızlı teslimat.",
    mainEntityOfPage: {
      "@type": "WebPage",
      "@id": pageUrl,
      url: pageUrl,
      name: "Hizmetlerimiz",
    },
    provider: {
      "@type": "Organization",
      name: SITE_NAME,
      url: `${SITE_URL}/`,
      logo: { "@type": "ImageObject", url: absoluteUrl(LOGO_PATH) },
    },
    areaServed: { "@type": "Country", name: "Global" },
    hasOfferCatalog: {
      "@type": "OfferCatalog",
      name: "Hizmetlerimiz Kapsamı",
      itemListElement: categories.map((category) => ({
        "@type": "Offer",
        itemOffered: {
          "@type": "Service",
          name: category.name,
          description: `Premium ${category.name} satış hizmetleri. Doğrulanmış hesaplar, yüksek kalite garantisi, anında teslimat ve 7/24 destek.`,
        },
      })),
    },
    breadcrumb: buildBreadcrumb(pageUrl, "Hizmetlerimiz"),
  };
}

export function contactStructuredData(input: {
  email: string;
  phone: string;
}): JsonValue {
  const pageUrl = absoluteUrl("/iletisim");

  return {
    "@context": "https://schema.org",
    "@type": "Organization",
    "@id": `${pageUrl}#organization`,
    name: SITE_NAME,
    url: pageUrl,
    logo: { "@type": "ImageObject", url: absoluteUrl(LOGO_PATH) },
    contactPoint: [
      {
        "@type": "ContactPoint",
        contactType: "Customer Support",
        name: "WhatsApp Destek",
        telephone: input.phone,
        availableLanguage: ["Turkish", "English"],
        areaServed: "Global",
      },
      {
        "@type": "ContactPoint",
        contactType: "Customer Support",
        name: "E-posta Destek",
        email: input.email,
        availableLanguage: ["Turkish", "English"],
        areaServed: "Global",
      },
    ],
    breadcrumb: buildBreadcrumb(pageUrl, "İletişim"),
  };
}

export function legalPageStructuredData(input: {
  slug: string;
  title: string;
  description: string | null;
}): JsonValue {
  const pageUrl = absoluteUrl(`/${input.slug}`);

  return {
    "@context": "https://schema.org",
    "@type": "WebPage",
    "@id": `${pageUrl}#webpage`,
    url: pageUrl,
    name: input.title,
    description: input.description ?? input.title,
    inLanguage: "tr",
    breadcrumb: buildBreadcrumb(pageUrl, input.title),
    publisher: {
      "@type": "Organization",
      name: SITE_NAME,
      logo: { "@type": "ImageObject", url: absoluteUrl(LOGO_PATH) },
    },
  };
}

export function serializeJsonLd(data: JsonValue): string {
  return JSON.stringify(data);
}
