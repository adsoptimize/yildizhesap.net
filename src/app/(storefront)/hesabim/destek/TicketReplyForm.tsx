"use client";

import { useActionState } from "react";
import { replyTicketAction } from "../actions";
import { INITIAL_CUSTOMER_FORM_STATE } from "@/lib/shop/form-state";

type TicketReplyFormProps = {
  ticketCode: string;
  canReply: boolean;
  blockedReason: string | null;
};

export function TicketReplyForm({
  ticketCode,
  canReply,
  blockedReason,
}: TicketReplyFormProps) {
  const [state, formAction, pending] = useActionState(
    replyTicketAction,
    INITIAL_CUSTOMER_FORM_STATE,
  );

  if (!canReply) {
    return <div className="shop-alert info">{blockedReason}</div>;
  }

  return (
    <form action={formAction} className="shop-form">
      {state.error === null ? null : (
        <div className="shop-alert error">{state.error}</div>
      )}
      {state.success === null ? null : (
        <div className="shop-alert success">{state.success}</div>
      )}

      <input type="hidden" name="ticketCode" value={ticketCode} />

      <div className="shop-field">
        <label htmlFor={`message-${ticketCode}`}>Yanıtınız</label>
        <textarea
          id={`message-${ticketCode}`}
          name="message"
          rows={4}
          minLength={10}
          required
        />
      </div>

      <button type="submit" className="shop-submit" disabled={pending}>
        {pending ? "Gönderiliyor..." : "Yanıt Gönder"}
      </button>
    </form>
  );
}
