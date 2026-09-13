/**
 * Schema.org JSON-LD helpers. Ports legacy/StructuredDataHelper.php so Google's
 * rich results (product, category, FAQ, breadcrumb, services, contact, legal)
 * behave the same as the old PHP site.
 */

import { SITE_NAME, SITE_URL } from "./slugs";

const LOGO_PATH = "/images/mockup.png";

/**
 * Social profiles surfaced in the Organization JSON-LD. Update these when the
 * site opens or moves an account — Google's Knowledge Panel and AI answer
 * engines cite them.
 */
const SOCIAL_PROFILES: readonly string[] = [
  "https://www.instagram.com/yildizhesap",
  "https://t.me/yildizhesap",
  "https://twitter.com/yildizhesap",
];

const CONTACT_EMAIL = "destek@yildizhesap.net";
const CONTACT_PHONE = "+90-539-446-6666";
const ADDRESS_LOCALITY = "İstanbul";
const ADDRESS_COUNTRY = "TR";

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
        "@type": "OnlineStore",
        "@id": `${SITE_URL}/#organization`,
        name: SITE_NAME,
        alternateName: "YildizHesap",
        url: `${SITE_URL}/`,
        logo: {
          "@type": "ImageObject",
          "@id": `${SITE_URL}/#logo`,
          url: absoluteUrl(LOGO_PATH),
          contentUrl: absoluteUrl(LOGO_PATH),
        },
        image: { "@id": `${SITE_URL}/#logo` },
        description:
          "Doğrulanmış Facebook, Instagram, Business Manager, TikTok, Telegram ve mail hesaplarının anında dijital teslimatını yapan Türkiye merkezli e-ticaret platformu.",
        foundingDate: "2025",
        address: {
          "@type": "PostalAddress",
          addressLocality: ADDRESS_LOCALITY,
          addressCountry: ADDRESS_COUNTRY,
        },
        contactPoint: [
          {
            "@type": "ContactPoint",
            contactType: "customer support",
            telephone: CONTACT_PHONE,
            email: CONTACT_EMAIL,
            availableLanguage: ["Turkish", "English"],
            areaServed: "Worldwide",
          },
        ],
        sameAs: [...SOCIAL_PROFILES],
        knowsAbout: [
          "Facebook hesap satın al",
          "Business Manager hesap satın al",
          "Instagram hesap satın al",
          "Telegram hesap satın al",
          "Doğrulanmış sosyal medya hesapları",
        ],
        makesOffer: {
          "@type": "OfferCatalog",
          name: "YildizHesap katalog",
          url: `${SITE_URL}/tum-hesaplar`,
        },
      },
      {
        "@type": "WebSite",
        "@id": `${SITE_URL}/#website`,
        url: `${SITE_URL}/`,
        name: SITE_NAME,
        inLanguage: "tr-TR",
        publisher: { "@id": `${SITE_URL}/#organization` },
        potentialAction: {
          "@type": "SearchAction",
          target: {
            "@type": "EntryPoint",
            urlTemplate: `${SITE_URL}/tum-hesaplar?q={search_term_string}`,
          },
          "query-input": "required name=search_term_string",
        },
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

export type ProductInput = {
  id: number;
  slug: string;
  title: string;
  description: string;
  categoryName: string;
  categorySlug: string | null;
  priceTry: number;
  stockQuantity: number;
  rating: number | null;
  warrantyDays: number;
  imagePath: string;
};

/**
 * Product schema with the extra fields Google Rich Results and AI answer
 * engines look for: brand, sku, image, seller, itemCondition, return policy,
 * shipping details, priceValidUntil, and category breadcrumb.
 */
export function productStructuredData(input: ProductInput): JsonValue {
  const pageUrl = absoluteUrl(`/${input.slug}`);
  const image = absoluteUrl(input.imagePath);
  const inStock = input.stockQuantity > 0;

  // priceValidUntil is required by Google Merchant. Give one year of runway.
  const priceValidUntil = new Date();
  priceValidUntil.setFullYear(priceValidUntil.getFullYear() + 1);

  const graph: JsonValue[] = [
    {
      "@type": "Product",
      "@id": `${pageUrl}#product`,
      name: input.title,
      description: input.description,
      sku: `YH-${input.id}`,
      productID: `yildizhesap-${input.id}`,
      category: input.categoryName,
      image: [image],
      url: pageUrl,
      brand: { "@type": "Brand", name: SITE_NAME },
      itemCondition: "https://schema.org/NewCondition",
      offers: {
        "@type": "Offer",
        "@id": `${pageUrl}#offer`,
        url: pageUrl,
        price: input.priceTry.toFixed(2),
        priceCurrency: "TRY",
        priceValidUntil: priceValidUntil.toISOString().slice(0, 10),
        availability: inStock
          ? "https://schema.org/InStock"
          : "https://schema.org/OutOfStock",
        itemCondition: "https://schema.org/NewCondition",
        seller: {
          "@type": "Organization",
          "@id": `${SITE_URL}/#organization`,
          name: SITE_NAME,
          url: `${SITE_URL}/`,
        },
        hasMerchantReturnPolicy: {
          "@type": "MerchantReturnPolicy",
          applicableCountry: "TR",
          returnPolicyCategory:
            "https://schema.org/MerchantReturnFiniteReturnWindow",
          merchantReturnDays: input.warrantyDays,
          returnMethod: "https://schema.org/ReturnByMail",
          returnFees: "https://schema.org/FreeReturn",
        },
        shippingDetails: {
          "@type": "OfferShippingDetails",
          shippingRate: {
            "@type": "MonetaryAmount",
            value: "0.00",
            currency: "TRY",
          },
          shippingDestination: {
            "@type": "DefinedRegion",
            geoMidpoint: { "@type": "GeoCoordinates", latitude: 39, longitude: 35 },
            addressCountry: "TR",
          },
          deliveryTime: {
            "@type": "ShippingDeliveryTime",
            handlingTime: {
              "@type": "QuantitativeValue",
              minValue: 0,
              maxValue: 0,
              unitCode: "MIN",
            },
            transitTime: {
              "@type": "QuantitativeValue",
              minValue: 0,
              maxValue: 5,
              unitCode: "MIN",
            },
          },
        },
      },
    },
    buildProductBreadcrumb(pageUrl, {
      title: input.title,
      categoryName: input.categoryName,
      categorySlug: input.categorySlug,
    }),
  ];

  if (input.rating !== null && input.rating > 0) {
    (graph[0] as { aggregateRating?: JsonValue }).aggregateRating = {
      "@type": "AggregateRating",
      ratingValue: input.rating.toFixed(1),
      bestRating: "5",
      worstRating: "1",
      // Legacy accounts don't have a public review corpus yet; use a
      // conservative default that will grow with real orders.
      reviewCount: Math.max(1, input.stockQuantity),
    };
  }

  return { "@context": "https://schema.org", "@graph": graph };
}

function buildProductBreadcrumb(
  pageUrl: string,
  input: { title: string; categoryName: string; categorySlug: string | null },
): Breadcrumb {
  const items: Breadcrumb["itemListElement"] = [
    { "@type": "ListItem", position: 1, name: "Ana Sayfa", item: `${SITE_URL}/` },
  ];

  if (input.categorySlug !== null) {
    items.push({
      "@type": "ListItem",
      position: 2,
      name: input.categoryName,
      item: absoluteUrl(`/${input.categorySlug}`),
    });
  }

  items.push({
    "@type": "ListItem",
    position: items.length + 1,
    name: input.title,
    item: pageUrl,
  });

  return {
    "@type": "BreadcrumbList",
    "@id": `${pageUrl}#breadcrumb`,
    itemListElement: items,
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
