import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { StatefulForm } from "@/components/admin/StatefulForm";
import { requireAdmin } from "@/lib/auth/admin";
import { formatDateTime } from "@/lib/admin/format";
import { prisma } from "@/lib/prisma";
import { saveLegalPageAction } from "./actions";

const PUBLIC_ROUTE_BY_PAGE_TYPE: Readonly<Record<string, string>> = {
  kvvk: "/kvkk",
  privacy: "/gizlilik-politikasi",
};

export default async function AdminLegalPagesPage() {
  const admin = await requireAdmin();

  const pages = await prisma.legalPage.findMany({
    orderBy: { pageType: "asc" },
  });

  return (
    <>
      <AdminPageHeader
        title="Yasal Sayfalar"
        subtitle="KVKK ve gizlilik politikası sitede yayında; diğerleri arşivde tutulur"
        userName={`${admin.firstName} ${admin.lastName}`}
      />

      {pages.map((page) => (
        <div className="content-card" key={page.pageType}>
          <div className="card-header">
            <h2 className="card-title">
              {page.title}{" "}
              <span className="admin-badge">
                {PUBLIC_ROUTE_BY_PAGE_TYPE[page.pageType] ?? page.pageType}
              </span>
            </h2>
            <span style={{ fontSize: "0.75rem", color: "#94a3b8" }}>
              Son güncelleme: {formatDateTime(page.updatedAt)}
            </span>
          </div>
          <div className="card-body">
            <StatefulForm action={saveLegalPageAction} submitLabel="Sayfayı Kaydet">
              <input type="hidden" name="pageType" value={page.pageType} />
              <div className="form-grid">
                <div className="form-field">
                  <label htmlFor={`title-${page.pageType}`}>Başlık</label>
                  <input
                    id={`title-${page.pageType}`}
                    name="title"
                    type="text"
                    defaultValue={page.title}
                    required
                  />
                </div>
                <div className="form-field">
                  <label htmlFor={`meta-${page.pageType}`}>Meta açıklama</label>
                  <input
                    id={`meta-${page.pageType}`}
                    name="metaDescription"
                    type="text"
                    defaultValue={page.metaDescription ?? ""}
                  />
                </div>
              </div>
              <div className="form-field">
                <label htmlFor={`content-${page.pageType}`}>İçerik (HTML)</label>
                <textarea
                  id={`content-${page.pageType}`}
                  name="content"
                  defaultValue={page.content}
                  style={{ minHeight: 260, fontFamily: "monospace" }}
                  required
                />
              </div>
              <label className="checkbox-field">
                <input type="checkbox" name="isActive" defaultChecked={page.isActive} />
                Sayfa yayında
              </label>
            </StatefulForm>
          </div>
        </div>
      ))}
    </>
  );
}
