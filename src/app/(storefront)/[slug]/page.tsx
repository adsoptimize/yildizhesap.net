import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { AddToCartForm } from "@/components/AddToCartForm";
import { Breadcrumbs, type Crumb } from "@/components/Breadcrumbs";
import { ContactForm } from "@/components/ContactForm";
import { FaqAccordion } from "@/components/FaqAccordion";
import { ProductGrid } from "@/components/ProductGrid";
import { PurchaseSteps } from "@/components/PurchaseSteps";
import { RelatedProducts } from "@/components/RelatedProducts";
import {
  getActiveFaqs,
  getActiveProducts,
  getCategoryBySeoSlug,
  getContactSettings,
  getLegalPage,
  getPrerenderableSlugs,
  getProductBySeoSlug,
  getProductsByCategoryId,
  getRelatedProducts,
  getStorefrontCategories,
  type CategoryCard,
  type ProductCard,
  type ProductDetail,
  type SettingsMap,
} from "@/lib/db/queries";
import {
  ALL_ACCOUNTS_META,
  CATEGORY_META,
  PRODUCT_META_DESCRIPTIONS,
  PRODUCT_META_KEYWORDS,
  PRODUCT_META_TITLES,
  STATIC_PAGE_META,
} from "@/lib/seo/meta";
import { STATIC_SEO_ROUTES } from "@/lib/seo/slugs";
import {
  ALL_ACCOUNTS_AI_META,
  aiMeta,
  categoryAiMeta,
  legalAiMeta,
  productAiMeta,
  STATIC_PAGE_AI_META,
} from "@/lib/seo/ai-tags";
import { pageOpenGraph, productFactMeta } from "@/lib/seo/open-graph";
import {
  categoryStructuredData,
  contactStructuredData,
  faqStructuredData,
  howToPurchaseStructuredData,
  legalPageStructuredData,
  productStructuredData,
  serializeJsonLd,
  servicesStructuredData,
} from "@/lib/seo/structured-data";
import { getSetting } from "@/lib/settings";

type PageProps = {
  params: Promise<{ slug: string }>;
};

// Segment config must be a literal; keep in sync with the other storefront routes.
export const revalidate = 300;

/** Legacy legal routes map onto legal_pages.page_type values. */
const LEGAL_ROUTES: Readonly<Record<string, string>> = {
  kvkk: "kvvk",
  "gizlilik-politikasi": "privacy",
  "kullanim-kosullari": "terms",
  "cerez-politikasi": "cookies",
  "iade-politikasi": "refund",
  hakkimizda: "about",
};

const ALL_ACCOUNTS_SLUG = "tum-hesaplar";

/** Slugs that now have their own route segment with forms and server actions. */
const DEDICATED_ROUTE_SLUGS: readonly string[] = [
  "giris-yap",
  "kayit-ol",
  "siparis-takip",
];

function formatPrice(value: string): string {
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency: "TRY",
    maximumFractionDigits: 0,
  }).format(Number(value));
}

export async function generateStaticParams() {
  const dynamicSlugs = await getPrerenderableSlugs();

  return [
    ...STATIC_SEO_ROUTES.filter(
      (slug) => !DEDICATED_ROUTE_SLUGS.includes(slug),
    ).map((slug) => ({ slug })),
    ...dynamicSlugs.map((slug) => ({ slug })),
  ];
}

export async function generateMetadata({
  params,
}: PageProps): Promise<Metadata> {
  const { slug } = await params;

  if (slug === ALL_ACCOUNTS_SLUG) {
    return {
      title: { absolute: ALL_ACCOUNTS_META.title },
      description: ALL_ACCOUNTS_META.description,
      alternates: { canonical: `/${slug}` },
      ...pageOpenGraph({
        title: ALL_ACCOUNTS_META.title,
        description: ALL_ACCOUNTS_META.description,
        slug,
      }),
      other: aiMeta(ALL_ACCOUNTS_AI_META),
    };
  }

  const category = await getCategoryBySeoSlug(slug);
  if (category !== null) {
    const meta = CATEGORY_META[category.id] ?? {
      title: category.name,
      description: category.description ?? ALL_ACCOUNTS_META.description,
    };
    return {
      title: { absolute: meta.title },
      description: meta.description,
      alternates: { canonical: `/${slug}` },
      ...pageOpenGraph({
        title: meta.title,
        description: meta.description,
        slug,
      }),
      other: aiMeta(categoryAiMeta({ categoryName: category.name })),
    };
  }

  const product = await getProductBySeoSlug(slug);
  if (product !== null) {
    const description =
      PRODUCT_META_DESCRIPTIONS[product.id] ??
      product.description ??
      `${product.title} - güvenli ve hızlı teslimat.`;
    const keywords = PRODUCT_META_KEYWORDS[product.id];
    const title = PRODUCT_META_TITLES[product.id] ?? product.title;

    return {
      title: { absolute: title },
      description,
      keywords: keywords === undefined ? undefined : keywords,
      alternates: { canonical: `/${slug}` },
      ...pageOpenGraph({
        title,
        description,
        slug,
        // Product image is the generic mockup until per-product art exists;
        // `article` picks that asset instead of the site logo.
        type: "article",
      }),
      other: {
        ...aiMeta(productAiMeta({ title, categoryName: product.categoryName })),
        ...productFactMeta({
          priceTry: Number(product.price),
          stockQuantity: product.stockQuantity,
        }),
      },
    };
  }

  const staticMeta = STATIC_PAGE_META[slug];
  if (staticMeta !== undefined) {
    const staticAi = STATIC_PAGE_AI_META[slug];
    return {
      title: { absolute: staticMeta.title },
      description: staticMeta.description,
      alternates: { canonical: `/${slug}` },
      ...pageOpenGraph({
        title: staticMeta.title,
        description: staticMeta.description,
        slug,
      }),
      ...(staticAi === undefined ? {} : { other: aiMeta(staticAi) }),
    };
  }

  const legalPageType = LEGAL_ROUTES[slug];
  if (legalPageType !== undefined) {
    const page = await getLegalPage(legalPageType);
    if (page !== null) {
      const description =
        page.metaDescription ?? "Yasal bilgilendirme sayfası";
      return {
        title: { absolute: page.title },
        description,
        alternates: { canonical: `/${slug}` },
        ...pageOpenGraph({
          title: page.title,
          description,
          slug,
          type: "article",
        }),
        other: aiMeta(legalAiMeta({ title: page.title })),
      };
    }
  }

  return { title: "Sayfa Bulunamadı" };
}

function CategoryStrip({ categories }: { categories: CategoryCard[] }) {
  return (
    <div style={{ marginTop: 30 }}>
      <div className="categories-grid">
        {categories.map((category) => (
          <Link
            key={category.id}
            href={`/${category.seoSlug}`}
            className="category-card"
            style={{ textDecoration: "none", color: "inherit" }}
          >
            <div className="category-header">
              <div className="category-icon">
                <i className={category.icon} />
              </div>
              <h3>{category.name}</h3>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}

function CatalogPage({
  title,
  description,
  products,
  categories,
  currentCategoryId,
  jsonLd,
  breadcrumbs,
}: {
  title: string;
  description: string;
  products: ProductCard[];
  categories: CategoryCard[];
  currentCategoryId?: number;
  jsonLd?: string;
  breadcrumbs?: readonly Crumb[];
}) {
  return (
    <main>
      {jsonLd === undefined ? null : (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: jsonLd }}
        />
      )}
      <section className="page-header compact">
        <div className="container">
          {breadcrumbs === undefined ? null : (
            <Breadcrumbs items={breadcrumbs} />
          )}
          <h1>{title}</h1>
          <p>{description}</p>
        </div>
      </section>
      <section className="accounts-section">
        <div className="container">
          <ProductGrid
            products={products}
            categories={categories}
            currentCategoryId={currentCategoryId}
          />
        </div>
      </section>
      <section className="accounts-section" style={{ paddingTop: 0 }}>
        <div className="container">
          <CategoryStrip categories={categories} />
        </div>
      </section>
    </main>
  );
}

function ProductPage({
  product,
  related,
}: {
  product: ProductDetail;
  related: readonly ProductCard[];
}) {
  const title = PRODUCT_META_TITLES[product.id] ?? product.title;
  const description =
    PRODUCT_META_DESCRIPTIONS[product.id] ??
    product.description ??
    `${product.title} - güvenli ve hızlı teslimat.`;

  const breadcrumbs: Crumb[] = [
    { label: "Tüm Hesaplar", href: `/${ALL_ACCOUNTS_SLUG}` },
    ...(product.categorySeoSlug === null
      ? []
      : [
          {
            label: product.categoryName,
            href: `/${product.categorySeoSlug}`,
          },
        ]),
    { label: title },
  ];

  const jsonLd = serializeJsonLd(
    productStructuredData({
      id: product.id,
      slug: product.seoSlug ?? "",
      title,
      description,
      categoryName: product.categoryName,
      categorySlug: product.categorySeoSlug,
      priceTry: Number(product.price),
      stockQuantity: product.stockQuantity,
      rating: product.rating,
      warrantyDays: product.warrantyDays,
      imagePath: "/images/mockup.png",
    }),
  );

  return (
    <main>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: jsonLd }}
      />
      <section className="accounts-section">
        <div className="container">
          <Breadcrumbs items={breadcrumbs} />
          <div className="account-details">
            <div className="account-detail-card">
              <div className="detail-header">
                <div className="detail-avatar">
                  <i className="fas fa-user-shield" />
                </div>
                <div className="detail-title">
                  <h1>{title}</h1>
                  <p>{product.accountType}</p>
                  <div className="detail-badges">
                    {product.isVerified ? (
                      <span className="badge verified">
                        <i className="fas fa-check-circle" /> Doğrulanmış
                      </span>
                    ) : null}
                    <span className="badge stock-available">
                      <i className="fas fa-box" /> Stok: {product.stockQuantity}
                    </span>
                    {product.limitInfo === null ? null : (
                      <span className="badge">
                        <i className="fas fa-gauge-high" /> {product.limitInfo}
                      </span>
                    )}
                  </div>
                </div>
              </div>
              <div className="detail-body">
                <div className="price-section">
                  <div className="price-info">
                    <span className="current-price">
                      {formatPrice(product.price)}
                    </span>
                    {product.oldPrice === null ? null : (
                      <span className="old-price">
                        {formatPrice(product.oldPrice)}
                      </span>
                    )}
                  </div>
                  {product.instantDelivery ? (
                    <div className="stock-info">
                      <i className="fas fa-bolt" /> Anında teslimat
                    </div>
                  ) : null}
                </div>

                {product.description === null ? null : (
                  <div className="description-section">
                    <h2>
                      <i className="fas fa-info-circle" /> Açıklama
                    </h2>
                    {product.description
                      .split(/\n{2,}/)
                      .map((paragraph, index) => (
                        <p key={`${product.id}-p${index}`}>{paragraph}</p>
                      ))}
                  </div>
                )}

                {product.features.length === 0 ? null : (
                  <div className="description-section">
                    <h2>
                      <i className="fas fa-list" /> Özellikler
                    </h2>
                    <ul>
                      {product.features.map((feature) => (
                        <li key={`${feature.featureName}-${feature.featureValue}`}>
                          <strong>{feature.featureName}:</strong>{" "}
                          {feature.featureValue}
                        </li>
                      ))}
                    </ul>
                  </div>
                )}

                <div className="purchase-section">
                  <AddToCartForm
                    accountId={product.id}
                    availableStock={product.availableStock}
                    variant="detail"
                  />
                  <div className="security-info">
                    <div className="security-item">
                      <i className="fas fa-shield-alt" />
                      Güvenli ödeme
                    </div>
                    <div className="security-item">
                      <i className="fas fa-headset" />
                      {product.support247 ? "7/24 destek" : "Destek"}
                    </div>
                    <div className="security-item">
                      <i className="fas fa-undo" />
                      {product.warrantyDays} gün garanti
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <RelatedProducts
            products={related}
            categoryName={product.categoryName}
            categorySlug={product.categorySeoSlug}
          />
        </div>
      </section>
    </main>
  );
}

function StaticContentPage({
  title,
  description,
  children,
  jsonLd,
  breadcrumbLabel,
}: {
  title: string;
  description: string;
  children: React.ReactNode;
  jsonLd?: string;
  /** Short label for the trail; page titles are usually too long for it. */
  breadcrumbLabel?: string;
}) {
  return (
    <main>
      {jsonLd === undefined ? null : (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: jsonLd }}
        />
      )}
      <section className="page-header compact">
        <div className="container">
          {breadcrumbLabel === undefined ? null : (
            <Breadcrumbs items={[{ label: breadcrumbLabel }]} />
          )}
          <h1>{title}</h1>
          <p>{description}</p>
        </div>
      </section>
      <section className="section" style={{ paddingTop: 40 }}>
        <div className="container">{children}</div>
      </section>
    </main>
  );
}

function ContactPage({ contact }: { contact: SettingsMap }) {
  const meta = STATIC_PAGE_META.iletisim;
  const whatsapp = getSetting(contact, "whatsapp_number", "");
  const email = getSetting(contact, "email_address", "");
  const telegram = getSetting(contact, "telegram_username", "");
  const officeHours = getSetting(contact, "office_hours_content", "");

  const jsonLd = serializeJsonLd(
    contactStructuredData({ email, phone: whatsapp }),
  );

  return (
    <StaticContentPage
      title={getSetting(contact, "page_title", meta.title)}
      description={getSetting(contact, "page_description", meta.description)}
      breadcrumbLabel="İletişim"
      jsonLd={jsonLd}
    >
      <div className="features-grid">
        {whatsapp === "" ? null : (
          <div className="feature-card">
            <div className="feature-icon">
              <i className="fab fa-whatsapp" />
            </div>
            <h3>{getSetting(contact, "whatsapp_text", "WhatsApp")}</h3>
            <p>{getSetting(contact, "whatsapp_description", "")}</p>
            <p>
              <a href={`https://wa.me/${whatsapp.replace(/[^0-9]/g, "")}`}>
                {whatsapp}
              </a>
            </p>
          </div>
        )}
        {email === "" ? null : (
          <div className="feature-card">
            <div className="feature-icon">
              <i className="fas fa-envelope" />
            </div>
            <h3>{getSetting(contact, "email_text", "E-posta")}</h3>
            <p>{getSetting(contact, "email_description", "")}</p>
            <p>
              <a href={`mailto:${email}`}>{email}</a>
            </p>
          </div>
        )}
        {telegram === "" ? null : (
          <div className="feature-card">
            <div className="feature-icon">
              <i className="fab fa-telegram" />
            </div>
            <h3>{getSetting(contact, "telegram_text", "Telegram")}</h3>
            <p>{getSetting(contact, "telegram_description", "")}</p>
            <p>
              <a href={`https://t.me/${telegram.replace(/^@/, "")}`}>
                @{telegram.replace(/^@/, "")}
              </a>
            </p>
          </div>
        )}
        {officeHours === "" ? null : (
          <div className="feature-card">
            <div className="feature-icon">
              <i className="fas fa-clock" />
            </div>
            <h3>{getSetting(contact, "office_hours_title", "Çalışma Saatleri")}</h3>
            <p>{officeHours}</p>
          </div>
        )}
      </div>
      <div className="contact-form-wrap">
        <div className="section-title" style={{ marginBottom: 20 }}>
          <h2>Bize Mesaj Gönderin</h2>
          <p>Mesajınız Telegram üzerinden ekibimize anında iletilir.</p>
        </div>
        <ContactForm />
      </div>
    </StaticContentPage>
  );
}

export default async function SeoSlugPage({ params }: PageProps) {
  const { slug } = await params;

  if (slug === ALL_ACCOUNTS_SLUG) {
    const [products, categories] = await Promise.all([
      getActiveProducts(),
      getStorefrontCategories(),
    ]);
    return (
      <CatalogPage
        title={ALL_ACCOUNTS_META.title}
        description={ALL_ACCOUNTS_META.description}
        products={products}
        categories={categories}
        breadcrumbs={[{ label: "Tüm Hesaplar" }]}
        jsonLd={serializeJsonLd(
          categoryStructuredData({
            slug: ALL_ACCOUNTS_SLUG,
            name: ALL_ACCOUNTS_META.title,
            description: ALL_ACCOUNTS_META.description,
            products,
          }),
        )}
      />
    );
  }

  const category = await getCategoryBySeoSlug(slug);
  if (category !== null) {
    const [products, categories] = await Promise.all([
      getProductsByCategoryId(category.id),
      getStorefrontCategories(),
    ]);
    const meta = CATEGORY_META[category.id];
    const categoryTitle = meta?.title ?? category.name;
    const categoryDescription =
      meta?.description ??
      category.description ??
      ALL_ACCOUNTS_META.description;

    return (
      <CatalogPage
        title={categoryTitle}
        description={categoryDescription}
        products={products}
        categories={categories}
        currentCategoryId={category.id}
        breadcrumbs={[
          { label: "Tüm Hesaplar", href: `/${ALL_ACCOUNTS_SLUG}` },
          { label: category.name },
        ]}
        jsonLd={serializeJsonLd(
          categoryStructuredData({
            slug,
            name: categoryTitle,
            description: categoryDescription,
            products,
          }),
        )}
      />
    );
  }

  const product = await getProductBySeoSlug(slug);
  if (product !== null) {
    const related = await getRelatedProducts({
      categoryId: product.categoryId,
      excludeId: product.id,
    });
    return <ProductPage product={product} related={related} />;
  }

  if (slug === "sikca-sorulan-sorular") {
    const [meta, faqs] = [STATIC_PAGE_META[slug], await getActiveFaqs()];
    return (
      <StaticContentPage
        title={meta.title}
        description={meta.description}
        breadcrumbLabel="Sıkça Sorulan Sorular"
        // Top-level array is valid JSON-LD; keeps FAQPage and HowTo as two
        // independent graphs rather than nesting one inside the other.
        jsonLd={serializeJsonLd([
          faqStructuredData(faqs),
          howToPurchaseStructuredData(),
        ])}
      >
        <FaqAccordion faqs={faqs} />
        <PurchaseSteps />
      </StaticContentPage>
    );
  }

  if (slug === "hizmetler") {
    const meta = STATIC_PAGE_META[slug];
    const categories = await getStorefrontCategories();
    return (
      <StaticContentPage
        title={meta.title}
        description={meta.description}
        breadcrumbLabel="Hizmetler"
        jsonLd={serializeJsonLd(servicesStructuredData(categories))}
      >
        <div className="services-grid">
          {categories.map((item) => (
            <div className="service-card facebook" key={item.id}>
              <div className="service-header">
                <div className="service-icon">
                  <i className={item.icon} />
                </div>
                <div className="service-info">
                  <h3>{item.name}</h3>
                  <p>{item.description}</p>
                </div>
              </div>
              <div className="service-body">
                <div className="service-action">
                  <Link href={`/${item.seoSlug}`} className="btn-primary">
                    İncele
                  </Link>
                </div>
              </div>
            </div>
          ))}
        </div>
      </StaticContentPage>
    );
  }

  if (slug === "iletisim") {
    const contact = await getContactSettings();
    return <ContactPage contact={contact} />;
  }

  const legalPageType = LEGAL_ROUTES[slug];
  if (legalPageType !== undefined) {
    const page = await getLegalPage(legalPageType);
    if (page !== null) {
      return (
        <StaticContentPage
          title={page.title}
          description={page.metaDescription ?? ""}
          breadcrumbLabel={page.title}
          jsonLd={serializeJsonLd(
            legalPageStructuredData({
              slug,
              title: page.title,
              description: page.metaDescription,
            }),
          )}
        >
          <div
            className="legal-content"
            dangerouslySetInnerHTML={{ __html: page.content }}
          />
        </StaticContentPage>
      );
    }
  }

  notFound();
}
