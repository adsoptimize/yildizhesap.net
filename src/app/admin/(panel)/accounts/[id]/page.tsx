import Link from "next/link";
import { notFound } from "next/navigation";
import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { requireAdmin } from "@/lib/auth/admin";
import { formatDateTime } from "@/lib/admin/format";
import { prisma } from "@/lib/prisma";
import { AccountForm } from "../AccountForm";
import {
  addFeatureAction,
  addStockAction,
  deleteAccountAction,
  deleteFeatureAction,
  deleteStockAction,
} from "../actions";

export default async function EditAccountPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const admin = await requireAdmin();
  const { id } = await params;
  const accountId = Number.parseInt(id, 10);

  if (Number.isNaN(accountId)) {
    notFound();
  }

  const [account, categories] = await Promise.all([
    prisma.account.findUnique({
      where: { id: accountId },
      include: {
        featureRows: { orderBy: { id: "asc" } },
        stock: { orderBy: { id: "desc" } },
      },
    }),
    prisma.category.findMany({
      orderBy: { name: "asc" },
      select: { id: true, name: true },
    }),
  ]);

  if (account === null) {
    notFound();
  }

  return (
    <>
      <AdminPageHeader
        title={account.title}
        subtitle={account.seoSlug === null ? undefined : `/${account.seoSlug}`}
        userName={`${admin.firstName} ${admin.lastName}`}
        actions={
          <>
            {account.seoSlug === null ? null : (
              <Link
                href={`/${account.seoSlug}`}
                className="admin-btn secondary"
                target="_blank"
              >
                <i className="fas fa-external-link-alt" /> Sitede gör
              </Link>
            )}
            <Link href="/admin/accounts" className="admin-btn secondary">
              <i className="fas fa-arrow-left" /> Liste
            </Link>
          </>
        }
      />

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Ürün Bilgileri</h2>
        </div>
        <div className="card-body">
          <AccountForm
            categories={categories}
            account={{
              id: account.id,
              categoryId: account.categoryId,
              title: account.title,
              seoSlug: account.seoSlug ?? "",
              description: account.description ?? "",
              price: account.price.toString(),
              oldPrice: account.oldPrice?.toString() ?? "",
              stockQuantity: account.stockQuantity,
              platform: account.platform,
              accountType: account.accountType,
              limitInfo: account.limitInfo ?? "",
              technicalInfo: account.technicalInfo ?? "",
              location: account.location ?? "",
              warrantyDays: account.warrantyDays,
              isPublished: account.status === "active",
              isVerified: account.isVerified,
              isPremium: account.isPremium,
              isFeatured: account.isFeatured,
              isSecure: account.isSecure,
              instantDelivery: account.instantDelivery,
              support247: account.support247,
              guarantee30Days: account.guarantee30Days,
            }}
          />
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Özellikler</h2>
        </div>
        <div className="card-body">
          {account.featureRows.length === 0 ? (
            <div className="admin-empty">Henüz özellik eklenmedi.</div>
          ) : (
            <div className="table-scroll">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>Özellik</th>
                    <th>Değer</th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  {account.featureRows.map((feature) => (
                    <tr key={feature.id}>
                      <td>{feature.featureName}</td>
                      <td>{feature.featureValue}</td>
                      <td>
                        <form action={deleteFeatureAction}>
                          <input type="hidden" name="id" value={feature.id} />
                          <button type="submit" className="admin-btn danger small">
                            Sil
                          </button>
                        </form>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          <form
            action={addFeatureAction}
            className="form-grid"
            style={{ marginTop: "1.25rem" }}
          >
            <input type="hidden" name="accountId" value={account.id} />
            <div className="form-field">
              <label htmlFor="featureName">Özellik adı</label>
              <input id="featureName" name="featureName" type="text" required />
            </div>
            <div className="form-field">
              <label htmlFor="featureValue">Değer</label>
              <input id="featureValue" name="featureValue" type="text" required />
            </div>
            <div className="form-field" style={{ justifyContent: "flex-end" }}>
              <button type="submit" className="admin-btn secondary">
                <i className="fas fa-plus" /> Özellik Ekle
              </button>
            </div>
          </form>
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Stok (teslim edilecek hesaplar)</h2>
          <span className="admin-badge info">
            {account.stock.filter((row) => !row.isSold).length} satılmadı
          </span>
        </div>
        <div className="card-body">
          {account.stock.length === 0 ? (
            <div className="admin-empty">Stok kaydı yok.</div>
          ) : (
            <div className="table-scroll">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>Kullanıcı</th>
                    <th>E-posta</th>
                    <th>Durum</th>
                    <th>Eklendi</th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  {account.stock.map((row) => (
                    <tr key={row.id}>
                      <td>{row.username}</td>
                      <td>{row.email ?? "-"}</td>
                      <td>
                        <span
                          className={`admin-badge ${row.isSold ? "danger" : "success"}`}
                        >
                          {row.isSold ? "Satıldı" : "Hazır"}
                        </span>
                      </td>
                      <td>{formatDateTime(row.createdAt)}</td>
                      <td>
                        {row.isSold ? null : (
                          <form action={deleteStockAction}>
                            <input type="hidden" name="id" value={row.id} />
                            <button type="submit" className="admin-btn danger small">
                              Sil
                            </button>
                          </form>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          <form
            action={addStockAction}
            className="form-grid"
            style={{ marginTop: "1.25rem" }}
          >
            <input type="hidden" name="accountId" value={account.id} />
            <div className="form-field">
              <label htmlFor="username">Kullanıcı adı</label>
              <input id="username" name="username" type="text" required />
            </div>
            <div className="form-field">
              <label htmlFor="password">Şifre</label>
              <input id="password" name="password" type="text" required />
            </div>
            <div className="form-field">
              <label htmlFor="email">E-posta</label>
              <input id="email" name="email" type="text" />
            </div>
            <div className="form-field">
              <label htmlFor="emailPassword">E-posta şifresi</label>
              <input id="emailPassword" name="emailPassword" type="text" />
            </div>
            <div className="form-field">
              <label htmlFor="totpSecret">2FA anahtarı</label>
              <input id="totpSecret" name="totpSecret" type="text" />
            </div>
            <div className="form-field">
              <label htmlFor="accountCreatedDate">Hesap açılış tarihi</label>
              <input id="accountCreatedDate" name="accountCreatedDate" type="date" />
            </div>
            <div className="form-field">
              <label htmlFor="additionalInfo">Ek bilgi</label>
              <input id="additionalInfo" name="additionalInfo" type="text" />
            </div>
            <div className="form-field" style={{ justifyContent: "flex-end" }}>
              <button type="submit" className="admin-btn secondary">
                <i className="fas fa-plus" /> Stok Ekle
              </button>
            </div>
          </form>
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Tehlikeli Bölge</h2>
        </div>
        <div className="card-body">
          <p style={{ marginBottom: "1rem", color: "#64748b" }}>
            Ürünü silmek stok ve özellik kayıtlarını da siler. SEO açısından
            silmek yerine yayından kaldırmak önerilir.
          </p>
          <form action={deleteAccountAction}>
            <input type="hidden" name="id" value={account.id} />
            <button type="submit" className="admin-btn danger">
              <i className="fas fa-trash" /> Ürünü Sil
            </button>
          </form>
        </div>
      </div>
    </>
  );
}
