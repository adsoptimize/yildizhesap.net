import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { requireAdmin } from "@/lib/auth/admin";
import { formatDateTime, formatNumber } from "@/lib/admin/format";
import { prisma } from "@/lib/prisma";
import {
  cleanupExpiredSessionsAction,
  invalidateSessionAction,
  invalidateUserSessionsAction,
} from "./actions";

const SESSION_LIMIT = 100;
const USER_AGENT_PREVIEW_LENGTH = 60;

export default async function AdminSessionsPage() {
  const admin = await requireAdmin();

  const now = new Date();

  const [sessions, activeCount, expiredCount] = await Promise.all([
    prisma.activeSession.findMany({
      orderBy: { lastActivity: "desc" },
      take: SESSION_LIMIT,
      select: {
        id: true,
        ipAddress: true,
        userAgent: true,
        createdAt: true,
        lastActivity: true,
        expiresAt: true,
        isActive: true,
        invalidatedBy: true,
        user: { select: { id: true, username: true, isAdmin: true } },
      },
    }),
    prisma.activeSession.count({
      where: { isActive: true, expiresAt: { gte: now } },
    }),
    prisma.activeSession.count({
      where: { isActive: true, expiresAt: { lt: now } },
    }),
  ]);

  return (
    <>
      <AdminPageHeader
        title="Session Yönetimi"
        subtitle={`${formatNumber(activeCount)} canlı oturum · ${formatNumber(expiredCount)} süresi geçmiş`}
        userName={`${admin.firstName} ${admin.lastName}`}
        actions={
          <form action={cleanupExpiredSessionsAction}>
            <button type="submit" className="admin-btn secondary">
              <i className="fas fa-broom" /> Süresi geçenleri kapat
            </button>
          </form>
        }
      />

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Oturumlar</h2>
        </div>
        <div className="card-body table-scroll">
          {sessions.length === 0 ? (
            <div className="admin-empty">Oturum kaydı yok.</div>
          ) : (
            <table className="data-table">
              <thead>
                <tr>
                  <th>Kullanıcı</th>
                  <th>IP</th>
                  <th>Cihaz</th>
                  <th>Son aktivite</th>
                  <th>Bitiş</th>
                  <th>Durum</th>
                  <th>İşlem</th>
                </tr>
              </thead>
              <tbody>
                {sessions.map((session) => {
                  const live =
                    session.isActive && session.expiresAt.getTime() >= now.getTime();

                  return (
                    <tr key={session.id}>
                      <td>
                        {session.user.username}
                        {session.user.isAdmin ? (
                          <span className="admin-badge info"> admin</span>
                        ) : null}
                      </td>
                      <td>{session.ipAddress}</td>
                      <td style={{ fontSize: "0.75rem", color: "#64748b" }}>
                        {session.userAgent.slice(0, USER_AGENT_PREVIEW_LENGTH)}
                      </td>
                      <td>{formatDateTime(session.lastActivity)}</td>
                      <td>{formatDateTime(session.expiresAt)}</td>
                      <td>
                        <span
                          className={`admin-badge ${live ? "success" : "danger"}`}
                        >
                          {live
                            ? "Canlı"
                            : (session.invalidatedBy ?? "süresi geçmiş")}
                        </span>
                      </td>
                      <td>
                        {live ? (
                          <div style={{ display: "flex", gap: "0.4rem" }}>
                            <form action={invalidateSessionAction}>
                              <input type="hidden" name="id" value={session.id} />
                              <button type="submit" className="admin-btn danger small">
                                Kapat
                              </button>
                            </form>
                            <form action={invalidateUserSessionsAction}>
                              <input
                                type="hidden"
                                name="userId"
                                value={session.user.id}
                              />
                              <button
                                type="submit"
                                className="admin-btn secondary small"
                              >
                                Tümü
                              </button>
                            </form>
                          </div>
                        ) : null}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </>
  );
}
