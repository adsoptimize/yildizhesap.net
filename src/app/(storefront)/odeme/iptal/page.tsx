import type { Metadata } from "next";
import Link from "next/link";
import { CART_PATH, ORDER_TRACK_PATH } from "@/lib/shop/constants";

export const metadata: Metadata = {
  title: { absolute: "Ödeme İptal Edildi | yildizhesap.net" },
  robots: { index: false, follow: false },
};

type PageProps = {
  searchParams: Promise<{ order?: string }>;
};

export default async function PaymentCancelPage({ searchParams }: PageProps) {
  const { order: orderCode } = await searchParams;

  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>Ödeme tamamlanmadı</h1>
          <p>Sipariş kaydınız beklemede; ödemeyi tekrar deneyebilirsiniz.</p>
        </div>
      </section>

      <section className="shop-section">
        <div className="container">
          <div className="shop-panel shop-panel-narrow">
            {orderCode === undefined ? null : (
              <div className="shop-summary-row">
                <span>Sipariş kodu</span>
                <span className="shop-mono">{orderCode}</span>
              </div>
            )}
            <p className="shop-muted" style={{ marginTop: 16 }}>
              Tutar hesabınızdan çekildiyse ödeme onayı gelene kadar sipariş
              beklemede kalır ve onaylandığında teslimat otomatik yapılır. Sorun
              yaşarsanız sipariş kodunuzla bize yazın.
            </p>
            <div className="shop-actions" style={{ marginTop: 24 }}>
              <Link href={CART_PATH} className="shop-btn accent">
                <i className="fas fa-cart-shopping" /> Sepete Dön
              </Link>
              <Link href={ORDER_TRACK_PATH} className="shop-btn">
                <i className="fas fa-magnifying-glass" /> Sipariş Takip
              </Link>
              <Link href="/iletisim" className="shop-btn">
                <i className="fas fa-headset" /> Destek
              </Link>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
