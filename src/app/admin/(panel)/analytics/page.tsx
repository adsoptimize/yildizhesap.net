import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { requireAdmin } from "@/lib/auth/admin";
import { formatCurrency, formatNumber } from "@/lib/admin/format";
import { prisma } from "@/lib/prisma";

const REPORT_WINDOW_DAYS = 30;
const MS_PER_DAY = 86_400_000;
const TOP_LIST_LIMIT = 10;

async function loadReport() {
  const since = new Date(Date.now() - REPORT_WINDOW_DAYS * MS_PER_DAY);

  const [
    windowOrders,
    windowRevenue,
    newUsers,
    topAccounts,
    categoryTotals,
    mostViewed,
    activityTotals,
  ] = await Promise.all([
    prisma.order.count({ where: { createdAt: { gte: since } } }),
    prisma.order.aggregate({
      where: { createdAt: { gte: since }, status: "completed" },
      _sum: { totalPrice: true },
    }),
    prisma.user.count({ where: { createdAt: { gte: since } } }),
    prisma.account.findMany({
      orderBy: { salesCount: "desc" },
      take: TOP_LIST_LIMIT,
      select: {
        id: true,
        title: true,
        salesCount: true,
        price: true,
        stockQuantity: true,
      },
    }),
    prisma.account.groupBy({
      by: ["categoryId"],
      _count: { categoryId: true },
      _sum: { salesCount: true, stockQuantity: true },
    }),
    prisma.account.findMany({
      orderBy: { views: "desc" },
      take: TOP_LIST_LIMIT,
      select: { id: true, title: true, views: true, seoSlug: true },
    }),
    prisma.userActivityLog.groupBy({
      by: ["activityType"],
      where: { createdAt: { gte: since } },
      _count: { activityType: true },
      orderBy: { _count: { activityType: "desc" } },
      take: TOP_LIST_LIMIT,
    }),
  ]);

  const categories = await prisma.category.findMany({
    where: { id: { in: categoryTotals.map((row) => row.categoryId) } },
    select: { id: true, name: true },
  });

  return {
    windowOrders,
    windowRevenue,
    newUsers,
    topAccounts,
    categoryTotals,
    mostViewed,
    activityTotals,
    categoryNameById: new Map(
      categories.map((category) => [category.id, category.name]),
    ),
  };
}

export default async function AdminAnalyticsPage() {
  const admin = await requireAdmin();
  const {
    windowOrders,
    windowRevenue,
    newUsers,
    topAccounts,
    categoryTotals,
    mostViewed,
    activityTotals,
    categoryNameById,
  } = await loadReport();

  return (
    <>
      <AdminPageHeader
        title="Analitik & Raporlar"
        subtitle={`Son ${REPORT_WINDOW_DAYS} gün`}
        userName={`${admin.firstName} ${admin.lastName}`}
      />

      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-header">
            <span className="stat-title">Sipariş</span>
            <div className="stat-icon orders">
              <i className="fas fa-shopping-cart" />
            </div>
          </div>
          <div className="stat-value">{formatNumber(windowOrders)}</div>
        </div>
        <div className="stat-card">
          <div className="stat-header">
            <span className="stat-title">Ciro</span>
            <div className="stat-icon revenue">
              <i className="fas fa-lira-sign" />
            </div>
          </div>
          <div className="stat-value">
            {formatCurrency(windowRevenue._sum.totalPrice?.toString() ?? "0")}
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-header">
            <span className="stat-title">Yeni kullanıcı</span>
            <div className="stat-icon users">
              <i className="fas fa-user-plus" />
            </div>
          </div>
          <div className="stat-value">{formatNumber(newUsers)}</div>
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">En Çok Satan Ürünler</h2>
        </div>
        <div className="card-body table-scroll">
          {topAccounts.length === 0 ? (
            <div className="admin-empty">Veri yok.</div>
          ) : (
            <table className="data-table">
              <thead>
                <tr>
                  <th>Ürün</th>
                  <th>Satış</th>
                  <th>Fiyat</th>
                  <th>Stok</th>
                </tr>
              </thead>
              <tbody>
                {topAccounts.map((account) => (
                  <tr key={account.id}>
                    <td>{account.title}</td>
                    <td>{formatNumber(account.salesCount)}</td>
                    <td>{formatCurrency(account.price.toString())}</td>
                    <td>{formatNumber(account.stockQuantity)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Kategori Dağılımı</h2>
        </div>
        <div className="card-body table-scroll">
          {categoryTotals.length === 0 ? (
            <div className="admin-empty">Veri yok.</div>
          ) : (
            <table className="data-table">
              <thead>
                <tr>
                  <th>Kategori</th>
                  <th>Ürün</th>
                  <th>Stok</th>
                  <th>Satış</th>
                </tr>
              </thead>
              <tbody>
                {categoryTotals.map((row) => (
                  <tr key={row.categoryId}>
                    <td>{categoryNameById.get(row.categoryId) ?? row.categoryId}</td>
                    <td>{formatNumber(row._count.categoryId)}</td>
                    <td>{formatNumber(row._sum.stockQuantity ?? 0)}</td>
                    <td>{formatNumber(row._sum.salesCount ?? 0)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>

      <div className="content-grid">
        <div className="content-card">
          <div className="card-header">
            <h2 className="card-title">En Çok Görüntülenen</h2>
          </div>
          <div className="card-body table-scroll">
            {mostViewed.length === 0 ? (
              <div className="admin-empty">Veri yok.</div>
            ) : (
              <table className="data-table">
                <thead>
                  <tr>
                    <th>Ürün</th>
                    <th>Görüntüleme</th>
                  </tr>
                </thead>
                <tbody>
                  {mostViewed.map((account) => (
                    <tr key={account.id}>
                      <td>{account.title}</td>
                      <td>{formatNumber(account.views)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>

        <div className="content-card">
          <div className="card-header">
            <h2 className="card-title">Aktivite Türleri</h2>
          </div>
          <div className="card-body table-scroll">
            {activityTotals.length === 0 ? (
              <div className="admin-empty">Veri yok.</div>
            ) : (
              <table className="data-table">
                <thead>
                  <tr>
                    <th>Tür</th>
                    <th>Adet</th>
                  </tr>
                </thead>
                <tbody>
                  {activityTotals.map((row) => (
                    <tr key={row.activityType}>
                      <td>{row.activityType}</td>
                      <td>{formatNumber(row._count.activityType)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
