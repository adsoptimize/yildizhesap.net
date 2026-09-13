import Link from "next/link";
import { notFound } from "next/navigation";
import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { requireAdmin } from "@/lib/auth/admin";
import { formatDateTime } from "@/lib/admin/format";
import {
  TICKET_PRIORITY_LABELS,
  TICKET_STATUS_LABELS,
} from "@/lib/admin/labels";
import { prisma } from "@/lib/prisma";
import { replyTicketAction, updateTicketAction } from "../actions";

export default async function AdminTicketDetailPage({
  params,
}: {
  params: Promise<{ code: string }>;
}) {
  const admin = await requireAdmin();
  const { code } = await params;
  const ticketCode = decodeURIComponent(code);

  const ticket = await prisma.supportTicket.findUnique({
    where: { ticketCode },
    include: {
      user: { select: { username: true, email: true } },
      replies: { orderBy: { createdAt: "asc" } },
    },
  });

  if (ticket === null) {
    notFound();
  }

  return (
    <>
      <AdminPageHeader
        title={ticket.subject}
        subtitle={`${ticket.ticketCode} · ${ticket.user.username} · sipariş ${ticket.orderCode}`}
        userName={`${admin.firstName} ${admin.lastName}`}
        actions={
          <Link href="/admin/support" className="admin-btn secondary">
            <i className="fas fa-arrow-left" /> Liste
          </Link>
        }
      />

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Durum</h2>
        </div>
        <div className="card-body">
          <form action={updateTicketAction} className="form-grid">
            <input type="hidden" name="ticketCode" value={ticket.ticketCode} />
            <div className="form-field">
              <label htmlFor="status">Durum</label>
              <select id="status" name="status" defaultValue={ticket.status}>
                {Object.entries(TICKET_STATUS_LABELS).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </div>
            <div className="form-field">
              <label htmlFor="priority">Öncelik</label>
              <select id="priority" name="priority" defaultValue={ticket.priority}>
                {Object.entries(TICKET_PRIORITY_LABELS).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </div>
            <div className="form-field" style={{ justifyContent: "flex-end" }}>
              <button type="submit" className="admin-btn">
                <i className="fas fa-save" /> Güncelle
              </button>
            </div>
          </form>
        </div>
      </div>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Yazışma</h2>
        </div>
        <div className="card-body">
          <div className="activity-item">
            <div>
              <div className="activity-title">
                {ticket.user.username} (müşteri)
              </div>
              <div className="activity-subtitle">{ticket.message}</div>
              <div className="activity-time">{formatDateTime(ticket.createdAt)}</div>
            </div>
          </div>

          {ticket.replies.map((reply) => (
            <div className="activity-item" key={reply.id}>
              <div>
                <div className="activity-title">
                  {reply.isAdminReply ? "Yönetici" : ticket.user.username}
                </div>
                <div className="activity-subtitle">{reply.message}</div>
                <div className="activity-time">
                  {formatDateTime(reply.createdAt)}
                </div>
              </div>
            </div>
          ))}

          <form
            action={replyTicketAction}
            className="admin-form"
            style={{ marginTop: "1.5rem" }}
          >
            <input type="hidden" name="ticketCode" value={ticket.ticketCode} />
            <div className="form-field">
              <label htmlFor="message">Yanıt</label>
              <textarea id="message" name="message" required />
            </div>
            <div>
              <button type="submit" className="admin-btn">
                <i className="fas fa-paper-plane" /> Yanıtla
              </button>
            </div>
          </form>
        </div>
      </div>
    </>
  );
}
