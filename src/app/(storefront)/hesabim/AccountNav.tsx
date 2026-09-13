"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { ACCOUNT_PATH } from "@/lib/shop/constants";

const NAV_ITEMS = [
  { href: ACCOUNT_PATH, label: "Siparişlerim", icon: "fas fa-box" },
  {
    href: `${ACCOUNT_PATH}/destek`,
    label: "Destek Taleplerim",
    icon: "fas fa-headset",
  },
  { href: `${ACCOUNT_PATH}/ayarlar`, label: "Ayarlar", icon: "fas fa-gear" },
] as const;

export function AccountNav() {
  const pathname = usePathname();

  return (
    <nav className="shop-nav">
      {NAV_ITEMS.map((item) => {
        const active =
          item.href === ACCOUNT_PATH
            ? pathname === ACCOUNT_PATH
            : pathname.startsWith(item.href);

        return (
          <Link
            key={item.href}
            href={item.href}
            className={active ? "active" : undefined}
          >
            <i className={item.icon} /> {item.label}
          </Link>
        );
      })}
    </nav>
  );
}
