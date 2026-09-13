/**
 * Replacement for legacy/banned.php. Kept outside the storefront route group so
 * a blocked visitor does not get the header, footer or popup ad.
 */

import type { Metadata } from "next";
import { formatDateTime } from "@/lib/admin/format";
import { getClientIp } from "@/lib/auth/session";
import { getSiteSettings } from "@/lib/db/queries";
import { getSetting } from "@/lib/settings";
import { getActiveBan } from "@/lib/security/ip-ban";
import "./banned.css";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "Erişim Engellendi",
  robots: { index: false, follow: false },
};

export default async function BannedPage() {
  const [ipAddress, settings] = await Promise.all([
    getClientIp(),
    getSiteSettings(),
  ]);
  const ban = await getActiveBan(ipAddress);

  const title = getSetting(settings, "ban_page_title", "Erişim Engellendi");
  const message = getSetting(
    settings,
    "ban_page_message",
    "IP adresiniz sistem yöneticisi tarafından engellenmiştir.",
  );
  const contact = getSetting(
    settings,
    "ban_page_contact",
    `Destek için ${getSetting(settings, "contact_email", "info@yildizhesap.net")} adresine yazabilirsiniz.`,
  );

  return (
    <main className="ban-container">
      <div className="ban-icon">
        <i className="fas fa-ban" />
      </div>

      <h1 className="ban-title">{title}</h1>
      <p className="ban-message">{message}</p>

      <div className="ban-details">
        <h2>
          <i className="fas fa-info-circle" /> Ban Detayları
        </h2>

        <div className="ban-detail-item">
          <span className="ban-detail-label">IP Adresiniz:</span>
          <span className="ban-detail-value">{ipAddress}</span>
        </div>

        {ban === null ? (
          <div className="ban-detail-item">
            <span className="ban-detail-label">Durum:</span>
            <span className="ban-detail-value">
              Aktif bir engel bulunamadı, sayfayı yenileyin.
            </span>
          </div>
        ) : (
          <>
            <div className="ban-detail-item">
              <span className="ban-detail-label">Ban Sebebi:</span>
              <span className="ban-detail-value">{ban.reason}</span>
            </div>
            <div className="ban-detail-item">
              <span className="ban-detail-label">Ban Tarihi:</span>
              <span className="ban-detail-value">
                {formatDateTime(ban.createdAt)}
              </span>
            </div>
            <div className="ban-detail-item">
              <span className="ban-detail-label">Ban Türü:</span>
              <span className="ban-detail-value">
                {ban.banType === "permanent" ? "Kalıcı" : "Geçici"}
              </span>
            </div>
            {ban.banType === "temporary" && ban.bannedUntil !== null ? (
              <div className="ban-detail-item">
                <span className="ban-detail-label">Ban Bitiş Tarihi:</span>
                <span className="ban-detail-value">
                  {formatDateTime(ban.bannedUntil)}
                </span>
              </div>
            ) : null}
          </>
        )}
      </div>

      <p className="ban-contact">{contact}</p>
    </main>
  );
}
