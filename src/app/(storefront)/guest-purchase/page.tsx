import type { Metadata } from "next";
import Link from "next/link";
import { AddToCartForm } from "@/components/AddToCartForm";
import { formatCurrency } from "@/lib/admin/format";
import { CART_PATH, ORDER_TRACK_PATH } from "@/lib/shop/constants";
import { prisma } from "@/lib/prisma";

export const metadata: Metadata = {
  title: { absolute: "Hesap Satın Al | Üye Olmadan Hızlı Alışveriş" },
  description:
    "Üye olmadan hesap satın alın. Ödeme sonrası hesap bilgileri anında teslim edilir.",
  robots: { index: false, follow: true },
};

export default async function GuestPurchasePage() {
  const products = await prisma.account.findMany({
    where: { status: "active" },
    orderBy: [{ isFeatured: "desc" }, { id: "asc" }],
    select: {
      id: true,
      title: true,
      seoSlug: true,
      price: true,
      platform: true,
      category: { select: { name: true } },
      _count: { select: { stock: { where: { isSold: false } } } },
    },
  });

  const inStock = products.filter((product) => product._count.stock > 0);

  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>Üye Olmadan Hesap Satın Al</h1>
          <p>
            Sepete ekleyin, e-posta adresinizi girin ve ödeme sonrası hesap
            bilgilerinizi anında alın.
          </p>
        </div>
      </section>

      <section className="shop-section">
        <div className="container">
          <div className="shop-alert info">
            Üyelik gerekmez. Ödeme sonrası hesap bilgileriniz{" "}
            <Link href={ORDER_TRACK_PATH} className="shop-link">
              sipariş takip
            </Link>{" "}
            sayfasında sipariş kodu ve e-posta ile görüntülenir.
          </div>

          <div className="shop-panel">
            <h2 className="shop-panel-title">
              <i className="fas fa-bolt" /> Anında Teslim Edilebilir Hesaplar
            </h2>

            {inStock.length === 0 ? (
              <div className="shop-empty">
                <i className="fas fa-box-open" />
                <h3>Şu anda stokta hesap yok</h3>
                <p className="shop-muted">
                  Stok girişleri yapıldığında hesaplar burada listelenecek.
                </p>
              </div>
            ) : (
              <div className="shop-table-wrap">
                <table className="shop-table">
                  <thead>
                    <tr>
                      <th>Hesap</th>
                      <th>Kategori</th>
                      <th>Stok</th>
                      <th>Fiyat</th>
                      <th />
                    </tr>
                  </thead>
                  <tbody>
                    {inStock.map((product) => (
                      <tr key={product.id}>
                        <td>
                          {product.seoSlug === null ? (
                            product.title
                          ) : (
                            <Link
                              href={`/${product.seoSlug}`}
                              className="shop-link"
                            >
                              {product.title}
                            </Link>
                          )}
                          <div className="shop-hint">{product.platform}</div>
                        </td>
                        <td>{product.category.name}</td>
                        <td>{product._count.stock}</td>
                        <td>{formatCurrency(product.price.toString())}</td>
                        <td>
                          <AddToCartForm
                            accountId={product.id}
                            availableStock={product._count.stock}
                            variant="compact"
                          />
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}

            <div className="shop-actions" style={{ marginTop: 20 }}>
              <Link href={CART_PATH} className="shop-btn accent">
                <i className="fas fa-cart-shopping" /> Sepete Git
              </Link>
              <Link href="/tum-hesaplar" className="shop-btn">
                <i className="fas fa-store" /> Tüm Hesaplar
              </Link>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
