"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

/** Mirrors legacy/admin/sidebar.php one to one. */
const ADMIN_NAV = [
  { href: "/admin", label: "Dashboard", icon: "fas fa-tachometer-alt" },
  { href: "/admin/site-settings", label: "Site Ayarları", icon: "fas fa-cog" },
  { href: "/admin/accounts", label: "Hesap Yönetimi", icon: "fas fa-users" },
  { href: "/admin/categories", label: "Kategori Yönetimi", icon: "fas fa-tags" },
  { href: "/admin/users", label: "Kullanıcı Yönetimi", icon: "fas fa-user-friends" },
  { href: "/admin/orders", label: "Sipariş Yönetimi", icon: "fas fa-shopping-cart" },
  { href: "/admin/support", label: "Destek Yönetimi", icon: "fas fa-headset" },
  { href: "/admin/analytics", label: "Analitik & Raporlar", icon: "fas fa-chart-line" },
  { href: "/admin/payment-settings", label: "Ödeme Ayarları", icon: "fab fa-bitcoin" },
  { href: "/admin/advertisements", label: "Reklam Yönetimi", icon: "fas fa-ad" },
  { href: "/admin/ip-limits", label: "IP Limit Yönetimi", icon: "fas fa-network-wired" },
  { href: "/admin/legal-pages", label: "Yasal Sayfalar", icon: "fas fa-shield-alt" },
  { href: "/admin/sessions", label: "Session Yönetimi", icon: "fas fa-user-shield" },
] as const;

type AdminSidebarProps = {
  logoutAction: () => Promise<void>;
};

export function AdminSidebar({ logoutAction }: AdminSidebarProps) {
  const pathname = usePathname();

  return (
    <div className="sidebar">
      <div className="sidebar-header">
        <div className="sidebar-logo">YildizHesap Admin</div>
        <div className="sidebar-subtitle">Control Panel</div>
      </div>

      <nav className="sidebar-nav">
        {ADMIN_NAV.map((item) => {
          const active =
            item.href === "/admin"
              ? pathname === "/admin"
              : pathname.startsWith(item.href);

          return (
            <div className="nav-item" key={item.href}>
              <Link
                href={item.href}
                className={`nav-link${active ? " active" : ""}`}
              >
                <i className={item.icon} />
                {item.label}
              </Link>
            </div>
          );
        })}

        <div className="nav-item">
          <Link href="/" className="nav-link" target="_blank">
            <i className="fas fa-external-link-alt" />
            Siteyi Görüntüle
          </Link>
        </div>

        <div className="nav-item">
          <form action={logoutAction}>
            <button type="submit" className="nav-link">
              <i className="fas fa-sign-out-alt" />
              Çıkış Yap
            </button>
          </form>
        </div>
      </nav>
    </div>
  );
}
