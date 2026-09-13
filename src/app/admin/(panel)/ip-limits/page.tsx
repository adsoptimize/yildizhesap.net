import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { StatefulForm } from "@/components/admin/StatefulForm";
import { requireAdmin } from "@/lib/auth/admin";
import { formatDateTime, formatNumber } from "@/lib/admin/format";
import { prisma } from "@/lib/prisma";
import {
  banIpAction,
  liftIpBanAction,
  resetIpLimitAction,
  saveIpLimitSettingsAction,
} from "./actions";

const TOP_IP_LIMIT = 15;
const RESET_HISTORY_LIMIT = 15;

export default async function AdminIpLimitsPage() {
  const admin = await requireAdmin();

  const [settings, bans, resets, topIps, inactiveUsers] = await Promise.all([
    prisma.siteSetting.findMany({
      where: {
        settingKey: {
          in: ["max_accounts_per_ip", "ip_limit_days", "ip_limit_enabled"],
        },
      },
      select: { settingKey: true, settingValue: true },
    }),
    prisma.ipBan.findMany({
      where: { isActive: true },
      orderBy: { createdAt: "desc" },
    }),
    prisma.ipLimitReset.findMany({
      orderBy: { createdAt: "desc" },
      take: RESET_HISTORY_LIMIT,
    }),
    prisma.user.groupBy({
      by: ["registrationIp"],
      where: { registrationIp: { not: null } },
      _count: { registrationIp: true },
      orderBy: { _count: { registrationIp: "desc" } },
      take: TOP_IP_LIMIT,
    }),
    prisma.user.count({ where: { isActive: false } }),
  ]);

  const valueByKey = new Map(
    settings.map((setting) => [setting.settingKey, setting.settingValue ?? ""]),
  );

  return (
    <>
      <AdminPageHeader
        title="IP Limit Yönetimi"
        subtitle={`${formatNumber(bans.length)} aktif ban · ${formatNumber(inactiveUsers)} pasif kullanıcı`}
        userName={`${admin.firstName} ${admin.lastName}`}
      />

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Limit Ayarları</h2>
        </div>
        <div className="card-body">
          <StatefulForm action={saveIpLimitSettingsAction} submitLabel="Kaydet">
            <div className="form-grid">
              <div className="form-field">
                <label htmlFor="maxAccounts">IP başına hesap</label>
                <input
                  id="maxAccounts"
                  name="maxAccounts"
                  type="number"
                  min={1}
                  defaultValue={valueByKey.get("max_accounts_per_ip") ?? "1"}
                />
              </div>
              <div className="form-field">
                <label htmlFor="limitDays">Limit süresi (gün)</label>
                <input
                  id="limitDays"
                  name="limitDays"
                  type="number"
                  min={1}
                  defaultValue={valueByKey.get("ip_limit_days") ?? "365"}
                />
              </div>
              <div className="form-field">
                <label htmlFor="enabled">Limit durumu</label>
                <label className="checkbox-field">
                  <input
                    id="enabled"
                    type="checkbox"
                    name="enabled"
                    defaultChecked={valueByKey.get("ip_limit_enabled") === "1"}
                  />
                  IP limiti aktif
                </label>
              </div>
            </div>
          </StatefulForm>
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Aktif IP Banları</h2>
        </div>
        <div className="card-body">
          {bans.length === 0 ? (
            <div className="admin-empty">Aktif ban yok.</div>
          ) : (
            <div className="table-scroll">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>IP</th>
                    <th>Sebep</th>
                    <th>Tür</th>
                    <th>Bitiş</th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  {bans.map((ban) => (
                    <tr key={ban.id}>
                      <td>{ban.ipAddress}</td>
                      <td>{ban.reason}</td>
                      <td>
                        <span className="admin-badge">
                          {ban.banType === "permanent" ? "Kalıcı" : "Geçici"}
                        </span>
                      </td>
                      <td>{formatDateTime(ban.bannedUntil)}</td>
                      <td>
                        <form action={liftIpBanAction}>
                          <input type="hidden" name="id" value={ban.id} />
                          <button type="submit" className="admin-btn secondary small">
                            Kaldır
                          </button>
                        </form>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          <form action={banIpAction} className="form-grid" style={{ marginTop: "1.25rem" }}>
            <div className="form-field">
              <label htmlFor="ipAddress">IP adresi</label>
              <input id="ipAddress" name="ipAddress" type="text" required />
            </div>
            <div className="form-field">
              <label htmlFor="reason">Sebep</label>
              <input id="reason" name="reason" type="text" required />
            </div>
            <div className="form-field">
              <label htmlFor="days">Süre (gün, boş = kalıcı)</label>
              <input id="days" name="days" type="number" min={1} />
            </div>
            <div className="form-field" style={{ justifyContent: "flex-end" }}>
              <button type="submit" className="admin-btn danger">
                <i className="fas fa-ban" /> Banla
              </button>
            </div>
          </form>
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">En Çok Kayıt Yapan IP&apos;ler</h2>
        </div>
        <div className="card-body table-scroll">
          {topIps.length === 0 ? (
            <div className="admin-empty">Kayıt yok.</div>
          ) : (
            <table className="data-table">
              <thead>
                <tr>
                  <th>IP</th>
                  <th>Kullanıcı sayısı</th>
                  <th>İşlem</th>
                </tr>
              </thead>
              <tbody>
                {topIps.map((row) => (
                  <tr key={row.registrationIp ?? "unknown"}>
                    <td>{row.registrationIp}</td>
                    <td>{formatNumber(row._count.registrationIp)}</td>
                    <td>
                      <form
                        action={resetIpLimitAction}
                        style={{ display: "flex", gap: "0.4rem" }}
                      >
                        <input
                          type="hidden"
                          name="ipAddress"
                          value={row.registrationIp ?? ""}
                        />
                        <input
                          name="reason"
                          type="text"
                          placeholder="sebep"
                          style={{
                            padding: "0.3rem 0.5rem",
                            border: "1px solid #e2e8f0",
                            borderRadius: 8,
                          }}
                          aria-label="Limit sıfırlama sebebi"
                        />
                        <button type="submit" className="admin-btn secondary small">
                          Limiti sıfırla
                        </button>
                      </form>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Sıfırlama Geçmişi</h2>
        </div>
        <div className="card-body table-scroll">
          {resets.length === 0 ? (
            <div className="admin-empty">Kayıt yok.</div>
          ) : (
            <table className="data-table">
              <thead>
                <tr>
                  <th>IP</th>
                  <th>Önceki hesap</th>
                  <th>Sebep</th>
                  <th>Tarih</th>
                </tr>
              </thead>
              <tbody>
                {resets.map((reset) => (
                  <tr key={reset.id}>
                    <td>{reset.ipAddress}</td>
                    <td>{formatNumber(reset.accountsBeforeReset)}</td>
                    <td>{reset.reason ?? "-"}</td>
                    <td>{formatDateTime(reset.createdAt)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </>
  );
}
