import Link from "next/link";
import { formatCurrency, formatDateTime } from "@/lib/admin/format";
import { requireCustomer } from "@/lib/auth/customer";
import { ACCOUNT_PATH } from "@/lib/shop/constants";
import { prisma } from "@/lib/prisma";

const ORDER_STATUS_LABELS: Readonly<Record<string, string>> = {
  pending: "Beklemede",
  processing: "İşleniyor",
  completed: "Tamamlandı",
  cancelled: "İptal Edildi",
};

const DELIVERY_STATUS_LABELS: Readonly<Record<string, string>> = {
  pending: "Hazırlanıyor",
  partial: "Kısmi teslim",
  delivered: "Teslim edildi",
  failed: "Başarısız",
};

export default async function CustomerOrdersPage() {
  const customer = await requireCustomer();

  const orders = await prisma.order.findMany({
    where: { userId: customer.id },
    orderBy: { createdAt: "desc" },
    select: {
      id: true,
      orderCode: true,
      productName: true,
      quantity: true,
      totalPrice: true,
      status: true,
      deliveryStatus: true,
      createdAt: true,
      _count: { select: { deliveredAccounts: true } },
    },
  });

  const totalSpent = orders
    .filter((order) => order.status === "completed")
    .reduce((sum, order) => sum + Number(order.totalPrice), 0);

  return (
    <>
      <div className="shop-stat-grid">
        <div className="shop-stat">
          <span>Toplam sipariş</span>
          <strong>{orders.length}</strong>
        </div>
        <div className="shop-stat">
          <span>Tamamlanan</span>
          <strong>
            {orders.filter((order) => order.status === "completed").length}
          </strong>
        </div>
        <div className="shop-stat">
          <span>Toplam harcama</span>
          <strong>{formatCurrency(totalSpent)}</strong>
        </div>
      </div>

      <div className="shop-panel">
        <h2 className="shop-panel-title">
          <i className="fas fa-box" /> Siparişlerim
        </h2>

        {orders.length === 0 ? (
          <div className="shop-empty">
            <i className="fas fa-box-open" />
            <h3>Henüz sipariş vermemişsiniz</h3>
            <p className="shop-muted">
              Katalogdan hesap seçip ilk siparişinizi oluşturabilirsiniz.
            </p>
            <div
              className="shop-actions"
              style={{ justifyContent: "center", marginTop: 20 }}
            >
              <Link href="/tum-hesaplar" className="shop-btn accent">
                <i className="fas fa-store" /> Hesapları İncele
              </Link>
            </div>
          </div>
        ) : (
          <div className="shop-table-wrap">
            <table className="shop-table">
              <thead>
                <tr>
                  <th>Sipariş</th>
                  <th>Ürün</th>
                  <th>Adet</th>
                  <th>Tutar</th>
                  <th>Durum</th>
                  <th>Teslimat</th>
                  <th>Tarih</th>
                  <th />
                </tr>
              </thead>
              <tbody>
                {orders.map((order) => (
                  <tr key={order.id}>
                    <td className="shop-mono">{order.orderCode}</td>
                    <td>{order.productName}</td>
                    <td>{order.quantity}</td>
                    <td>{formatCurrency(order.totalPrice.toString())}</td>
                    <td>
                      <span className={`shop-badge ${order.status}`}>
                        {ORDER_STATUS_LABELS[order.status] ?? order.status}
                      </span>
                    </td>
                    <td>
                      {DELIVERY_STATUS_LABELS[order.deliveryStatus] ??
                        order.deliveryStatus}
                      {order._count.deliveredAccounts > 0
                        ? ` (${order._count.deliveredAccounts})`
                        : ""}
                    </td>
                    <td>{formatDateTime(order.createdAt)}</td>
                    <td>
                      <Link
                        href={`${ACCOUNT_PATH}/siparis/${order.orderCode}`}
                        className="shop-btn"
                      >
                        <i className="fas fa-eye" /> Detay
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </>
  );
}
