import type { Metadata } from "next";
import Link from "next/link";
import { formatCurrency } from "@/lib/admin/format";
import { getCart } from "@/lib/shop/cart";
import { CHECKOUT_PATH, MAX_ITEM_QUANTITY } from "@/lib/shop/constants";
import {
  clearCartAction,
  removeCartItemAction,
  updateCartItemAction,
} from "./actions";

export const metadata: Metadata = {
  title: { absolute: "Sepetim | yildizhesap.net" },
  description: "Sepetinizdeki hesapları görüntüleyin ve ödemeye geçin.",
  robots: { index: false, follow: false },
};

export default async function CartPage() {
  const cart = await getCart();

  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>Sepetim</h1>
          <p>Seçtiğiniz hesapları kontrol edip ödeme adımına geçin.</p>
        </div>
      </section>

      <section className="shop-section">
        <div className="container">
          {cart.removedTitles.length > 0 ? (
            <div className="shop-alert error">
              Stok tükendiği için sepetten çıkarıldı:{" "}
              {cart.removedTitles.join(", ")}
            </div>
          ) : null}

          {cart.items.length === 0 ? (
            <div className="shop-panel">
              <div className="shop-empty">
                <i className="fas fa-cart-shopping" />
                <h3>Sepetiniz boş</h3>
                <p className="shop-muted">
                  Hesap katalogundan ürün ekleyerek satın almaya başlayabilirsiniz.
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
            </div>
          ) : (
            <div className="shop-layout">
              <div className="shop-panel">
                <h2 className="shop-panel-title">
                  <i className="fas fa-list" /> Ürünler
                </h2>
                <div className="shop-table-wrap">
                  <table className="shop-table">
                    <thead>
                      <tr>
                        <th>Ürün</th>
                        <th>Birim Fiyat</th>
                        <th>Adet</th>
                        <th>Tutar</th>
                        <th />
                      </tr>
                    </thead>
                    <tbody>
                      {cart.items.map((item) => (
                        <tr key={item.accountId}>
                          <td>
                            {item.seoSlug === null ? (
                              item.title
                            ) : (
                              <Link href={`/${item.seoSlug}`} className="shop-link">
                                {item.title}
                              </Link>
                            )}
                            <div className="shop-hint">
                              {item.categoryName} · Stok: {item.availableStock}
                            </div>
                          </td>
                          <td>{formatCurrency(item.unitPrice)}</td>
                          <td>
                            <form action={updateCartItemAction} className="shop-qty">
                              <input
                                type="hidden"
                                name="accountId"
                                value={item.accountId}
                              />
                              <input
                                type="number"
                                name="quantity"
                                min={1}
                                max={Math.min(MAX_ITEM_QUANTITY, item.availableStock)}
                                defaultValue={item.quantity}
                                aria-label={`${item.title} adedi`}
                              />
                              <button type="submit" className="shop-btn">
                                <i className="fas fa-rotate" />
                              </button>
                            </form>
                          </td>
                          <td>{formatCurrency(item.lineTotal)}</td>
                          <td>
                            <form action={removeCartItemAction}>
                              <input
                                type="hidden"
                                name="accountId"
                                value={item.accountId}
                              />
                              <button type="submit" className="shop-btn danger">
                                <i className="fas fa-trash" />
                              </button>
                            </form>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                <div className="shop-actions" style={{ marginTop: 20 }}>
                  <form action={clearCartAction}>
                    <button type="submit" className="shop-btn danger">
                      <i className="fas fa-xmark" /> Sepeti Boşalt
                    </button>
                  </form>
                  <Link href="/tum-hesaplar" className="shop-btn">
                    <i className="fas fa-plus" /> Alışverişe Devam Et
                  </Link>
                </div>
              </div>

              <div className="shop-panel">
                <h2 className="shop-panel-title">
                  <i className="fas fa-receipt" /> Özet
                </h2>
                <div className="shop-summary-row">
                  <span>Ürün çeşidi</span>
                  <span>{cart.items.length}</span>
                </div>
                <div className="shop-summary-row">
                  <span>Toplam adet</span>
                  <span>{cart.totalQuantity}</span>
                </div>
                <div className="shop-summary-total">
                  <span>Toplam</span>
                  <strong>{formatCurrency(cart.totalPrice)}</strong>
                </div>
                <Link
                  href={CHECKOUT_PATH}
                  className="shop-btn accent"
                  style={{ justifyContent: "center", marginTop: 20, width: "100%" }}
                >
                  <i className="fas fa-lock" /> Ödemeye Geç
                </Link>
                <p className="shop-hint" style={{ marginTop: 14 }}>
                  Ödeme sonrası hesap bilgileri otomatik teslim edilir. Üye
                  olmadan da satın alabilirsiniz.
                </p>
              </div>
            </div>
          )}
        </div>
      </section>
    </main>
  );
}
