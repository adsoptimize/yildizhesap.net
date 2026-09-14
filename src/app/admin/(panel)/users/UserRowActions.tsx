"use client";

/**
 * Client-side controls for the per-row user admin actions:
 *   - Admin role toggle (with confirm)
 *   - Password reset with inline validation feedback
 *   - Permanent delete with confirm
 *
 * Each action is wrapped so validation errors from the server action surface
 * on the same row without navigating away.
 */

import { useActionState } from "react";
import { INITIAL_ADMIN_FORM_STATE } from "@/lib/admin/form-state";
import {
  deleteUserAction,
  resetUserPasswordAction,
  toggleUserAdminAction,
  type UserFormState,
} from "./actions";

type UserRowActionsProps = {
  userId: number;
  username: string;
  isTargetAdmin: boolean;
  /** Callers use this to prevent self-mutation of the logged-in admin. */
  isSelf: boolean;
};

export function UserRowActions({
  userId,
  username,
  isTargetAdmin,
  isSelf,
}: UserRowActionsProps) {
  const [resetState, resetAction, resetPending] = useActionState<
    UserFormState,
    FormData
  >(resetUserPasswordAction, INITIAL_ADMIN_FORM_STATE);

  return (
    <div style={{ display: "flex", flexDirection: "column", gap: "0.4rem" }}>
      <form
        action={resetAction}
        style={{ display: "flex", gap: "0.3rem", alignItems: "center" }}
      >
        <input type="hidden" name="id" value={userId} />
        <input
          name="newPassword"
          type="text"
          placeholder="yeni şifre"
          minLength={8}
          autoComplete="off"
          style={{
            width: 130,
            padding: "0.3rem 0.5rem",
            border: "1px solid #e2e8f0",
            borderRadius: 8,
            fontSize: "0.8rem",
          }}
          aria-label={`${username} için yeni şifre`}
        />
        <button
          type="submit"
          className="admin-btn secondary small"
          disabled={resetPending}
          title="Şifreyi sıfırla"
        >
          <i className="fas fa-key" />
        </button>
      </form>

      {resetState.error === null ? null : (
        <span style={{ fontSize: "0.7rem", color: "#dc2626" }}>
          {resetState.error}
        </span>
      )}
      {resetState.success === null ? null : (
        <span style={{ fontSize: "0.7rem", color: "#16a34a" }}>
          {resetState.success}
        </span>
      )}

      {isSelf ? (
        <span className="admin-badge info" title="Kendi hesabınız">
          siz
        </span>
      ) : (
        <>
          <form
            action={toggleUserAdminAction}
            onSubmit={(event) => {
              const message = isTargetAdmin
                ? `${username} kullanıcısının admin yetkisi kaldırılsın mı?`
                : `${username} kullanıcısına admin yetkisi verilsin mi?`;
              if (!window.confirm(message)) {
                event.preventDefault();
              }
            }}
          >
            <input type="hidden" name="id" value={userId} />
            <button
              type="submit"
              className={`admin-btn small${isTargetAdmin ? " danger" : " secondary"}`}
              title={isTargetAdmin ? "Admin yetkisini kaldır" : "Admin yap"}
            >
              <i
                className={
                  isTargetAdmin ? "fas fa-user-shield" : "fas fa-user-plus"
                }
              />{" "}
              {isTargetAdmin ? "Admin yetkisini al" : "Admin yap"}
            </button>
          </form>

          <form
            action={deleteUserAction}
            onSubmit={(event) => {
              if (
                !window.confirm(
                  `${username} kullanıcısı kalıcı olarak silinsin mi? Yalnızca sipariş/destek geçmişi olmayan kullanıcılar silinebilir.`,
                )
              ) {
                event.preventDefault();
              }
            }}
          >
            <input type="hidden" name="id" value={userId} />
            <button
              type="submit"
              className="admin-btn danger small"
              title="Kullanıcıyı kalıcı sil"
            >
              <i className="fas fa-trash" /> Sil
            </button>
          </form>
        </>
      )}
    </div>
  );
}
