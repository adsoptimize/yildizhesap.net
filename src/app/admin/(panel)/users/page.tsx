import type { Prisma } from "@prisma/client";
import Link from "next/link";
import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { requireAdmin } from "@/lib/auth/admin";
import { formatCurrency, formatDateTime, formatNumber } from "@/lib/admin/format";
import { prisma } from "@/lib/prisma";
import { toggleUserStatusAction, updateUserBalanceAction } from "./actions";

const PAGE_SIZE = 25;

type SearchParams = {
  q?: string;
  status?: string;
  page?: string;
};

export default async function AdminUsersPage({
  searchParams,
}: {
  searchParams: Promise<SearchParams>;
}) {
  const admin = await requireAdmin();
  const { q, status, page } = await searchParams;

  const search = q?.trim() ?? "";
  const currentPage = Math.max(Number.parseInt(page ?? "1", 10) || 1, 1);

  const where: Prisma.UserWhereInput = {
    ...(search === ""
      ? {}
      : {
          OR: [
            { username: { contains: search, mode: "insensitive" } },
            { email: { contains: search, mode: "insensitive" } },
            { firstName: { contains: search, mode: "insensitive" } },
            { lastName: { contains: search, mode: "insensitive" } },
          ],
        }),
    ...(status === "active"
      ? { isActive: true }
      : status === "inactive"
        ? { isActive: false }
        : status === "admin"
          ? { isAdmin: true }
          : {}),
  };

  const [total, users, activeCount] = await Promise.all([
    prisma.user.count({ where }),
    prisma.user.findMany({
      where,
      orderBy: { createdAt: "desc" },
      skip: (currentPage - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
      select: {
        id: true,
        username: true,
        email: true,
        firstName: true,
        lastName: true,
        phone: true,
        balance: true,
        isActive: true,
        isAdmin: true,
        registrationIp: true,
        lastLogin: true,
        createdAt: true,
        deactivationReason: true,
        _count: { select: { orders: true } },
      },
    }),
    prisma.user.count({ where: { isActive: true } }),
  ]);

  const pageCount = Math.max(Math.ceil(total / PAGE_SIZE), 1);

  return (
    <>
      <AdminPageHeader
        title="Kullanıcı Yönetimi"
        subtitle={`${formatNumber(total)} kayıt · ${formatNumber(activeCount)} aktif`}
        userName={`${admin.firstName} ${admin.lastName}`}
      />

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Filtrele</h2>
        </div>
        <div className="card-body">
          <form className="form-grid" method="get">
            <div className="form-field">
              <label htmlFor="q">Kullanıcı adı / e-posta / isim</label>
              <input id="q" name="q" type="text" defaultValue={search} />
            </div>
            <div className="form-field">
              <label htmlFor="status">Durum</label>
              <select id="status" name="status" defaultValue={status ?? ""}>
                <option value="">Tümü</option>
                <option value="active">Aktif</option>
                <option value="inactive">Pasif</option>
                <option value="admin">Yöneticiler</option>
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
          <h2 className="card-title">Kullanıcılar</h2>
          <span className="admin-badge info">
            Sayfa {currentPage} / {pageCount}
          </span>
        </div>
        <div className="card-body table-scroll">
          {users.length === 0 ? (
            <div className="admin-empty">Kullanıcı bulunamadı.</div>
          ) : (
            <table className="data-table">
              <thead>
                <tr>
                  <th>Kullanıcı</th>
                  <th>İletişim</th>
                  <th>Sipariş</th>
                  <th>Bakiye</th>
                  <th>Son giriş</th>
                  <th>Durum</th>
                  <th>İşlem</th>
                </tr>
              </thead>
              <tbody>
                {users.map((user) => (
                  <tr key={user.id}>
                    <td>
                      <strong>{user.username}</strong>
                      <div style={{ fontSize: "0.75rem", color: "#94a3b8" }}>
                        {user.firstName} {user.lastName}
                        {user.isAdmin ? " · admin" : ""}
                      </div>
                    </td>
                    <td>
                      {user.email}
                      <div style={{ fontSize: "0.75rem", color: "#94a3b8" }}>
                        {user.phone ?? "-"} · IP: {user.registrationIp ?? "-"}
                      </div>
                    </td>
                    <td>{formatNumber(user._count.orders)}</td>
                    <td>
                      <form
                        action={updateUserBalanceAction}
                        style={{ display: "flex", gap: "0.3rem" }}
                      >
                        <input type="hidden" name="id" value={user.id} />
                        <input
                          name="balance"
                          type="text"
                          defaultValue={user.balance.toString()}
                          style={{
                            width: 90,
                            padding: "0.3rem 0.5rem",
                            border: "1px solid #e2e8f0",
                            borderRadius: 8,
                          }}
                          aria-label={`${user.username} bakiyesi`}
                        />
                        <button type="submit" className="admin-btn secondary small">
                          <i className="fas fa-check" />
                        </button>
                      </form>
                      <div style={{ fontSize: "0.75rem", color: "#94a3b8" }}>
                        {formatCurrency(user.balance.toString())}
                      </div>
                    </td>
                    <td>{formatDateTime(user.lastLogin)}</td>
                    <td>
                      <span
                        className={`admin-badge ${user.isActive ? "success" : "danger"}`}
                      >
                        {user.isActive ? "Aktif" : "Pasif"}
                      </span>
                      {user.deactivationReason === null ? null : (
                        <div style={{ fontSize: "0.7rem", color: "#94a3b8" }}>
                          {user.deactivationReason}
                        </div>
                      )}
                    </td>
                    <td>
                      {user.isAdmin ? (
                        <span className="admin-badge">korumalı</span>
                      ) : (
                        <form action={toggleUserStatusAction}>
                          <input type="hidden" name="id" value={user.id} />
                          <input
                            name="reason"
                            type="text"
                            placeholder="sebep"
                            style={{
                              width: 110,
                              padding: "0.3rem 0.5rem",
                              border: "1px solid #e2e8f0",
                              borderRadius: 8,
                              marginBottom: "0.3rem",
                            }}
                            aria-label="Durum değişikliği sebebi"
                          />
                          <button
                            type="submit"
                            className={`admin-btn small${user.isActive ? " danger" : ""}`}
                          >
                            {user.isActive ? "Pasifleştir" : "Aktifleştir"}
                          </button>
                        </form>
                      )}
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
              if (status !== undefined && status !== "") params.set("status", status);
              params.set("page", String(pageNumber));

              return (
                <Link
                  key={pageNumber}
                  href={`/admin/users?${params.toString()}`}
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
