import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { formatCurrency } from "@/lib/admin/format";
import { getCurrentCustomer } from "@/lib/auth/customer";
import { getPaymentAvailability } from "@/lib/payments/settings";
import { getCart } from "@/lib/shop/cart";
import { CART_PATH, type PaymentMethod } from "@/lib/shop/constants";
import { CheckoutForm } from "./CheckoutForm";

export const metadata: Metadata = {
  title: { absolute: "Ödeme | yildizhesap.net" },
  description: "Siparişinizi tamamlayın.",
  robots: { index: false, follow: false },
};

export default async function CheckoutPage() {
  const [cart, customer, availability] = await Promise.all([
    getCart(),
    getCurrentCustomer(),
    getPaymentAvailability(),
  ]);

  if (cart.items.length === 0) {
    redirect(CART_PATH);
  }

  const availableMethods: PaymentMethod[] = [];

  if (availability.cryptomus) {
    availableMethods.push("cryptomus");
  }

  if (availability.shopier) {
    availableMethods.push("shopier");
  }

  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>Ödeme</h1>
          <p>Bilgilerinizi kontrol edip ödeme yöntemini seçin.</p>
        </div>
      </section>

      <section className="shop-section">
        <div className="container">
          <div className="shop-layout">
            <div className="shop-panel">
              <h2 className="shop-panel-title">
                <i className="fas fa-user" /> Sipariş Bilgileri
              </h2>
              <CheckoutForm
                isLoggedIn={customer !== null}
                availableMethods={availableMethods}
                customerName={
                  customer === null
                    ? ""
                    : `${customer.firstName} ${customer.lastName}`.trim()
                }
                customerEmail={customer?.email ?? ""}
              />
              {customer === null ? (
                <p className="shop-hint" style={{ marginTop: 18 }}>
                  Üyeyseniz <Link href="/giris-yap" className="shop-link">giriş
                  yapın</Link>; siparişleriniz panelinizde toplanır.
                </p>
              ) : null}
            </div>

            <div className="shop-panel">
              <h2 className="shop-panel-title">
                <i className="fas fa-receipt" /> Sipariş Özeti
              </h2>
              {cart.items.map((item) => (
                <div className="shop-summary-row" key={item.accountId}>
                  <span>
                    {item.title} × {item.quantity}
                  </span>
                  <span>{formatCurrency(item.lineTotal)}</span>
                </div>
              ))}
              <div className="shop-summary-total">
                <span>Toplam</span>
                <strong>{formatCurrency(cart.totalPrice)}</strong>
              </div>
              <Link
                href={CART_PATH}
                className="shop-btn"
                style={{ justifyContent: "center", marginTop: 18, width: "100%" }}
              >
                <i className="fas fa-pen" /> Sepeti Düzenle
              </Link>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
