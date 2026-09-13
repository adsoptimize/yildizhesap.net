import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { FaqAccordion } from "@/components/FaqAccordion";
import { SEED_CATEGORIES, SEED_FAQS, SITE_DEFAULTS } from "@/data/seed";
import { HOME_META } from "@/lib/seo/meta";

export const metadata: Metadata = {
  title: { absolute: HOME_META.title },
  description: HOME_META.description,
  other: {
    "ai-intent":
      "Facebook reklam hesabı satın al ile sosyal medya hesaplarını güvenli ve hızlı şekilde edinmek isteyen kullanıcıları bilgilendirme",
  },
};

export default function HomePage() {
  return (
    <main>
      <section className="hero">
        <div className="container">
          <div className="hero-content">
            <div className="hero-text">
              <h1>{SITE_DEFAULTS.heroTitle}</h1>
              <p>{SITE_DEFAULTS.heroDescription}</p>
              <div className="hero-buttons">
                <Link href={SITE_DEFAULTS.heroButtonUrl} className="btn btn-primary">
                  <i className="fas fa-shopping-bag" /> {SITE_DEFAULTS.heroButtonText}
                </Link>
                <a href="#categories" className="btn btn-secondary">
                  <i className="fas fa-th-large" /> Kategorileri Gör
                </a>
              </div>
              <div className="stats-grid">
                {SITE_DEFAULTS.heroStats.map((stat) => (
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
                src={SITE_DEFAULTS.heroImage}
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
            {SEED_CATEGORIES.map((category) => (
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

      <section className="section">
        <div className="container">
          <div className="section-title">
            <h2>Sıkça Sorulan Sorular</h2>
            <p>Aklınıza takılan soruların cevaplarını burada bulabilirsiniz</p>
          </div>
          <FaqAccordion faqs={SEED_FAQS.slice(0, 6)} />
          <div className="faq-more">
            <Link href="/sikca-sorulan-sorular" className="btn btn-outline">
              <i className="fas fa-question-circle" /> Tüm SSS&apos;leri Gör
            </Link>
          </div>
        </div>
      </section>
    </main>
  );
}
