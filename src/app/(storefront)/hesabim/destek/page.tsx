import { formatDateTime } from "@/lib/admin/format";
import {
  TICKET_PRIORITY_LABELS,
  TICKET_STATUS_LABELS,
} from "@/lib/admin/labels";
import { requireCustomer } from "@/lib/auth/customer";
import { prisma } from "@/lib/prisma";
import { TicketCreateForm } from "./TicketCreateForm";
import { TicketReplyForm } from "./TicketReplyForm";

function blockedReason(
  status: string,
  lastReplyBy: string | null,
): string | null {
  if (status === "closed") {
    return "Kapalı ticket'lara yanıt verilemez.";
  }

  if (lastReplyBy === "customer") {
    return "Destek ekibimizden yanıt gelmeden yeni mesaj gönderemezsiniz.";
  }

  return null;
}

export default async function CustomerSupportPage() {
  const customer = await requireCustomer();

  const [tickets, orders] = await Promise.all([
    prisma.supportTicket.findMany({
      where: { userId: customer.id },
      orderBy: { createdAt: "desc" },
      select: {
        id: true,
        ticketCode: true,
        orderCode: true,
        subject: true,
        status: true,
        priority: true,
        createdAt: true,
        lastReplyBy: true,
        replies: {
          orderBy: { createdAt: "asc" },
          select: {
            id: true,
            message: true,
            isAdminReply: true,
            createdAt: true,
          },
        },
      },
    }),
    prisma.order.findMany({
      where: { userId: customer.id },
      orderBy: { createdAt: "desc" },
      select: { orderCode: true, productName: true },
    }),
  ]);

  const ticketedOrders = new Set(tickets.map((ticket) => ticket.orderCode));
  const openableOrders = orders.filter(
    (order) => !ticketedOrders.has(order.orderCode),
  );

  return (
    <>
      <div className="shop-panel">
        <h2 className="shop-panel-title">
          <i className="fas fa-plus" /> Yeni Destek Talebi
        </h2>
        <TicketCreateForm orders={openableOrders} />
      </div>

      <div className="shop-panel">
        <h2 className="shop-panel-title">
          <i className="fas fa-comments" /> Taleplerim
        </h2>

        {tickets.length === 0 ? (
          <div className="shop-empty">
            <i className="fas fa-inbox" />
            <h3>Henüz destek talebiniz yok</h3>
          </div>
        ) : (
          tickets.map((ticket) => {
            const reason = blockedReason(ticket.status, ticket.lastReplyBy);

            return (
              <div className="shop-credentials" key={ticket.id}>
                <div
                  style={{
                    alignItems: "center",
                    display: "flex",
                    flexWrap: "wrap",
                    gap: 10,
                    justifyContent: "space-between",
                  }}
                >
                  <div>
                    <strong>{ticket.subject}</strong>
                    <div className="shop-hint">
                      {ticket.ticketCode} · Sipariş {ticket.orderCode} ·{" "}
                      {formatDateTime(ticket.createdAt)} ·{" "}
                      {TICKET_PRIORITY_LABELS[ticket.priority] ?? ticket.priority}
                    </div>
                  </div>
                  <span className="shop-badge processing">
                    {TICKET_STATUS_LABELS[ticket.status] ?? ticket.status}
                  </span>
                </div>

                <div style={{ marginTop: 16 }}>
                  {ticket.replies.map((reply) => (
                    <div
                      key={reply.id}
                      style={{
                        borderLeft: reply.isAdminReply
                          ? "3px solid var(--accent)"
                          : "3px solid var(--primary)",
                        marginBottom: 12,
                        paddingLeft: 12,
                      }}
                    >
                      <div className="shop-hint">
                        {reply.isAdminReply ? "Destek ekibi" : "Siz"} ·{" "}
                        {formatDateTime(reply.createdAt)}
                      </div>
                      <p style={{ whiteSpace: "pre-wrap" }}>{reply.message}</p>
                    </div>
                  ))}
                </div>

                <TicketReplyForm
                  ticketCode={ticket.ticketCode}
                  canReply={reason === null}
                  blockedReason={reason}
                />
              </div>
            );
          })
        )}
      </div>
    </>
  );
}
