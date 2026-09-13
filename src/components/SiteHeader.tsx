"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";

const NAV_ITEMS = [
  { name: "Anasayfa", href: "/", icon: "fas fa-home" },
  { name: "Hizmetler", href: "/hizmetler", icon: "fas fa-cogs" },
  { name: "Hesaplar", href: "/tum-hesaplar", icon: "fas fa-shopping-cart" },
  {
    name: "Hesap Satın Al",
    href: "/guest-purchase",
    icon: "fas fa-shopping-bag",
  },
  { name: "Sipariş Takip", href: "/siparis-takip", icon: "fas fa-search" },
  { name: "SSS", href: "/sikca-sorulan-sorular", icon: "fas fa-question-circle" },
  { name: "İletişim", href: "/iletisim", icon: "fas fa-phone-alt" },
] as const;

type SiteHeaderProps = {
  siteTitle: string;
  logoSubtext: string;
};

export function SiteHeader({ siteTitle, logoSubtext }: SiteHeaderProps) {
  const pathname = usePathname();
  const [mobileOpen, setMobileOpen] = useState(false);

  return (
    <header>
      <div className="container header-container">
        <Link href="/" className="logo">
          <div className="logo-icon simple">
            <i className="fas fa-crown logo-main-icon" />
          </div>
          <div className="logo-text">
            <span className="logo-title">{siteTitle}</span>
            <span>{logoSubtext}</span>
          </div>
        </Link>

        <nav className={mobileOpen ? "open" : undefined}>
          <ul>
            {NAV_ITEMS.map((item) => {
              const active =
                item.href === "/"
                  ? pathname === "/"
                  : pathname === item.href || pathname.startsWith(`${item.href}/`);
              return (
                <li key={item.href}>
                  <Link
                    href={item.href}
                    className={active ? "active" : undefined}
                    onClick={() => setMobileOpen(false)}
                  >
                    <i className={item.icon} /> {item.name}
                  </Link>
                </li>
              );
            })}
          </ul>
        </nav>

        <div className="order-track-icon-container">
          <Link
            href="/siparis-takip"
            className="order-track-icon"
            title="Sipariş Takip"
            aria-label="Sipariş takip"
          >
            <i className="fas fa-eye" />
          </Link>
        </div>

        <div className="header-actions">
          <Link href="/giris-yap" className="btn btn-primary">
            <i className="fas fa-sign-in-alt" />{" "}
            <span className="btn-text">Giriş Yap</span>
          </Link>
          <Link href="/kayit-ol" className="btn btn-secondary">
            <i className="fas fa-user-plus" />{" "}
            <span className="btn-text">Kayıt Ol</span>
          </Link>
        </div>

        <button
          type="button"
          className="mobile-menu-btn"
          aria-label="Menü"
          onClick={() => setMobileOpen((value) => !value)}
        >
          <i className={mobileOpen ? "fas fa-times" : "fas fa-bars"} />
        </button>
      </div>
    </header>
  );
}
