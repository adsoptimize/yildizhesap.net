import Link from "next/link";
import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { requireAdmin } from "@/lib/auth/admin";
import { formatCurrency, formatDateTime, formatNumber } from "@/lib/admin/format";
import { prisma } from "@/lib/prisma";

const RECENT_ACTIVITY_LIMIT = 8;
const RECENT_ORDER_LIMIT = 6;

export default async function AdminDashboardPage() {
  const admin = await requireAdmin();

  const [
    userCount,
    accountCount,
    activeAccountCount,
    orderCount,
    pendingOrderCount,
    openTicketCount,
    revenue,
    stockTotal,
    recentActivity,
    recentOrders,
  ] = await Promise.all([
    prisma.user.count(),
    prisma.account.count(),
    prisma.account.count({ where: { status: "active" } }),
    prisma.order.count(),
    prisma.order.count({ where: { status: "pending" } }),
    prisma.supportTicket.count({ where: { status: { in: ["open", "in_progress"] } } }),
    prisma.order.aggregate({
      where: { status: "completed" },
      _sum: { totalPrice: true },
    }),
    prisma.account.aggregate({ _sum: { stockQuantity: true } }),
    prisma.userActivityLog.findMany({
      orderBy: { createdAt: "desc" },
      take: RECENT_ACTIVITY_LIMIT,
      select: {
        id: true,
        activityType: true,
        description: true,
        createdAt: true,
        user: { select: { username: true } },
      },
    }),
    prisma.order.findMany({
      orderBy: { createdAt: "desc" },
      take: RECENT_ORDER_LIMIT,
      select: {
        id: true,
        orderCode: true,
        productName: true,
        totalPrice: true,
        status: true,
        createdAt: true,
        customerName: true,
        user: { select: { username: true } },
      },
    }),
  ]);

  return (
    <>
      <AdminPageHeader
        title="Dashboard"
        subtitle={`Hoş geldin, ${admin.firstName}`}
        userName={`${admin.firstName} ${admin.lastName}`}
      />

      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-header">
            <span className="stat-title">Kullanıcı</span>
            <div className="stat-icon users">
              <i className="fas fa-user-friends" />
            </div>
          </div>
          <div className="stat-value">{formatNumber(userCount)}</div>
          <div className="stat-change">Kayıtlı toplam kullanıcı</div>
        </div>

        <div className="stat-card">
          <div className="stat-header">
            <span className="stat-title">Hesap / Ürün</span>
            <div className="stat-icon accounts">
              <i className="fas fa-box" />
            </div>
          </div>
          <div className="stat-value">{formatNumber(accountCount)}</div>
          <div className="stat-change">
            {formatNumber(activeAccountCount)} aktif ·{" "}
            {formatNumber(stockTotal._sum.stockQuantity ?? 0)} stok
          </div>
        </div>

        <div className="stat-card">
          <div className="stat-header">
            <span className="stat-title">Sipariş</span>
            <div className="stat-icon orders">
              <i className="fas fa-shopping-cart" />
            </div>
          </div>
          <div className="stat-value">{formatNumber(orderCount)}</div>
          <div className="stat-change">
            {formatNumber(pendingOrderCount)} bekleyen ·{" "}
            {formatNumber(openTicketCount)} açık talep
          </div>
        </div>

        <div className="stat-card">
          <div className="stat-header">
            <span className="stat-title">Ciro</span>
            <div className="stat-icon revenue">
              <i className="fas fa-lira-sign" />
            </div>
          </div>
          <div className="stat-value">
            {formatCurrency(revenue._sum.totalPrice?.toString() ?? "0")}
          </div>
          <div className="stat-change">Tamamlanan siparişler</div>
        </div>
      </div>

      <div className="quick-actions">
        <Link href="/admin/accounts" className="action-card">
          <div className="action-icon accounts">
            <i className="fas fa-plus" />
          </div>
          <div className="action-title">Hesap Ekle</div>
          <div className="action-subtitle">Yeni ürün ve stok girişi</div>
        </Link>
        <Link href="/admin/categories" className="action-card">
          <div className="action-icon settings">
            <i className="fas fa-tags" />
          </div>
          <div className="action-title">Kategoriler</div>
          <div className="action-subtitle">SEO slug ve görünürlük</div>
        </Link>
        <Link href="/admin/orders" className="action-card">
          <div className="action-icon users">
            <i className="fas fa-truck" />
          </div>
          <div className="action-title">Siparişler</div>
          <div className="action-subtitle">Teslimat ve durum yönetimi</div>
        </Link>
        <Link href="/admin/analytics" className="action-card">
          <div className="action-icon analytics">
            <i className="fas fa-chart-line" />
          </div>
          <div className="action-title">Analitik</div>
          <div className="action-subtitle">Satış ve trafik raporları</div>
        </Link>
      </div>

      <div className="content-grid">
        <div className="content-card">
          <div className="card-header">
            <h2 className="card-title">Son Siparişler</h2>
            <Link href="/admin/orders" className="admin-btn secondary small">
              Tümü
            </Link>
          </div>
          <div className="card-body table-scroll">
            {recentOrders.length === 0 ? (
              <div className="admin-empty">Henüz sipariş yok.</div>
            ) : (
              <table className="data-table">
                <thead>
                  <tr>
                    <th>Sipariş</th>
                    <th>Ürün</th>
                    <th>Kullanıcı</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                  </tr>
                </thead>
                <tbody>
                  {recentOrders.map((order) => (
                    <tr key={order.id}>
                      <td>{order.orderCode}</td>
                      <td>{order.productName}</td>
                      <td>
                        {order.user?.username ??
                          `${order.customerName ?? "Misafir"} (misafir)`}
                      </td>
                      <td>{formatCurrency(order.totalPrice.toString())}</td>
                      <td>
                        <span className="admin-badge info">{order.status}</span>
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
            <h2 className="card-title">Son Hareketler</h2>
          </div>
          <div className="card-body">
            {recentActivity.length === 0 ? (
              <div className="admin-empty">Kayıt yok.</div>
            ) : (
              recentActivity.map((activity) => (
                <div className="activity-item" key={activity.id}>
                  <div>
                    <div className="activity-title">
                      {activity.user?.username ?? "sistem"} · {activity.activityType}
                    </div>
                    <div className="activity-subtitle">{activity.description}</div>
                    <div className="activity-time">
                      {formatDateTime(activity.createdAt)}
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </div>
    </>
  );
}
