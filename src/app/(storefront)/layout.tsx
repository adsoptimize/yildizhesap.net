import { PopupAd } from "@/components/PopupAd";
import { SiteFooter } from "@/components/SiteFooter";
import { SiteHeader } from "@/components/SiteHeader";
import { getSiteSettings } from "@/lib/db/queries";
import { getSetting } from "@/lib/settings";
import "./shop.css";

export default async function StorefrontLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  const settings = await getSiteSettings();

  return (
    <>
      <SiteHeader
        siteTitle={getSetting(settings, "site_title", "YildizHesap")}
        logoSubtext={getSetting(settings, "logo_subtext", "PREMIUM HESAP PAZARI")}
      />
      {children}
      <SiteFooter settings={settings} />
      <PopupAd />
    </>
  );
}
