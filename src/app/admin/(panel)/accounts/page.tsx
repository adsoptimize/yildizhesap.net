import type { Prisma } from "@prisma/client";
import Link from "next/link";
import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { requireAdmin } from "@/lib/auth/admin";
import { formatCurrency, formatNumber } from "@/lib/admin/format";
import { prisma } from "@/lib/prisma";
import { toggleAccountStatusAction } from "./actions";

const PAGE_SIZE = 20;

type SearchParams = {
  q?: string;
  category?: string;
  status?: string;
  page?: string;
};

export default async function AdminAccountsPage({
  searchParams,
}: {
  searchParams: Promise<SearchParams>;
}) {
  const admin = await requireAdmin();
  const { q, category, status, page } = await searchParams;

  const search = q?.trim() ?? "";
  const categoryId = Number.parseInt(category ?? "", 10);
  const currentPage = Math.max(Number.parseInt(page ?? "1", 10) || 1, 1);

  const where: Prisma.AccountWhereInput = {
    ...(search === ""
      ? {}
      : {
          OR: [
            { title: { contains: search, mode: "insensitive" } },
            { seoSlug: { contains: search, mode: "insensitive" } },
          ],
        }),
    ...(Number.isNaN(categoryId) ? {} : { categoryId }),
    ...(status === "active" || status === "inactive" ? { status } : {}),
  };

  const [categories, total, accounts] = await Promise.all([
    prisma.category.findMany({
      orderBy: { name: "asc" },
      select: { id: true, name: true },
    }),
    prisma.account.count({ where }),
    prisma.account.findMany({
      where,
      orderBy: { id: "desc" },
      skip: (currentPage - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
      select: {
        id: true,
        title: true,
        seoSlug: true,
        price: true,
        stockQuantity: true,
        status: true,
        isFeatured: true,
        category: { select: { name: true } },
        _count: { select: { stock: true } },
      },
    }),
  ]);

  const pageCount = Math.max(Math.ceil(total / PAGE_SIZE), 1);

  return (
    <>
      <AdminPageHeader
        title="Hesap Yönetimi"
        subtitle={`${formatNumber(total)} kayıt`}
        userName={`${admin.firstName} ${admin.lastName}`}
        actions={
          <Link href="/admin/accounts/new" className="admin-btn">
            <i className="fas fa-plus" /> Yeni Ürün
          </Link>
        }
      />

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Filtrele</h2>
        </div>
        <div className="card-body">
          <form className="form-grid" method="get">
            <div className="form-field">
              <label htmlFor="q">Başlık / slug</label>
              <input id="q" name="q" type="text" defaultValue={search} />
            </div>
            <div className="form-field">
              <label htmlFor="category">Kategori</label>
              <select id="category" name="category" defaultValue={category ?? ""}>
                <option value="">Tümü</option>
                {categories.map((item) => (
                  <option key={item.id} value={item.id}>
                    {item.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="form-field">
              <label htmlFor="status">Durum</label>
              <select id="status" name="status" defaultValue={status ?? ""}>
                <option value="">Tümü</option>
                <option value="active">Yayında</option>
                <option value="inactive">Kapalı</option>
              </select>
            </div>
            <div className="form-field" style={{ justifyContent: "flex-end" }}>
              <button type="submit" className="admin-btn secondary">
                <i className="fas fa-filter" /> Uygula
              </button>
            </div>
          </form>
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Ürünler</h2>
          <span className="admin-badge info">
            Sayfa {currentPage} / {pageCount}
          </span>
        </div>
        <div className="card-body table-scroll">
          {accounts.length === 0 ? (
            <div className="admin-empty">
              Kayıt bulunamadı. <Link href="/admin/accounts/new">Yeni ürün ekle</Link>.
            </div>
          ) : (
            <table className="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Başlık</th>
                  <th>Kategori</th>
                  <th>Fiyat</th>
                  <th>Stok</th>
                  <th>Durum</th>
                  <th>İşlem</th>
                </tr>
              </thead>
              <tbody>
                {accounts.map((account) => (
                  <tr key={account.id}>
                    <td>{account.id}</td>
                    <td>
                      <Link href={`/admin/accounts/${account.id}`}>
                        {account.title}
                      </Link>
                      {account.seoSlug === null ? null : (
                        <div style={{ fontSize: "0.75rem", color: "#94a3b8" }}>
                          /{account.seoSlug}
                        </div>
                      )}
                    </td>
                    <td>{account.category.name}</td>
                    <td>{formatCurrency(account.price.toString())}</td>
                    <td>
                      {formatNumber(account.stockQuantity)}
                      {account._count.stock > 0 ? (
                        <span style={{ fontSize: "0.75rem", color: "#94a3b8" }}>
                          {" "}
                          ({formatNumber(account._count.stock)} kayıt)
                        </span>
                      ) : null}
                    </td>
                    <td>
                      <span
                        className={`admin-badge ${
                          account.status === "active" ? "success" : "danger"
                        }`}
                      >
                        {account.status === "active" ? "Yayında" : "Kapalı"}
                      </span>
                    </td>
                    <td>
                      <div style={{ display: "flex", gap: "0.4rem" }}>
                        <Link
                          href={`/admin/accounts/${account.id}`}
                          className="admin-btn secondary small"
                        >
                          Düzenle
                        </Link>
                        <form action={toggleAccountStatusAction}>
                          <input type="hidden" name="id" value={account.id} />
                          <button type="submit" className="admin-btn secondary small">
                            {account.status === "active" ? "Kapat" : "Yayınla"}
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>

      {pageCount > 1 ? (
        <div style={{ display: "flex", gap: "0.5rem", flexWrap: "wrap" }}>
          {Array.from({ length: pageCount }, (_, index) => index + 1).map(
            (pageNumber) => {
              const params = new URLSearchParams();
              if (search !== "") params.set("q", search);
              if (category !== undefined && category !== "")
                params.set("category", category);
              if (status !== undefined && status !== "") params.set("status", status);
              params.set("page", String(pageNumber));

              return (
                <Link
                  key={pageNumber}
                  href={`/admin/accounts?${params.toString()}`}
                  className={`admin-btn small${
                    pageNumber === currentPage ? "" : " secondary"
                  }`}
                >
                  {pageNumber}
                </Link>
              );
            },
          )}
        </div>
      ) : null}
    </>
  );
}
