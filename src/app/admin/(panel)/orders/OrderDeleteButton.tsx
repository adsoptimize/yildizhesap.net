"use client";

/**
 * Confirm-guarded delete button for orders. Server-side action refuses to
 * delete completed/delivered orders (audit trail), but we still show a
 * confirm prompt so an admin can't nuke a pending order by mistake.
 */

import { deleteOrderAction } from "./actions";

export function OrderDeleteButton({
  orderId,
  orderCode,
}: {
  orderId: number;
  orderCode: string;
}) {
  return (
    <form
      action={deleteOrderAction}
      onSubmit={(event) => {
        if (
          !window.confirm(
            `${orderCode} siparişi kalıcı olarak silinsin mi? Tamamlanan / teslim edilen siparişler zaten silinemez.`,
          )
        ) {
          event.preventDefault();
        }
      }}
    >
      <input type="hidden" name="id" value={orderId} />
      <button type="submit" className="admin-btn danger">
        <i className="fas fa-trash" /> Siparişi Sil
      </button>
    </form>
  );
}
