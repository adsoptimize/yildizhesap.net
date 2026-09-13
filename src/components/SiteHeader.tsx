"use client";

import Image from "next/image";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useEffect, useState } from "react";
import type { SessionSummary } from "@/app/api/session-summary/route";
import { logoutCustomerAction } from "@/app/(storefront)/hesabim/actions";
import { CART_PATH } from "@/lib/shop/constants";

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

const EMPTY_SUMMARY: SessionSummary = {
  firstName: null,
  isAdmin: false,
  cartQuantity: 0,
};

export function SiteHeader({ siteTitle, logoSubtext }: SiteHeaderProps) {
  const pathname = usePathname();
  const [mobileOpen, setMobileOpen] = useState(false);
  const [summary, setSummary] = useState<SessionSummary>(EMPTY_SUMMARY);

  useEffect(() => {
    let active = true;

    async function loadSummary(): Promise<void> {
      try {
        const response = await fetch("/api/session-summary", {
          cache: "no-store",
        });

        if (!response.ok) {
          return;
        }

        const data = (await response.json()) as SessionSummary;

        if (active) {
          setSummary(data);
        }
      } catch {
        // Keep the guest header when the request fails.
      }
    }

    void loadSummary();

    return () => {
      active = false;
    };
  }, [pathname]);

  return (
    <header>
      <div className="container header-container">
        <Link href="/" className="logo" aria-label={`${siteTitle} - ${logoSubtext}`}>
          <Image
            src="/images/logo-adsoptimize.png"
            alt={`${siteTitle} - AdsOptimize`}
            width={642}
            height={120}
            priority
            className="logo-image"
          />
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

        <div className="cart-icon-container">
          <Link href={CART_PATH} className="cart-icon" aria-label="Sepeti görüntüle">
            <i className="fas fa-shopping-cart" />
            <span className="cart-count">{summary.cartQuantity}</span>
          </Link>
        </div>

        <div className="header-actions">
          {summary.firstName === null ? (
            <>
              <Link href="/giris-yap" className="btn btn-primary">
                <i className="fas fa-sign-in-alt" />{" "}
                <span className="btn-text">Giriş Yap</span>
              </Link>
              <Link href="/kayit-ol" className="btn btn-secondary">
                <i className="fas fa-user-plus" />{" "}
                <span className="btn-text">Kayıt Ol</span>
              </Link>
            </>
          ) : (
            <>
              <Link href="/hesabim" className="btn btn-primary">
                <i className="fas fa-user" />{" "}
                <span className="btn-text">{summary.firstName}</span>
              </Link>
              {summary.isAdmin ? (
                <Link href="/admin" className="btn btn-secondary">
                  <i className="fas fa-shield-alt" />{" "}
                  <span className="btn-text">Admin</span>
                </Link>
              ) : null}
              <form action={logoutCustomerAction}>
                <button type="submit" className="btn btn-secondary">
                  <i className="fas fa-sign-out-alt" />{" "}
                  <span className="btn-text">Çıkış</span>
                </button>
              </form>
            </>
          )}
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
