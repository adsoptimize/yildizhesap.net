import type { Metadata } from "next";
import Link from "next/link";
import { formatCurrency } from "@/lib/admin/format";
import { getCurrentCustomer } from "@/lib/auth/customer";
import { ACCOUNT_PATH, ORDER_TRACK_PATH } from "@/lib/shop/constants";
import { prisma } from "@/lib/prisma";

export const metadata: Metadata = {
  title: { absolute: "Ödeme Alındı | yildizhesap.net" },
  robots: { index: false, follow: false },
};

type PageProps = {
  searchParams: Promise<{ order?: string }>;
};

export default async function PaymentSuccessPage({ searchParams }: PageProps) {
  const { order: orderCode } = await searchParams;
  const customer = await getCurrentCustomer();

  const order =
    orderCode === undefined
      ? null
      : await prisma.order.findUnique({
          where: { orderCode },
          select: {
            orderCode: true,
            productName: true,
            totalPrice: true,
            status: true,
            deliveryStatus: true,
            email: true,
            _count: { select: { deliveredAccounts: true } },
          },
        });

  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>Ödeme adımı tamamlandı</h1>
          <p>
            Ödemeniz onaylandığında hesap bilgileri otomatik olarak teslim edilir.
          </p>
        </div>
      </section>

      <section className="shop-section">
        <div className="container">
          <div className="shop-panel shop-panel-narrow">
            {order === null ? (
              <div className="shop-alert info">
                Sipariş bilgisi bulunamadı. Sipariş kodunuzla takip sayfasından
                sorgulayabilirsiniz.
              </div>
            ) : (
              <>
                <div className="shop-summary-row">
                  <span>Sipariş kodu</span>
                  <span className="shop-mono">{order.orderCode}</span>
                </div>
                <div className="shop-summary-row">
                  <span>Ürün</span>
                  <span>{order.productName}</span>
                </div>
                <div className="shop-summary-row">
                  <span>Tutar</span>
                  <span>{formatCurrency(order.totalPrice.toString())}</span>
                </div>
                <div className="shop-summary-row">
                  <span>Teslimat</span>
                  <span>
                    {order.deliveryStatus === "delivered"
                      ? "Teslim edildi"
                      : "Hazırlanıyor"}
                  </span>
                </div>
                <p className="shop-hint" style={{ marginTop: 16 }}>
                  Sipariş kodunuzu saklayın. Kripto ödemelerde blok onayı birkaç
                  dakika sürebilir; teslimat otomatik olarak tamamlanır.
                </p>
              </>
            )}

            <div className="shop-actions" style={{ marginTop: 24 }}>
              {customer === null ? (
                <Link href={ORDER_TRACK_PATH} className="shop-btn accent">
                  <i className="fas fa-magnifying-glass" /> Siparişimi Takip Et
                </Link>
              ) : (
                <Link href={ACCOUNT_PATH} className="shop-btn accent">
                  <i className="fas fa-box" /> Siparişlerim
                </Link>
              )}
              <Link href="/tum-hesaplar" className="shop-btn">
                <i className="fas fa-store" /> Hesaplara Dön
              </Link>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
