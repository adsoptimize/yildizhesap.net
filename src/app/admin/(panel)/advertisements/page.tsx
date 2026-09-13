import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { StatefulForm } from "@/components/admin/StatefulForm";
import { requireAdmin } from "@/lib/auth/admin";
import { formatNumber } from "@/lib/admin/format";
import { prisma } from "@/lib/prisma";
import {
  cleanupPopupViewsAction,
  resetPopupViewsAction,
  savePopupAdAction,
} from "./actions";

const TARGET_OPTIONS = [
  { value: "both", label: "Herkes" },
  { value: "visitors", label: "Sadece ziyaretçiler" },
  { value: "users", label: "Sadece üyeler" },
] as const;

const TODAY_START_HOUR = 0;

export default async function AdminAdvertisementsPage() {
  const admin = await requireAdmin();

  const today = new Date();
  today.setHours(TODAY_START_HOUR, 0, 0, 0);

  const [settings, totalViews, todayViews, uniqueVisitors] = await Promise.all([
    prisma.siteSetting.findMany({
      where: { settingKey: { startsWith: "popup_ad_" } },
      select: { settingKey: true, settingValue: true },
    }),
    prisma.popupAdView.aggregate({ _sum: { viewCount: true } }),
    prisma.popupAdView.aggregate({
      where: { viewDate: { gte: today } },
      _sum: { viewCount: true },
    }),
    prisma.popupAdView.count(),
  ]);

  const valueByKey = new Map(
    settings.map((setting) => [setting.settingKey, setting.settingValue ?? ""]),
  );

  return (
    <>
      <AdminPageHeader
        title="Reklam Yönetimi"
        subtitle="Popup reklam içeriği ve gösterim limitleri"
        userName={`${admin.firstName} ${admin.lastName}`}
      />

      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-header">
            <span className="stat-title">Toplam gösterim</span>
            <div className="stat-icon accounts">
              <i className="fas fa-eye" />
            </div>
          </div>
          <div className="stat-value">
            {formatNumber(totalViews._sum.viewCount ?? 0)}
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-header">
            <span className="stat-title">Bugün</span>
            <div className="stat-icon orders">
              <i className="fas fa-calendar-day" />
            </div>
          </div>
          <div className="stat-value">
            {formatNumber(todayViews._sum.viewCount ?? 0)}
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-header">
            <span className="stat-title">Tekil kayıt</span>
            <div className="stat-icon users">
              <i className="fas fa-fingerprint" />
            </div>
          </div>
          <div className="stat-value">{formatNumber(uniqueVisitors)}</div>
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Popup Ayarları</h2>
        </div>
        <div className="card-body">
          <StatefulForm action={savePopupAdAction} submitLabel="Ayarları Kaydet">
            <label className="checkbox-field">
              <input
                type="checkbox"
                name="enabled"
                defaultChecked={valueByKey.get("popup_ad_enabled") === "1"}
              />
              Popup reklam sistemi aktif
            </label>

            <div className="form-field">
              <label htmlFor="content">Popup içeriği (HTML)</label>
              <textarea
                id="content"
                name="content"
                defaultValue={valueByKey.get("popup_ad_content") ?? ""}
                style={{ minHeight: 200, fontFamily: "monospace" }}
              />
            </div>

            <div className="form-grid">
              <div className="form-field">
                <label htmlFor="frequency">Günlük gösterim (1-10)</label>
                <input
                  id="frequency"
                  name="frequency"
                  type="number"
                  min={1}
                  max={10}
                  defaultValue={valueByKey.get("popup_ad_frequency") ?? "1"}
                />
              </div>
              <div className="form-field">
                <label htmlFor="target">Hedef kitle</label>
                <select
                  id="target"
                  name="target"
                  defaultValue={valueByKey.get("popup_ad_target") ?? "both"}
                >
                  {TARGET_OPTIONS.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </StatefulForm>
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Gösterim Kayıtları</h2>
        </div>
        <div className="card-body" style={{ display: "flex", gap: "0.75rem" }}>
          <form action={resetPopupViewsAction}>
            <button type="submit" className="admin-btn danger">
              <i className="fas fa-rotate-left" /> Tüm gösterimleri sıfırla
            </button>
          </form>
          <form action={cleanupPopupViewsAction}>
            <button type="submit" className="admin-btn secondary">
              <i className="fas fa-broom" /> 30 günden eskileri temizle
            </button>
          </form>
        </div>
      </div>
    </>
  );
}
