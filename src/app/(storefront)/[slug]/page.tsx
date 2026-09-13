import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { FaqAccordion } from "@/components/FaqAccordion";
import {
  getCategoryBySlug,
  getProductBySlug,
  getProductsByCategoryId,
  SEED_CATEGORIES,
  SEED_FAQS,
  SEED_PRODUCTS,
  SITE_DEFAULTS,
} from "@/data/seed";
import {
  ALL_ACCOUNTS_META,
  CATEGORY_META,
  PRODUCT_META_TITLES,
  STATIC_PAGE_META,
} from "@/lib/seo/meta";
import {
  CATEGORY_SEO_SLUGS,
  PRODUCT_SEO_SLUGS,
  STATIC_SEO_ROUTES,
} from "@/lib/seo/slugs";

type PageProps = {
  params: Promise<{ slug: string }>;
};

function formatPrice(value: number): string {
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency: "TRY",
    maximumFractionDigits: 0,
  }).format(value);
}

export async function generateStaticParams() {
  return [
    ...STATIC_SEO_ROUTES.map((slug) => ({ slug })),
    ...Object.values(CATEGORY_SEO_SLUGS).map((slug) => ({ slug })),
    ...Object.values(PRODUCT_SEO_SLUGS).map((slug) => ({ slug })),
    { slug: "guest-purchase" },
  ];
}

export async function generateMetadata({
  params,
}: PageProps): Promise<Metadata> {
  const { slug } = await params;

  if (slug === "tum-hesaplar") {
    return {
      title: { absolute: ALL_ACCOUNTS_META.title },
      description: ALL_ACCOUNTS_META.description,
    };
  }

  const category = getCategoryBySlug(slug);
  if (category) {
    const meta = CATEGORY_META[category.id] ?? ALL_ACCOUNTS_META;
    return {
      title: { absolute: meta.title },
      description: meta.description,
    };
  }

  const product = getProductBySlug(slug);
  if (product) {
    const title = PRODUCT_META_TITLES[product.id] ?? product.title;
    return {
      title: { absolute: title },
      description: product.description,
    };
  }

  const staticMeta = STATIC_PAGE_META[slug];
  if (staticMeta) {
    return {
      title: { absolute: staticMeta.title },
      description: staticMeta.description,
    };
  }

  if (slug === "kvkk" || slug === "gizlilik-politikasi") {
    return {
      title: { absolute: slug === "kvkk" ? "KVKK Aydınlatma Metni" : "Gizlilik Politikası" },
      description: "Yasal bilgilendirme sayfası",
    };
  }

  if (slug === "guest-purchase") {
    return {
      title: { absolute: "Hesap Satın Al | Misafir Alışveriş" },
      description: "Üye olmadan hızlı hesap satın alın.",
    };
  }

  return { title: "Sayfa Bulunamadı" };
}

function CatalogPage({
  title,
  description,
  products,
}: {
  title: string;
  description: string;
  products: typeof SEED_PRODUCTS;
}) {
  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>{title}</h1>
          <p>{description}</p>
        </div>
      </section>
      <section className="accounts-section">
        <div className="container">
          <div className="accounts-list">
            <div className="list-header">
              <h3>
                Hesaplar <span className="account-count">({products.length})</span>
              </h3>
            </div>
            {products.map((product) => (
              <Link
                key={product.id}
                href={`/${product.seoSlug}`}
                className="account-item"
                style={{ textDecoration: "none", color: "inherit" }}
              >
                <div className="account-avatar">
                  <i className="fas fa-user" />
                </div>
                <div className="account-info">
                  <h4>{product.title}</h4>
                  <p>{product.platform} · Stok: {product.stock}</p>
                  <div className="account-meta">
                    <span className="price">{formatPrice(product.price)}</span>
                    <span className="stock in-stock">Stokta</span>
                  </div>
                </div>
                {product.isVerified ? (
                  <div className="account-badge verified" title="Doğrulanmış">
                    <i className="fas fa-check" />
                  </div>
                ) : null}
              </Link>
            ))}
          </div>
          <div style={{ marginTop: 30 }}>
            <div className="categories-grid">
              {SEED_CATEGORIES.map((category) => (
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
        </div>
      </section>
    </main>
  );
}

function ProductPage({
  product,
}: {
  product: NonNullable<ReturnType<typeof getProductBySlug>>;
}) {
  const title = PRODUCT_META_TITLES[product.id] ?? product.title;
  return (
    <main>
      <section className="accounts-section">
        <div className="container">
          <div className="account-details">
            <div className="account-detail-card">
              <div className="detail-header">
                <div className="detail-avatar">
                  <i className="fas fa-user-shield" />
                </div>
                <div className="detail-title">
                  <h2>{title}</h2>
                  <p>{product.description}</p>
                  <div className="detail-badges">
                    {product.isVerified ? (
                      <span className="badge verified">
                        <i className="fas fa-check-circle" /> Doğrulanmış
                      </span>
                    ) : null}
                    <span className="badge stock-available">
                      <i className="fas fa-box" /> Stok: {product.stock}
                    </span>
                  </div>
                </div>
              </div>
              <div className="detail-body">
                <div className="price-section">
                  <div className="price-info">
                    <span className="current-price">{formatPrice(product.price)}</span>
                    {product.oldPrice ? (
                      <span className="old-price">{formatPrice(product.oldPrice)}</span>
                    ) : null}
                  </div>
                  <div className="stock-info">
                    <i className="fas fa-bolt" /> Anında teslimat
                  </div>
                </div>
                <div className="description-section">
                  <h3>
                    <i className="fas fa-info-circle" /> Açıklama
                  </h3>
                  <p>{product.description}</p>
                </div>
                <div className="purchase-section">
                  <div className="purchase-buttons">
                    <Link href="/guest-purchase" className="btn-buy-now">
                      <i className="fas fa-shopping-bag" /> Hemen Satın Al
                    </Link>
                    <Link href="/giris-yap" className="btn-add-cart">
                      <i className="fas fa-cart-plus" /> Sepete Ekle
                    </Link>
                  </div>
                  <div className="security-info">
                    <div className="security-item">
                      <i className="fas fa-shield-alt" />
                      Güvenli ödeme
                    </div>
                    <div className="security-item">
                      <i className="fas fa-headset" />
                      7/24 destek
                    </div>
                    <div className="security-item">
                      <i className="fas fa-undo" />
                      24 saat değişim
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}

function StaticContentPage({
  title,
  description,
  children,
}: {
  title: string;
  description: string;
  children: React.ReactNode;
}) {
  return (
    <main>
      <section className="page-header compact">
        <div className="container">
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

export default async function SeoSlugPage({ params }: PageProps) {
  const { slug } = await params;

  if (slug === "tum-hesaplar") {
    return (
      <CatalogPage
        title={ALL_ACCOUNTS_META.title}
        description={ALL_ACCOUNTS_META.description}
        products={SEED_PRODUCTS}
      />
    );
  }

  const category = getCategoryBySlug(slug);
  if (category) {
    const meta = CATEGORY_META[category.id] ?? ALL_ACCOUNTS_META;
    return (
      <CatalogPage
        title={meta.title}
        description={meta.description}
        products={getProductsByCategoryId(category.id)}
      />
    );
  }

  const product = getProductBySlug(slug);
  if (product) {
    return <ProductPage product={product} />;
  }

  if (slug === "sikca-sorulan-sorular") {
    const meta = STATIC_PAGE_META[slug];
    return (
      <StaticContentPage title={meta.title} description={meta.description}>
        <FaqAccordion faqs={SEED_FAQS} />
      </StaticContentPage>
    );
  }

  if (slug === "hizmetler") {
    const meta = STATIC_PAGE_META[slug];
    return (
      <StaticContentPage title={meta.title} description={meta.description}>
        <div className="services-grid">
          {SEED_CATEGORIES.map((item) => (
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
    const meta = STATIC_PAGE_META[slug];
    return (
      <StaticContentPage title={meta.title} description={meta.description}>
        <div className="features-grid">
          <div className="feature-card">
            <div className="feature-icon">
              <i className="fas fa-phone" />
            </div>
            <h3>Telefon</h3>
            <p>{SITE_DEFAULTS.contactPhone}</p>
          </div>
          <div className="feature-card">
            <div className="feature-icon">
              <i className="fas fa-envelope" />
            </div>
            <h3>E-posta</h3>
            <p>{SITE_DEFAULTS.contactEmail}</p>
          </div>
          <div className="feature-card">
            <div className="feature-icon">
              <i className="fab fa-whatsapp" />
            </div>
            <h3>WhatsApp</h3>
            <p>{SITE_DEFAULTS.contactWhatsapp}</p>
          </div>
        </div>
      </StaticContentPage>
    );
  }

  if (slug === "giris-yap" || slug === "kayit-ol") {
    const meta = STATIC_PAGE_META[slug];
    return (
      <StaticContentPage title={meta.title} description={meta.description}>
        <div className="feature-card" style={{ maxWidth: 520, margin: "0 auto" }}>
          <h3>{slug === "giris-yap" ? "Giriş" : "Kayıt"} formu yakında</h3>
          <p>
            Auth ve veritabanı (Neon) bağlandıktan sonra canlı site ile aynı giriş /
            kayıt akışı aktif olacak. Şimdilik katalog ve SEO sayfaları hazır.
          </p>
          <Link href="/tum-hesaplar" className="btn btn-primary" style={{ marginTop: 20 }}>
            Hesaplara Dön
          </Link>
        </div>
      </StaticContentPage>
    );
  }

  if (slug === "siparis-takip" || slug === "guest-purchase") {
    const title =
      slug === "siparis-takip"
        ? STATIC_PAGE_META["siparis-takip"].title
        : "Hesap Satın Al";
    const description =
      slug === "siparis-takip"
        ? STATIC_PAGE_META["siparis-takip"].description
        : "Misafir olarak hızlı satın alma.";
    return (
      <StaticContentPage title={title} description={description}>
        <div className="feature-card" style={{ maxWidth: 640, margin: "0 auto" }}>
          <h3>Ödeme / takip akışı bağlanıyor</h3>
          <p>
            Shopier ve Cryptomus entegrasyonu Neon + admin paneli ile birlikte
            sonraki adımda tamamlanacak. SEO URL’ler ve vitrin birebir korunuyor.
          </p>
        </div>
      </StaticContentPage>
    );
  }

  if (slug === "kvkk" || slug === "gizlilik-politikasi") {
    return (
      <StaticContentPage
        title={slug === "kvkk" ? "KVKK Aydınlatma Metni" : "Gizlilik Politikası"}
        description="Yasal bilgilendirme"
      >
        <div className="feature-card">
          <p>
            Yasal sayfa içerikleri Neon veritabanındaki `legal_pages` tablosundan
            çekilecek. Legacy içerik `legacy/` altında arşivlidir.
          </p>
        </div>
      </StaticContentPage>
    );
  }

  notFound();
}
