import type { Prisma } from "@prisma/client";
import Link from "next/link";
import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { requireAdmin } from "@/lib/auth/admin";
import { formatDateTime, formatNumber } from "@/lib/admin/format";
import {
  TICKET_PRIORITY_LABELS,
  TICKET_STATUS_BADGES,
  TICKET_STATUS_LABELS,
} from "@/lib/admin/labels";
import { prisma } from "@/lib/prisma";

const PAGE_SIZE = 25;

type SearchParams = {
  status?: string;
  page?: string;
};

export default async function AdminSupportPage({
  searchParams,
}: {
  searchParams: Promise<SearchParams>;
}) {
  const admin = await requireAdmin();
  const { status, page } = await searchParams;

  const currentPage = Math.max(Number.parseInt(page ?? "1", 10) || 1, 1);
  const where: Prisma.SupportTicketWhereInput =
    status !== undefined && status in TICKET_STATUS_LABELS
      ? { status: status as Prisma.EnumTicketStatusFilter }
      : {};

  const [total, tickets, openCount] = await Promise.all([
    prisma.supportTicket.count({ where }),
    prisma.supportTicket.findMany({
      where,
      orderBy: [{ updatedAt: "desc" }],
      skip: (currentPage - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
      select: {
        id: true,
        ticketCode: true,
        subject: true,
        orderCode: true,
        status: true,
        priority: true,
        createdAt: true,
        lastReplyAt: true,
        user: { select: { username: true, email: true } },
        _count: { select: { replies: true } },
      },
    }),
    prisma.supportTicket.count({ where: { status: { in: ["open", "in_progress"] } } }),
  ]);

  const pageCount = Math.max(Math.ceil(total / PAGE_SIZE), 1);

  return (
    <>
      <AdminPageHeader
        title="Destek Yönetimi"
        subtitle={`${formatNumber(total)} talep · ${formatNumber(openCount)} açık`}
        userName={`${admin.firstName} ${admin.lastName}`}
      />

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Filtrele</h2>
        </div>
        <div className="card-body">
          <form className="form-grid" method="get">
            <div className="form-field">
              <label htmlFor="status">Durum</label>
              <select id="status" name="status" defaultValue={status ?? ""}>
                <option value="">Tümü</option>
                {Object.entries(TICKET_STATUS_LABELS).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
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
          <h2 className="card-title">Talepler</h2>
          <span className="admin-badge info">
            Sayfa {currentPage} / {pageCount}
          </span>
        </div>
        <div className="card-body table-scroll">
          {tickets.length === 0 ? (
            <div className="admin-empty">Destek talebi yok.</div>
          ) : (
            <table className="data-table">
              <thead>
                <tr>
                  <th>Talep</th>
                  <th>Konu</th>
                  <th>Kullanıcı</th>
                  <th>Sipariş</th>
                  <th>Öncelik</th>
                  <th>Durum</th>
                  <th>Son yanıt</th>
                </tr>
              </thead>
              <tbody>
                {tickets.map((ticket) => (
                  <tr key={ticket.id}>
                    <td>
                      <Link href={`/admin/support/${ticket.ticketCode}`}>
                        {ticket.ticketCode}
                      </Link>
                      <div style={{ fontSize: "0.75rem", color: "#94a3b8" }}>
                        {formatNumber(ticket._count.replies)} yanıt
                      </div>
                    </td>
                    <td>{ticket.subject}</td>
                    <td>
                      {ticket.user.username}
                      <div style={{ fontSize: "0.75rem", color: "#94a3b8" }}>
                        {ticket.user.email}
                      </div>
                    </td>
                    <td>{ticket.orderCode}</td>
                    <td>
                      {TICKET_PRIORITY_LABELS[ticket.priority] ?? ticket.priority}
                    </td>
                    <td>
                      <span
                        className={`admin-badge ${TICKET_STATUS_BADGES[ticket.status] ?? "info"}`}
                      >
                        {TICKET_STATUS_LABELS[ticket.status] ?? ticket.status}
                      </span>
                    </td>
                    <td>{formatDateTime(ticket.lastReplyAt ?? ticket.createdAt)}</td>
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
              if (status !== undefined && status !== "") params.set("status", status);
              params.set("page", String(pageNumber));

              return (
                <Link
                  key={pageNumber}
                  href={`/admin/support?${params.toString()}`}
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
