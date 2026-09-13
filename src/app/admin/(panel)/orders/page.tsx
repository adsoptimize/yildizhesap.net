import type { Prisma } from "@prisma/client";
import Link from "next/link";
import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { requireAdmin } from "@/lib/auth/admin";
import { formatCurrency, formatDateTime, formatNumber } from "@/lib/admin/format";
import { prisma } from "@/lib/prisma";

const PAGE_SIZE = 25;

const ORDER_STATUS_LABELS: Readonly<Record<string, string>> = {
  pending: "Bekliyor",
  processing: "İşleniyor",
  completed: "Tamamlandı",
  cancelled: "İptal",
};

const DELIVERY_STATUS_LABELS: Readonly<Record<string, string>> = {
  pending: "Teslim edilmedi",
  partial: "Kısmi",
  delivered: "Teslim edildi",
  failed: "Başarısız",
};

const STATUS_BADGES: Readonly<Record<string, string>> = {
  pending: "warning",
  processing: "info",
  completed: "success",
  cancelled: "danger",
};

type SearchParams = {
  q?: string;
  status?: string;
  page?: string;
};

export default async function AdminOrdersPage({
  searchParams,
}: {
  searchParams: Promise<SearchParams>;
}) {
  const admin = await requireAdmin();
  const { q, status, page } = await searchParams;

  const search = q?.trim() ?? "";
  const currentPage = Math.max(Number.parseInt(page ?? "1", 10) || 1, 1);
  const statusFilter =
    status !== undefined && status in ORDER_STATUS_LABELS
      ? (status as Prisma.EnumOrderStatusFilter)
      : undefined;

  const where: Prisma.OrderWhereInput = {
    ...(search === ""
      ? {}
      : {
          OR: [
            { orderCode: { contains: search, mode: "insensitive" } },
            { productName: { contains: search, mode: "insensitive" } },
            { user: { username: { contains: search, mode: "insensitive" } } },
          ],
        }),
    ...(statusFilter === undefined ? {} : { status: statusFilter }),
  };

  const [total, orders, revenue] = await Promise.all([
    prisma.order.count({ where }),
    prisma.order.findMany({
      where,
      orderBy: { createdAt: "desc" },
      skip: (currentPage - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
      select: {
        id: true,
        orderCode: true,
        productName: true,
        category: true,
        quantity: true,
        totalPrice: true,
        status: true,
        deliveryStatus: true,
        orderDate: true,
        isGuestOrder: true,
        customerName: true,
        email: true,
        user: { select: { username: true, email: true } },
      },
    }),
    prisma.order.aggregate({ where, _sum: { totalPrice: true } }),
  ]);

  const pageCount = Math.max(Math.ceil(total / PAGE_SIZE), 1);

  return (
    <>
      <AdminPageHeader
        title="Sipariş Yönetimi"
        subtitle={`${formatNumber(total)} sipariş · ${formatCurrency(
          revenue._sum.totalPrice?.toString() ?? "0",
        )} toplam`}
        userName={`${admin.firstName} ${admin.lastName}`}
      />

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Filtrele</h2>
        </div>
        <div className="card-body">
          <form className="form-grid" method="get">
            <div className="form-field">
              <label htmlFor="q">Sipariş kodu / ürün / kullanıcı</label>
              <input id="q" name="q" type="text" defaultValue={search} />
            </div>
            <div className="form-field">
              <label htmlFor="status">Durum</label>
              <select id="status" name="status" defaultValue={status ?? ""}>
                <option value="">Tümü</option>
                {Object.entries(ORDER_STATUS_LABELS).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </div>
            <div className="form-field" style={{ justifyContent: "flex-end" }}>
              <button type="submit" className="admin-btn secondary">
                <i className="fas fa-filter" /> Uygula
              </button>
            </div>
          </form>
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Siparişler</h2>
          <span className="admin-badge info">
            Sayfa {currentPage} / {pageCount}
          </span>
        </div>
        <div className="card-body table-scroll">
          {orders.length === 0 ? (
            <div className="admin-empty">Sipariş bulunamadı.</div>
          ) : (
            <table className="data-table">
              <thead>
                <tr>
                  <th>Kod</th>
                  <th>Ürün</th>
                  <th>Kullanıcı</th>
                  <th>Adet</th>
                  <th>Tutar</th>
                  <th>Durum</th>
                  <th>Teslimat</th>
                  <th>Tarih</th>
                </tr>
              </thead>
              <tbody>
                {orders.map((order) => (
                  <tr key={order.id}>
                    <td>
                      <Link href={`/admin/orders/${order.orderCode}`}>
                        {order.orderCode}
                      </Link>
                    </td>
                    <td>
                      {order.productName}
                      <div style={{ fontSize: "0.75rem", color: "#94a3b8" }}>
                        {order.category}
                      </div>
                    </td>
                    <td>
                      {order.user?.username ??
                        `${order.customerName ?? "Misafir"} (misafir)`}
                      <div style={{ fontSize: "0.75rem", color: "#94a3b8" }}>
                        {order.user?.email ?? order.email ?? "-"}
                      </div>
                    </td>
                    <td>{formatNumber(order.quantity)}</td>
                    <td>{formatCurrency(order.totalPrice.toString())}</td>
                    <td>
                      <span
                        className={`admin-badge ${STATUS_BADGES[order.status] ?? "info"}`}
                      >
                        {ORDER_STATUS_LABELS[order.status] ?? order.status}
                      </span>
                    </td>
                    <td>
                      {DELIVERY_STATUS_LABELS[order.deliveryStatus] ??
                        order.deliveryStatus}
                    </td>
                    <td>{formatDateTime(order.orderDate)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>

      {pageCount > 1 ? (
        <div style={{ display: "flex", gap: "0.5rem", flexWrap: "wrap" }}>
          {Array.from({ length: pageCount }, (_, index) => index + 1).map(
            (pageNumber) => {
              const params = new URLSearchParams();
              if (search !== "") params.set("q", search);
              if (status !== undefined && status !== "") params.set("status", status);
              params.set("page", String(pageNumber));

              return (
                <Link
                  key={pageNumber}
                  href={`/admin/orders?${params.toString()}`}
                  className={`admin-btn small${
                    pageNumber === currentPage ? "" : " secondary"
                  }`}
                >
                  {pageNumber}
                </Link>
              );
            },
          )}
        </div>
      ) : null}
    </>
  );
}
