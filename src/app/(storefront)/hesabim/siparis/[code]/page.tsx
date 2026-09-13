import Link from "next/link";
import { notFound } from "next/navigation";
import { CopyButton } from "@/components/CopyButton";
import { formatCurrency, formatDate, formatDateTime } from "@/lib/admin/format";
import { requireCustomer } from "@/lib/auth/customer";
import { ACCOUNT_PATH } from "@/lib/shop/constants";
import { prisma } from "@/lib/prisma";
import { TicketCreateForm } from "../../destek/TicketCreateForm";

type PageProps = {
  params: Promise<{ code: string }>;
};

const ORDER_STATUS_LABELS: Readonly<Record<string, string>> = {
  pending: "Beklemede",
  processing: "İşleniyor",
  completed: "Tamamlandı",
  cancelled: "İptal Edildi",
};

function credentialLine(row: {
  username: string;
  password: string;
  email: string | null;
  emailPassword: string | null;
  totpSecret: string | null;
}): string {
  return [
    row.username,
    row.password,
    row.email ?? "",
    row.emailPassword ?? "",
    row.totpSecret ?? "",
  ]
    .filter((part) => part !== "")
    .join(":");
}

export default async function CustomerOrderDetailPage({ params }: PageProps) {
  const [{ code }, customer] = await Promise.all([params, requireCustomer()]);

  const order = await prisma.order.findFirst({
    where: { orderCode: code, userId: customer.id },
    select: {
      orderCode: true,
      productName: true,
      category: true,
      quantity: true,
      unitPrice: true,
      totalPrice: true,
      status: true,
      deliveryStatus: true,
      paymentMethod: true,
      createdAt: true,
      deliveredAccounts: {
        orderBy: { id: "asc" },
        select: {
          id: true,
          username: true,
          password: true,
          email: true,
          emailPassword: true,
          totpSecret: true,
          accountCreatedDate: true,
        },
      },
      payment: { select: { status: true } },
    },
  });

  if (order === null) {
    notFound();
  }

  const existingTicket = await prisma.supportTicket.findUnique({
    where: { orderCode: code },
    select: { ticketCode: true },
  });

  return (
    <>
      <div className="shop-panel">
        <h2 className="shop-panel-title">
          <i className="fas fa-receipt" /> Sipariş {order.orderCode}
        </h2>
        <div className="shop-summary-row">
          <span>Ürün</span>
          <span>{order.productName}</span>
        </div>
        <div className="shop-summary-row">
          <span>Kategori</span>
          <span>{order.category}</span>
        </div>
        <div className="shop-summary-row">
          <span>Adet</span>
          <span>{order.quantity}</span>
        </div>
        <div className="shop-summary-row">
          <span>Ödeme yöntemi</span>
          <span>
            {order.paymentMethod === "shopier"
              ? "Kredi kartı (Shopier)"
              : order.paymentMethod === "cryptomus"
                ? "Kripto para"
                : "-"}
          </span>
        </div>
        <div className="shop-summary-row">
          <span>Ödeme durumu</span>
          <span>{order.payment?.status ?? "-"}</span>
        </div>
        <div className="shop-summary-row">
          <span>Sipariş durumu</span>
          <span className={`shop-badge ${order.status}`}>
            {ORDER_STATUS_LABELS[order.status] ?? order.status}
          </span>
        </div>
        <div className="shop-summary-row">
          <span>Tarih</span>
          <span>{formatDateTime(order.createdAt)}</span>
        </div>
        <div className="shop-summary-total">
          <span>Toplam</span>
          <strong>{formatCurrency(order.totalPrice.toString())}</strong>
        </div>

        <div className="shop-actions" style={{ marginTop: 20 }}>
          <Link href={ACCOUNT_PATH} className="shop-btn">
            <i className="fas fa-arrow-left" /> Siparişlerime Dön
          </Link>
          {order.deliveredAccounts.length === 0 ? null : (
            <a
              href={`${ACCOUNT_PATH}/siparis/${order.orderCode}/indir`}
              className="shop-btn accent"
            >
              <i className="fas fa-download" /> Hesap Bilgilerini İndir
            </a>
          )}
        </div>
      </div>

      <div className="shop-panel">
        <h2 className="shop-panel-title">
          <i className="fas fa-key" /> Hesap Bilgileri
        </h2>

        {order.deliveredAccounts.length === 0 ? (
          <div className="shop-alert info">
            {order.payment?.status === "paid"
              ? "Ödemeniz alındı, hesap bilgileri hazırlanıyor. Stok tamamlandığında burada görünecek."
              : "Ödeme onaylandığında hesap bilgileri otomatik olarak burada görünecek."}
          </div>
        ) : (
          <>
            <div className="shop-table-wrap">
              <table className="shop-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Kullanıcı adı</th>
                    <th>Şifre</th>
                    <th>E-posta</th>
                    <th>E-posta şifresi</th>
                    <th>2FA anahtarı</th>
                    <th>Açılış</th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  {order.deliveredAccounts.map((row, index) => (
                    <tr key={row.id}>
                      <td>{index + 1}</td>
                      <td className="shop-mono">{row.username}</td>
                      <td className="shop-mono">{row.password}</td>
                      <td className="shop-mono">{row.email ?? "-"}</td>
                      <td className="shop-mono">{row.emailPassword ?? "-"}</td>
                      <td className="shop-mono">
                        {row.totpSecret === null ? (
                          "-"
                        ) : (
                          <Link
                            href={`/2fa?secret=${encodeURIComponent(row.totpSecret)}`}
                            className="shop-link"
                            target="_blank"
                          >
                            {row.totpSecret} <i className="fas fa-key" />
                          </Link>
                        )}
                      </td>
                      <td>{formatDate(row.accountCreatedDate)}</td>
                      <td>
                        <CopyButton value={credentialLine(row)} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <p className="shop-hint" style={{ marginTop: 14 }}>
              Bilgileri güvenli bir yerde saklayın, kimseyle paylaşmayın. 2FA
              anahtarı varsa doğrulama uygulamanıza ekleyin.
            </p>
          </>
        )}
      </div>

      <div className="shop-panel">
        <h2 className="shop-panel-title">
          <i className="fas fa-headset" /> Destek
        </h2>
        {existingTicket === null ? (
          <TicketCreateForm
            orders={[{ orderCode: order.orderCode, productName: order.productName }]}
          />
        ) : (
          <div className="shop-alert info">
            Bu sipariş için {existingTicket.ticketCode} numaralı destek talebi
            mevcut.{" "}
            <Link href={`${ACCOUNT_PATH}/destek`} className="shop-link">
              Yazışmayı görüntüle
            </Link>
            .
          </div>
        )}
      </div>
    </>
  );
}
