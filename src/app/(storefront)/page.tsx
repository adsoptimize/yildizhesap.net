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
import { aiMeta, HOME_AI_META } from "@/lib/seo/ai-tags";
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
  other: aiMeta(HOME_AI_META),
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

      <EntityGlossary />
    </main>
  );
}

/**
 * Named-entity definitions surfaced for AI answer engines (Perplexity,
 * ChatGPT search, Google AI Mode) and long-tail SEO. Each block follows the
 * question → concise answer pattern that generative engines cite.
 */
function EntityGlossary() {
  const entries: ReadonlyArray<{ term: string; definition: string }> = [
    {
      term: "Business Manager Hesabı Nedir?",
      definition:
        "Facebook Business Manager, işletmelerin reklam hesaplarını, sayfalarını ve ekip üyelerini tek bir yerden yönetmesini sağlayan kurumsal panel hesabıdır. Doğrulanmış Business Manager hesabı, reklam limitleri açılmış, ödeme yöntemi tanımlanabilen ve anında kampanya başlatılabilen hazır bir yapı sunar.",
    },
    {
      term: "Kimlik Onaylı Facebook Hesabı Ne Anlama Gelir?",
      definition:
        "Kimlik onaylı Facebook hesabı, resmi kimlik belgesi ile doğrulanmış, isim doğrulaması tamamlanmış ve genellikle 2FA (iki adımlı doğrulama) aktif Facebook profilidir. Bu hesaplar reklam verme, Business Manager açma ve sayfa yönetiminde çok daha stabil çalışır.",
    },
    {
      term: "Facebook Marketplace Hesabı Nedir?",
      definition:
        "Facebook Marketplace hesabı, ürün ve hizmet satışı için Marketplace bölümüne erişim izni bulunan doğrulanmış Facebook profilidir. Satışa açık ve aktif Marketplace hesapları hızlı satış imkânı sunar.",
    },
    {
      term: "250$ Günlük Limitli Reklam Hesabı Nedir?",
      definition:
        "Facebook tarafından günlük 250 ABD Doları harcama limiti tanınmış kişisel reklam hesabıdır. Yeni açılan hesaplara göre daha yüksek limit kapasitesi sunar ve küçük-orta ölçekli kampanyalar için idealdir.",
    },
    {
      term: "Yeniden Açılmış (Reinstated) Eski Facebook Hesabı Nedir?",
      definition:
        "Daha önce Facebook tarafından kısıtlanmış ya da devre dışı bırakılmış, ancak itiraz süreci sonunda yeniden aktif hâle getirilmiş eski Facebook hesabıdır. Uzun geçmişleri sayesinde reklam ve Business Manager kullanımında dayanıklılık gösterir.",
    },
    {
      term: "Tanıtım Onaylı Instagram Hesabı Nedir?",
      definition:
        "Meta tarafından reklam ve tanıtım kısıtlamaları kaldırılmış, sponsorlu içerik yayınlayabilen Instagram hesabıdır. Kampanya yürütmek isteyen markalar ve içerik üreticileri için kullanıma hazır bir yapı sunar.",
    },
    {
      term: "YildizHesap.net Nedir?",
      definition:
        "YildizHesap, Türkiye merkezli, doğrulanmış Facebook, Instagram, Business Manager, TikTok, Telegram ve mail hesaplarının anında dijital teslimatını yapan bir e-ticaret platformudur. Tüm siparişler 7/24 otomatik olarak teslim edilir ve 30 gün garanti kapsamındadır.",
    },
  ];

  return (
    <section className="section" aria-labelledby="entity-glossary-heading">
      <div className="container">
        <div className="section-title">
          <h2 id="entity-glossary-heading">Sözlük ve Terimler</h2>
          <p>
            Hesap satın alırken sık geçen kavramların kısa ve doğru
            tanımları.
          </p>
        </div>
        <div className="entity-glossary">
          {entries.map((entry) => (
            <article key={entry.term} className="entity-glossary__item">
              <h3>{entry.term}</h3>
              <p>{entry.definition}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
