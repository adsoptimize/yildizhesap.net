import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { requireAdmin } from "@/lib/auth/admin";
import { formatNumber } from "@/lib/admin/format";
import { prisma } from "@/lib/prisma";
import { CategoryForm } from "./CategoryForm";
import { deleteCategoryAction, toggleCategoryAction } from "./actions";

export default async function AdminCategoriesPage() {
  const admin = await requireAdmin();

  const categories = await prisma.category.findMany({
    orderBy: { id: "asc" },
    select: {
      id: true,
      name: true,
      slug: true,
      seoSlug: true,
      icon: true,
      description: true,
      isActive: true,
      _count: { select: { accounts: true } },
    },
  });

  return (
    <>
      <AdminPageHeader
        title="Kategori Yönetimi"
        subtitle={`${formatNumber(categories.length)} kategori · SEO slug'ları indeksli, değiştirirken dikkat`}
        userName={`${admin.firstName} ${admin.lastName}`}
      />

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Kategoriler</h2>
        </div>
        <div className="card-body table-scroll">
          <table className="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Ad</th>
                <th>SEO slug</th>
                <th>Ürün</th>
                <th>Durum</th>
                <th>İşlem</th>
              </tr>
            </thead>
            <tbody>
              {categories.map((category) => (
                <tr key={category.id}>
                  <td>{category.id}</td>
                  <td>
                    <i className={category.icon} /> {category.name}
                  </td>
                  <td>
                    <code>/{category.seoSlug ?? category.slug}</code>
                  </td>
                  <td>{formatNumber(category._count.accounts)}</td>
                  <td>
                    <span
                      className={`admin-badge ${category.isActive ? "success" : "danger"}`}
                    >
                      {category.isActive ? "Aktif" : "Kapalı"}
                    </span>
                  </td>
                  <td>
                    <div style={{ display: "flex", gap: "0.4rem" }}>
                      <form action={toggleCategoryAction}>
                        <input type="hidden" name="id" value={category.id} />
                        <button type="submit" className="admin-btn secondary small">
                          {category.isActive ? "Kapat" : "Aç"}
                        </button>
                      </form>
                      {category._count.accounts === 0 ? (
                        <form action={deleteCategoryAction}>
                          <input type="hidden" name="id" value={category.id} />
                          <button type="submit" className="admin-btn danger small">
                            Sil
                          </button>
                        </form>
                      ) : null}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {categories.map((category) => (
        <details className="content-card" key={`edit-${category.id}`}>
          <summary
            className="card-header"
            style={{ cursor: "pointer", listStyle: "none" }}
          >
            <span className="card-title">
              <i className="fas fa-pen" /> {category.name} düzenle
            </span>
          </summary>
          <div className="card-body">
            <CategoryForm
              category={{
                id: category.id,
                name: category.name,
                seoSlug: category.seoSlug ?? category.slug,
                icon: category.icon,
                description: category.description ?? "",
                isActive: category.isActive,
              }}
            />
          </div>
        </details>
      ))}

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Yeni Kategori</h2>
        </div>
        <div className="card-body">
          <CategoryForm />
        </div>
      </div>
    </>
  );
}
