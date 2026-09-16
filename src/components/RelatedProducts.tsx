import Link from "next/link";
import type { ProductCard } from "@/lib/db/queries";

/**
 * "Related accounts" block for product detail pages. Two jobs:
 *  - gives every product page outbound internal links, so link equity flows
 *    across the catalog instead of dead-ending on leaf pages
 *  - keeps shoppers moving when the account they opened is out of stock
 *
 * Server component; card markup mirrors `ProductGrid`'s item so both share the
 * existing `.account-item` styles.
 */

function formatPrice(price: string): string {
  const value = Number(price);
  if (!Number.isFinite(value)) return `${price}₺`;

  return `${value.toLocaleString("tr-TR", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}₺`;
}

export function RelatedProducts({
  products,
  categoryName,
  categorySlug,
}: {
  products: readonly ProductCard[];
  categoryName: string;
  categorySlug: string | null;
}) {
  if (products.length === 0) return null;

  return (
    <section className="related-products" aria-labelledby="related-heading">
      <div className="related-products__head">
        <h2 id="related-heading">
          <i className="fas fa-layer-group" aria-hidden="true" /> Benzer
          Hesaplar
        </h2>
        {categorySlug === null ? null : (
          <Link href={`/${categorySlug}`} className="related-products__all">
            {categoryName} kategorisinin tamamı
            <i className="fas fa-arrow-right" aria-hidden="true" />
          </Link>
        )}
      </div>

      <div className="related-products__grid">
        {products.map((product) => (
          <Link
            key={product.id}
            href={`/${product.seoSlug ?? ""}`}
            className="account-item"
            style={{ textDecoration: "none", color: "inherit" }}
          >
            <div className="account-avatar">
              <i className="fas fa-user" aria-hidden="true" />
            </div>
            <div className="account-info">
              <h4>{product.title}</h4>
              <p>
                {product.platform} · Stok: {product.stockQuantity}
              </p>
              <div className="account-meta">
                <span className="price">{formatPrice(product.price)}</span>
                <span
                  className={
                    product.stockQuantity > 0
                      ? "stock in-stock"
                      : "stock out-stock"
                  }
                >
                  {product.stockQuantity > 0 ? "Stokta" : "Tükendi"}
                </span>
              </div>
            </div>
            {product.isVerified ? (
              <div className="account-badge verified" title="Doğrulanmış">
                <i className="fas fa-check" aria-hidden="true" />
              </div>
            ) : null}
          </Link>
        ))}
      </div>
    </section>
  );
}
