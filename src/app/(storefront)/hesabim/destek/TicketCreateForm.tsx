"use client";

import { useActionState } from "react";
import { createTicketAction } from "../actions";
import { INITIAL_CUSTOMER_FORM_STATE } from "@/lib/shop/form-state";

type TicketOrderOption = {
  orderCode: string;
  productName: string;
};

type TicketCreateFormProps = {
  orders: readonly TicketOrderOption[];
};

const PRIORITY_OPTIONS = [
  { value: "low", label: "Düşük" },
  { value: "medium", label: "Orta" },
  { value: "high", label: "Yüksek" },
  { value: "urgent", label: "Acil" },
] as const;

export function TicketCreateForm({ orders }: TicketCreateFormProps) {
  const [state, formAction, pending] = useActionState(
    createTicketAction,
    INITIAL_CUSTOMER_FORM_STATE,
  );

  if (orders.length === 0) {
    return (
      <div className="shop-alert info">
        Destek talebi açmak için tamamlanmış bir siparişiniz olmalı.
      </div>
    );
  }

  return (
    <form action={formAction} className="shop-form">
      {state.error === null ? null : (
        <div className="shop-alert error">{state.error}</div>
      )}
      {state.success === null ? null : (
        <div className="shop-alert success">{state.success}</div>
      )}

      {orders.length === 1 ? (
        <input type="hidden" name="orderCode" value={orders[0]?.orderCode} />
      ) : (
        <div className="shop-field">
          <label htmlFor="orderCode">Sipariş</label>
          <select id="orderCode" name="orderCode" required>
            {orders.map((order) => (
              <option key={order.orderCode} value={order.orderCode}>
                {order.orderCode} — {order.productName}
              </option>
            ))}
          </select>
        </div>
      )}

      <div className="shop-field">
        <label htmlFor="subject">Konu</label>
        <input id="subject" name="subject" type="text" minLength={5} required />
      </div>

      <div className="shop-field">
        <label htmlFor="priority">Öncelik</label>
        <select id="priority" name="priority" defaultValue="medium">
          {PRIORITY_OPTIONS.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      </div>

      <div className="shop-field">
        <label htmlFor="message">Mesaj</label>
        <textarea id="message" name="message" rows={5} minLength={10} required />
      </div>

      <button type="submit" className="shop-submit" disabled={pending}>
        {pending ? "Gönderiliyor..." : "Destek Talebi Oluştur"}
      </button>
    </form>
  );
}
