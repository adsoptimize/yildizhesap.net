import Link from "next/link";
import type { SettingsMap } from "@/lib/db/queries";
import {
  digitsOnly,
  getFooterLinkGroups,
  getSetting,
  getSocialLinks,
} from "@/lib/settings";

type SiteFooterProps = {
  settings: SettingsMap;
};

export function SiteFooter({ settings }: SiteFooterProps) {
  const siteTitle = getSetting(settings, "site_title", "YildizHesap");
  const footerDescription = getSetting(
    settings,
    "footer_description",
    "Kaliteli sosyal medya hesapları ile işinizi büyütmeniz için buradayız.",
  );
  const copyright = getSetting(
    settings,
    "footer_copyright",
    "© 2025 YildizHesap.net - Tüm Hakları Saklıdır.",
  );
  const phone = getSetting(settings, "contact_phone", "");
  const email = getSetting(settings, "contact_email", "info@yildizhesap.net");
  const whatsapp = digitsOnly(getSetting(settings, "contact_whatsapp", ""));
  const address = getSetting(settings, "contact_address", "İstanbul, Türkiye");
  const linkGroups = getFooterLinkGroups(settings);
  const socialLinks = getSocialLinks(settings);

  return (
    <footer>
      <div className="container">
        <div className="footer-grid">
          <div className="footer-column">
            <h3>{siteTitle}</h3>
            <p>{footerDescription}</p>
            <div className="social-icons">
              {socialLinks.map((social) => (
                <a
                  key={social.platform}
                  href={social.url}
                  aria-label={social.platform}
                  target="_blank"
                  rel="noreferrer"
                >
                  <i className={social.icon} />
                </a>
              ))}
              {whatsapp === "" ? null : (
                <a
                  href={`https://wa.me/${whatsapp}`}
                  aria-label="WhatsApp"
                  target="_blank"
                  rel="noreferrer"
                >
                  <i className="fab fa-whatsapp" />
                </a>
              )}
            </div>
          </div>

          {linkGroups.map((group) => (
            <div className="footer-column" key={group.title}>
              <h3>{group.title}</h3>
              <ul>
                {group.links.map((link) => (
                  <li key={link.url}>
                    <Link href={link.url}>
                      <i className="fas fa-chevron-right" /> {link.name}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}

          <div className="footer-column">
            <h3>İletişim</h3>
            <ul>
              {phone === "" ? null : (
                <li>
                  <a href={`tel:${digitsOnly(phone)}`}>
                    <i className="fas fa-phone" /> {phone}
                  </a>
                </li>
              )}
              <li>
                <a href={`mailto:${email}`}>
                  <i className="fas fa-envelope" /> {email}
                </a>
              </li>
              {whatsapp === "" ? null : (
                <li>
                  <a
                    href={`https://wa.me/${whatsapp}`}
                    target="_blank"
                    rel="noreferrer"
                  >
                    <i className="fab fa-whatsapp" /> WhatsApp
                  </a>
                </li>
              )}
              <li>
                <span>
                  <i className="fas fa-map-marker-alt" /> {address}
                </span>
              </li>
            </ul>
          </div>

          <div className="footer-column">
            <h3>Ödeme Yöntemleri</h3>
            <p>Tüm kredi kartları, Shopier ve kripto ile güvenli ödeme</p>
            <div className="payment-methodssa">
              <div className="payment-methodsa" title="Visa">
                <i className="fab fa-cc-visa" />
              </div>
              <div className="payment-methodsa" title="Mastercard">
                <i className="fab fa-cc-mastercard" />
              </div>
              <div className="payment-methodsa" title="Bitcoin">
                <i className="fab fa-bitcoin" />
              </div>
            </div>
          </div>
        </div>

        <div className="footer-bottom">
          <p>
            {copyright} | <Link href="/kvkk">KVKK</Link> ve{" "}
            <Link href="/gizlilik-politikasi">Gizlilik Politikası</Link>
          </p>
        </div>
      </div>
    </footer>
  );
}
