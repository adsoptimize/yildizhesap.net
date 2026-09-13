"use client";

/**
 * Category page product grid with client-side search, sort, and price-range
 * filter. Ports the legacy `hesaplar.php` filter row into a React component.
 * The initial dataset is delivered from the server (SSG/ISR); filtering runs
 * entirely in the browser so it never forces the page to render dynamically.
 */

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMemo, useState } from "react";
import type { CategoryCard, ProductCard } from "@/lib/db/queries";

type SortKey = "smart" | "date" | "price-low" | "price-high" | "popular" | "rating";

type PriceRange = "" | "0-100" | "100-500" | "500-1000" | "1000-5000" | "5000+";

const PRICE_RANGES: Readonly<Record<Exclude<PriceRange, "">, readonly [number, number | null]>> = {
  "0-100": [0, 100],
  "100-500": [100, 500],
  "500-1000": [500, 1000],
  "1000-5000": [1000, 5000],
  "5000+": [5000, null],
};

function formatPrice(price: string): string {
  const value = Number(price);
  if (!Number.isFinite(value)) return `${price}₺`;
  return `${value.toLocaleString("tr-TR", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}₺`;
}

function sortProducts(products: ProductCard[], key: SortKey): ProductCard[] {
  const copy = [...products];
  switch (key) {
    case "date":
      // No createdAt on the card; fall back to descending id (newer first).
      return copy.sort((a, b) => b.id - a.id);
    case "price-low":
      return copy.sort((a, b) => Number(a.price) - Number(b.price));
    case "price-high":
      return copy.sort((a, b) => Number(b.price) - Number(a.price));
    case "popular":
      return copy.sort((a, b) => b.salesCount - a.salesCount);
    case "rating":
      return copy.sort((a, b) => (b.rating ?? 0) - (a.rating ?? 0));
    case "smart":
    default:
      return copy.sort((a, b) => {
        // Featured first, then in-stock, then higher rating, then newer.
        if (a.isFeatured !== b.isFeatured) return a.isFeatured ? -1 : 1;
        const aStock = a.stockQuantity > 0 ? 1 : 0;
        const bStock = b.stockQuantity > 0 ? 1 : 0;
        if (aStock !== bStock) return bStock - aStock;
        const aRating = a.rating ?? 0;
        const bRating = b.rating ?? 0;
        if (aRating !== bRating) return bRating - aRating;
        return b.id - a.id;
      });
  }
}

export function ProductGrid({
  products,
  categories,
  currentCategoryId,
}: {
  products: ProductCard[];
  /** Category dropdown lets shoppers jump straight to another catalog. */
  categories?: readonly CategoryCard[];
  currentCategoryId?: number;
}) {
  const router = useRouter();
  const [search, setSearch] = useState("");
  const [sortKey, setSortKey] = useState<SortKey>("smart");
  const [priceRange, setPriceRange] = useState<PriceRange>("");

  const filtered = useMemo(() => {
    let list = products;

    if (search.trim() !== "") {
      const needle = search.toLocaleLowerCase("tr-TR");
      list = list.filter((product) =>
        product.title.toLocaleLowerCase("tr-TR").includes(needle),
      );
    }

    if (priceRange !== "") {
      const [min, max] = PRICE_RANGES[priceRange];
      list = list.filter((product) => {
        const price = Number(product.price);
        if (max === null) return price >= min;
        return price >= min && price < max;
      });
    }

    return sortProducts(list, sortKey);
  }, [products, search, sortKey, priceRange]);

  const showCategoryFilter =
    categories !== undefined && categories.length > 0;

  return (
    <div className="product-grid">
      <div className="product-grid__filters">
        <div className="filter-item">
          <label htmlFor="pg-sort">Sıralama</label>
          <select
            id="pg-sort"
            className="filter-select"
            value={sortKey}
            onChange={(event) => setSortKey(event.target.value as SortKey)}
          >
            <option value="smart">Akıllı Sıralama</option>
            <option value="date">Yeni Eklenenler</option>
            <option value="price-low">Fiyat (Düşük → Yüksek)</option>
            <option value="price-high">Fiyat (Yüksek → Düşük)</option>
            <option value="popular">En Popüler</option>
            <option value="rating">En Yüksek Puan</option>
          </select>
        </div>

        {showCategoryFilter ? (
          <div className="filter-item">
            <label htmlFor="pg-category">Kategori</label>
            <select
              id="pg-category"
              className="filter-select"
              defaultValue={currentCategoryId ?? ""}
              onChange={(event) => {
                const value = event.target.value;
                if (value === "") {
                  router.push("/tum-hesaplar");
                  return;
                }
                const target = categories.find(
                  (category) => category.id === Number(value),
                );
                if (target !== undefined) {
                  router.push(`/${target.seoSlug}`);
                }
              }}
            >
              <option value="">Tüm Kategoriler</option>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </div>
        ) : null}

        <div className="filter-item">
          <label htmlFor="pg-price">Fiyat</label>
          <select
            id="pg-price"
            className="filter-select"
            value={priceRange}
            onChange={(event) => setPriceRange(event.target.value as PriceRange)}
          >
            <option value="">Tüm Fiyatlar</option>
            <option value="0-100">0₺ - 100₺</option>
            <option value="100-500">100₺ - 500₺</option>
            <option value="500-1000">500₺ - 1000₺</option>
            <option value="1000-5000">1000₺ - 5000₺</option>
            <option value="5000+">5000₺+</option>
          </select>
        </div>

        <div className="filter-item filter-item--search">
          <label htmlFor="pg-search">Arama</label>
          <div className="search-box-compact">
            <i className="fas fa-search" aria-hidden="true" />
            <input
              id="pg-search"
              type="search"
              placeholder="Hesap ara..."
              value={search}
              onChange={(event) => setSearch(event.target.value)}
            />
          </div>
        </div>
      </div>

      <div className="list-header">
        <h3>
          Hesaplar{" "}
          <span className="account-count">({filtered.length})</span>
        </h3>
      </div>

      {filtered.length === 0 ? (
        <p style={{ padding: "20px 0" }}>
          {products.length === 0
            ? "Bu kategoride şu anda listelenen hesap bulunmuyor. Stok girişleri yapıldığında burada görünecek."
            : "Aradığınız kriterlere uygun hesap bulunamadı."}
        </p>
      ) : (
        filtered.map((product) => (
          <ProductGridItem key={product.id} product={product} />
        ))
      )}
    </div>
  );
}

function ProductGridItem({ product }: { product: ProductCard }) {
  return (
    <Link
      href={`/${product.seoSlug ?? ""}`}
      className="account-item"
      style={{ textDecoration: "none", color: "inherit" }}
    >
      <div className="account-avatar">
        <i className="fas fa-user" />
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
              product.stockQuantity > 0 ? "stock in-stock" : "stock out-stock"
            }
          >
            {product.stockQuantity > 0 ? "Stokta" : "Tükendi"}
          </span>
          {product.rating !== null && product.rating > 0 ? (
            <span className="product-rating" title="Ortalama puan">
              <i className="fas fa-star" aria-hidden="true" />{" "}
              {product.rating.toFixed(1)}
            </span>
          ) : null}
        </div>
      </div>
      {product.isVerified ? (
        <div className="account-badge verified" title="Doğrulanmış">
          <i className="fas fa-check" aria-hidden="true" />
        </div>
      ) : null}
    </Link>
  );
}
