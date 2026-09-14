import Link from "next/link";
import { notFound } from "next/navigation";
import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { requireAdmin } from "@/lib/auth/admin";
import {
  formatCurrency,
  formatDate,
  formatDateTime,
  formatNumber,
} from "@/lib/admin/format";
import { prisma } from "@/lib/prisma";
import {
  addOrderAccountAction,
  deleteOrderAccountAction,
  updateOrderStatusAction,
} from "../actions";
import { OrderDeleteButton } from "../OrderDeleteButton";

const ORDER_STATUSES = [
  { value: "pending", label: "Bekliyor" },
  { value: "processing", label: "İşleniyor" },
  { value: "completed", label: "Tamamlandı" },
  { value: "cancelled", label: "İptal" },
] as const;

const DELIVERY_STATUSES = [
  { value: "pending", label: "Teslim edilmedi" },
  { value: "partial", label: "Kısmi teslim" },
  { value: "delivered", label: "Teslim edildi" },
  { value: "failed", label: "Başarısız" },
] as const;

export default async function AdminOrderDetailPage({
  params,
}: {
  params: Promise<{ code: string }>;
}) {
  const admin = await requireAdmin();
  const { code } = await params;
  const orderCode = decodeURIComponent(code);

  const order = await prisma.order.findUnique({
    where: { orderCode },
    include: {
      user: {
        select: { id: true, username: true, email: true, phone: true },
      },
      deliveredAccounts: { orderBy: { id: "desc" } },
    },
  });

  if (order === null) {
    notFound();
  }

  return (
    <>
      <AdminPageHeader
        title={`Sipariş ${order.orderCode}`}
        subtitle={`${order.productName} · ${formatCurrency(order.totalPrice.toString())}`}
        userName={`${admin.firstName} ${admin.lastName}`}
        actions={
          <Link href="/admin/orders" className="admin-btn secondary">
            <i className="fas fa-arrow-left" /> Liste
          </Link>
        }
      />

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Sipariş Bilgileri</h2>
        </div>
        <div className="card-body table-scroll">
          <table className="data-table">
            <tbody>
              <tr>
                <th>Kullanıcı</th>
                <td>
                  {order.user === null ? (
                    <>
                      {order.customerName ?? "Misafir"}{" "}
                      <span className="admin-badge info">misafir sipariş</span>
                      {order.email === null ? "" : ` · ${order.email}`}
                      {order.phone === null ? "" : ` · ${order.phone}`}
                    </>
                  ) : (
                    <>
                      <Link href={`/admin/users?q=${order.user.username}`}>
                        {order.user.username}
                      </Link>{" "}
                      · {order.user.email}
                      {order.user.phone === null ? "" : ` · ${order.user.phone}`}
                    </>
                  )}
                </td>
              </tr>
              <tr>
                <th>Ödeme yöntemi</th>
                <td>
                  {order.paymentMethod === "shopier"
                    ? "Kredi kartı (Shopier)"
                    : order.paymentMethod === "cryptomus"
                      ? "Kripto para (Cryptomus)"
                      : "-"}
                </td>
              </tr>
              <tr>
                <th>Ürün</th>
                <td>
                  {order.productName} ({order.category})
                </td>
              </tr>
              <tr>
                <th>Adet / Birim</th>
                <td>
                  {formatNumber(order.quantity)} ×{" "}
                  {formatCurrency(order.unitPrice.toString())}
                </td>
              </tr>
              <tr>
                <th>Sipariş tarihi</th>
                <td>{formatDateTime(order.orderDate)}</td>
              </tr>
            </tbody>
          </table>

          <form
            action={updateOrderStatusAction}
            className="form-grid"
            style={{ marginTop: "1.25rem" }}
          >
            <input type="hidden" name="id" value={order.id} />
            <div className="form-field">
              <label htmlFor="status">Sipariş durumu</label>
              <select id="status" name="status" defaultValue={order.status}>
                {ORDER_STATUSES.map((item) => (
                  <option key={item.value} value={item.value}>
                    {item.label}
                  </option>
                ))}
              </select>
            </div>
            <div className="form-field">
              <label htmlFor="deliveryStatus">Teslimat durumu</label>
              <select
                id="deliveryStatus"
                name="deliveryStatus"
                defaultValue={order.deliveryStatus}
              >
                {DELIVERY_STATUSES.map((item) => (
                  <option key={item.value} value={item.value}>
                    {item.label}
                  </option>
                ))}
              </select>
            </div>
            <div className="form-field" style={{ justifyContent: "flex-end" }}>
              <button type="submit" className="admin-btn">
                <i className="fas fa-save" /> Durumu Güncelle
              </button>
            </div>
          </form>
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Teslim Edilen Hesap Bilgileri</h2>
        </div>
        <div className="card-body">
          {order.deliveredAccounts.length === 0 ? (
            <div className="admin-empty">Henüz teslim kaydı yok.</div>
          ) : (
            <div className="table-scroll">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>Kullanıcı</th>
                    <th>Şifre</th>
                    <th>E-posta</th>
                    <th>Mail şifresi</th>
                    <th>2FA</th>
                    <th>Açılış</th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  {order.deliveredAccounts.map((row) => (
                    <tr key={row.id}>
                      <td>{row.username}</td>
                      <td>{row.password}</td>
                      <td>{row.email ?? "-"}</td>
                      <td>{row.emailPassword ?? "-"}</td>
                      <td>{row.totpSecret ?? "-"}</td>
                      <td>{formatDate(row.accountCreatedDate)}</td>
                      <td>
                        <form action={deleteOrderAccountAction}>
                          <input type="hidden" name="id" value={row.id} />
                          <button type="submit" className="admin-btn danger small">
                            Sil
                          </button>
                        </form>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {(() => {
            const isDeletable =
              order.status !== "completed" &&
              order.deliveryStatus !== "delivered" &&
              order.deliveryStatus !== "partial" &&
              order.deliveredAccounts.length === 0;
            return isDeletable ? (
              <div
                style={{
                  marginTop: "1.25rem",
                  padding: "0.75rem 1rem",
                  border: "1px solid #fee2e2",
                  borderRadius: 8,
                  background: "#fef2f2",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "space-between",
                  gap: "1rem",
                  flexWrap: "wrap",
                }}
              >
                <div>
                  <strong>Bu sipariş henüz teslim edilmedi.</strong>
                  <div style={{ fontSize: "0.8rem", color: "#64748b" }}>
                    Test / hatalı / iptal siparişleri kalıcı silebilirsiniz.
                  </div>
                </div>
                <OrderDeleteButton
                  orderId={order.id}
                  orderCode={order.orderCode}
                />
              </div>
            ) : null;
          })()}

          <form
            action={addOrderAccountAction}
            className="form-grid"
            style={{ marginTop: "1.25rem" }}
          >
            <input type="hidden" name="orderCode" value={order.orderCode} />
            <div className="form-field">
              <label htmlFor="username">Kullanıcı adı</label>
              <input id="username" name="username" type="text" required />
            </div>
            <div className="form-field">
              <label htmlFor="password">Şifre</label>
              <input id="password" name="password" type="text" required />
            </div>
            <div className="form-field">
              <label htmlFor="email">E-posta</label>
              <input id="email" name="email" type="text" required />
            </div>
            <div className="form-field">
              <label htmlFor="emailPassword">E-posta şifresi</label>
              <input id="emailPassword" name="emailPassword" type="text" required />
            </div>
            <div className="form-field">
              <label htmlFor="totpSecret">2FA anahtarı</label>
              <input id="totpSecret" name="totpSecret" type="text" />
            </div>
            <div className="form-field">
              <label htmlFor="accountCreatedDate">Hesap açılış tarihi</label>
              <input
                id="accountCreatedDate"
                name="accountCreatedDate"
                type="date"
                required
              />
            </div>
            <div className="form-field" style={{ justifyContent: "flex-end" }}>
              <button type="submit" className="admin-btn secondary">
                <i className="fas fa-plus" /> Teslim Bilgisi Ekle
              </button>
            </div>
          </form>
        </div>
      </div>
    </>
  );
}
