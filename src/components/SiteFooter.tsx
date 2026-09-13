import Link from "next/link";
import { SITE_DEFAULTS } from "@/data/seed";

export function SiteFooter() {
  const phone = SITE_DEFAULTS.contactPhone.replace(/[^0-9+]/g, "");
  const whatsapp = SITE_DEFAULTS.contactWhatsapp.replace(/[^0-9]/g, "");

  return (
    <footer>
      <div className="container">
        <div className="footer-grid">
          <div className="footer-column">
            <h3>{SITE_DEFAULTS.title}</h3>
            <p>
              Kaliteli sosyal medya hesapları ile işinizi büyütmeniz için
              buradayız. Güvenli alışverişin premium adresi.
            </p>
            <div className="social-icons">
              <a href="https://instagram.com" aria-label="Instagram" target="_blank" rel="noreferrer">
                <i className="fab fa-instagram" />
              </a>
              <a href="https://t.me" aria-label="Telegram" target="_blank" rel="noreferrer">
                <i className="fab fa-telegram" />
              </a>
              <a href={`https://wa.me/${whatsapp}`} aria-label="WhatsApp" target="_blank" rel="noreferrer">
                <i className="fab fa-whatsapp" />
              </a>
            </div>
          </div>

          <div className="footer-column">
            <h3>Hızlı Erişim</h3>
            <ul>
              <li>
                <Link href="/">
                  <i className="fas fa-chevron-right" /> Anasayfa
                </Link>
              </li>
              <li>
                <Link href="/tum-hesaplar">
                  <i className="fas fa-chevron-right" /> Tüm Hesaplar
                </Link>
              </li>
              <li>
                <Link href="/hizmetler">
                  <i className="fas fa-chevron-right" /> Hizmetlerimiz
                </Link>
              </li>
              <li>
                <Link href="/sikca-sorulan-sorular">
                  <i className="fas fa-chevron-right" /> SSS
                </Link>
              </li>
              <li>
                <Link href="/iletisim">
                  <i className="fas fa-chevron-right" /> İletişim
                </Link>
              </li>
            </ul>
          </div>

          <div className="footer-column">
            <h3>İletişim</h3>
            <ul>
              <li>
                <a href={`tel:${phone}`}>
                  <i className="fas fa-phone" /> {SITE_DEFAULTS.contactPhone}
                </a>
              </li>
              <li>
                <a href={`mailto:${SITE_DEFAULTS.contactEmail}`}>
                  <i className="fas fa-envelope" /> {SITE_DEFAULTS.contactEmail}
                </a>
              </li>
              <li>
                <a href={`https://wa.me/${whatsapp}`} target="_blank" rel="noreferrer">
                  <i className="fab fa-whatsapp" /> WhatsApp
                </a>
              </li>
              <li>
                <span>
                  <i className="fas fa-map-marker-alt" /> {SITE_DEFAULTS.contactAddress}
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
            © 2025 YildizHesap.net - Tüm Hakları Saklıdır. |{" "}
            <Link href="/kvkk">KVKK</Link> ve{" "}
            <Link href="/gizlilik-politikasi">Gizlilik Politikası</Link>
          </p>
        </div>
      </div>
    </footer>
  );
}
