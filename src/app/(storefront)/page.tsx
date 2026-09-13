import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { FaqAccordion } from "@/components/FaqAccordion";
import {
  getActiveFaqs,
  getSiteSettings,
  getStorefrontCategories,
} from "@/lib/db/queries";
import { HOME_META } from "@/lib/seo/meta";
import {
  faqStructuredData,
  homepageStructuredData,
  serializeJsonLd,
} from "@/lib/seo/structured-data";
import { getHeroStats, getSetting } from "@/lib/settings";

// Segment config must be a literal; keep in sync with the other storefront routes.
export const revalidate = 300;

const HOME_FAQ_COUNT = 6;

export const metadata: Metadata = {
  title: { absolute: HOME_META.title },
  description: HOME_META.description,
  keywords:
    "facebook reklam hesabı satın al, business manager hesap, doğrulanmış business manager, facebook reklam hesabı, kimlik onaylı facebook hesabı, instagram hesap satın al, tiktok hesap, twitter hesap, discord hesap, telegram hesap, gmail hesap, outlook hesap, hesap satış platformu",
  alternates: { canonical: "/" },
  other: {
    "ai-intent":
      "Facebook reklam hesabı satın al ile sosyal medya hesaplarını güvenli ve hızlı şekilde edinmek isteyen kullanıcıları bilgilendirme",
  },
};

export default async function HomePage() {
  const [settings, categories, faqs] = await Promise.all([
    getSiteSettings(),
    getStorefrontCategories(),
    getActiveFaqs(HOME_FAQ_COUNT),
  ]);

  const heroTitle = getSetting(
    settings,
    "hero_title",
    "Premium Sosyal Medya Hesapları",
  );
  const heroDescription = getSetting(
    settings,
    "hero_description",
    "Doğrulanmış, yüksek limitli ve güvenli hesapları keşfedin.",
  );
  const heroButtonText = getSetting(settings, "hero_button_text", "Hemen Satın Al");
  const heroButtonUrl = getSetting(settings, "hero_button_url", "/tum-hesaplar");
  const heroImage = getSetting(settings, "hero_background", "/images/mockup.png");
  const heroStats = getHeroStats(settings);

  const homeJsonLd = serializeJsonLd(homepageStructuredData());
  const faqJsonLd = serializeJsonLd(faqStructuredData(faqs));

  return (
    <main>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: homeJsonLd }}
      />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: faqJsonLd }}
      />
      <section className="hero">
        <div className="container">
          <div className="hero-content">
            <div className="hero-text">
              <h1>{heroTitle}</h1>
              <p>{heroDescription}</p>
              <div className="hero-buttons">
                <Link href={heroButtonUrl} className="btn btn-primary">
                  <i className="fas fa-shopping-bag" /> {heroButtonText}
                </Link>
                <a href="#categories" className="btn btn-secondary">
                  <i className="fas fa-th-large" /> Kategorileri Gör
                </a>
              </div>
              <div className="stats-grid">
                {heroStats.map((stat) => (
                  <div className="stat-card" key={stat.label}>
                    <div className="stat-number">{stat.number}</div>
                    <div className="stat-label">{stat.label}</div>
                  </div>
                ))}
              </div>
            </div>

            <div className="hero-image">
              <div className="floating-element" />
              <div className="floating-element" />
              <div className="floating-element" />
              <Image
                src={heroImage}
                alt="Facebook Reklam Hesabı Satın Al - Sosyal Medya Hesapları"
                width={600}
                height={400}
                priority
              />
              <div className="shimmer" />
            </div>
          </div>
        </div>
      </section>

      <section id="categories" className="section">
        <div className="container">
          <div className="section-title">
            <h2>Kategoriler</h2>
            <p>İhtiyacınıza uygun kategoriyi seçin ve hesapları keşfedin</p>
          </div>
          <div className="categories-grid">
            {categories.map((category) => (
              <Link
                key={category.id}
                href={`/${category.seoSlug}`}
                className="category-card"
                style={{ textDecoration: "none", color: "inherit", display: "block" }}
              >
                <div className="category-header">
                  <div className="category-icon">
                    <i className={category.icon} />
                  </div>
                  <h3>{category.name}</h3>
                </div>
                <div className="category-body">
                  <p className="category-description">{category.description}</p>
                  <span className="btn-block">
                    <i className="fas fa-arrow-right" /> Hesapları Gör
                  </span>
                </div>
              </Link>
            ))}
          </div>
          <div className="faq-more" style={{ marginTop: 40 }}>
            <Link href="/tum-hesaplar" className="btn btn-outline">
              <i className="fas fa-th-large" /> Tüm Hesapları Gör
            </Link>
          </div>
        </div>
      </section>

      <section className="section features">
        <div className="container">
          <div className="section-title">
            <h2>Neden YildizHesap?</h2>
            <p>Premium hesap deneyimini farklı kılan özelliklerimiz</p>
          </div>
          <div className="features-grid">
            <div className="feature-card">
              <div className="feature-icon">
                <i className="fas fa-shield-alt" />
              </div>
              <h3>Tam Güvenlik</h3>
              <p>
                Hesaplar özenle oluşturulur ve size özel olarak sunulur. Verileriniz
                güvenli altyapı ile korunur.
              </p>
            </div>
            <div className="feature-card">
              <div className="feature-icon">
                <i className="fas fa-bolt" />
              </div>
              <h3>Anında Teslimat</h3>
              <p>
                Satın aldığınız hesapların bilgileri anında panelinize düşer.
                Beklemeden erişim sağlarsınız.
              </p>
            </div>
            <div className="feature-card">
              <div className="feature-icon">
                <i className="fas fa-headset" />
              </div>
              <h3>7/24 Premium Destek</h3>
              <p>
                Profesyonel destek ekibimiz 7 gün 24 saat yanınızda. Canlı destek ve
                hızlı çözüm.
              </p>
            </div>
          </div>
        </div>
      </section>

      {faqs.length === 0 ? null : (
        <section className="section">
          <div className="container">
            <div className="section-title">
              <h2>Sıkça Sorulan Sorular</h2>
              <p>Aklınıza takılan soruların cevaplarını burada bulabilirsiniz</p>
            </div>
            <FaqAccordion faqs={faqs} />
            <div className="faq-more">
              <Link href="/sikca-sorulan-sorular" className="btn btn-outline">
                <i className="fas fa-question-circle" /> Tüm SSS&apos;leri Gör
              </Link>
            </div>
          </div>
        </section>
      )}
    </main>
  );
}
