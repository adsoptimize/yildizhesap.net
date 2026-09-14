"use client";

/**
 * Admin-side "new user" form. Uses React `useActionState` so validation
 * errors from `createUserAction` surface without a full navigation. The
 * hosting page remains a Server Component; only this form is a client
 * island.
 */

import { useActionState } from "react";
import { INITIAL_ADMIN_FORM_STATE } from "@/lib/admin/form-state";
import { createUserAction, type UserFormState } from "./actions";

export function UserCreateForm() {
  const [state, formAction, pending] = useActionState<UserFormState, FormData>(
    createUserAction,
    INITIAL_ADMIN_FORM_STATE,
  );

  return (
    <form action={formAction} className="form-grid">
      {state.error === null ? null : (
        <div className="admin-alert error" style={{ gridColumn: "1 / -1" }}>
          {state.error}
        </div>
      )}
      {state.success === null ? null : (
        <div className="admin-alert success" style={{ gridColumn: "1 / -1" }}>
          {state.success}
        </div>
      )}

      <div className="form-field">
        <label htmlFor="new-username">Kullanıcı adı</label>
        <input
          id="new-username"
          name="username"
          type="text"
          required
          autoComplete="off"
          minLength={3}
          maxLength={32}
          placeholder="ornek: ahmetd"
        />
      </div>
      <div className="form-field">
        <label htmlFor="new-email">E-posta</label>
        <input
          id="new-email"
          name="email"
          type="email"
          required
          autoComplete="off"
          maxLength={120}
        />
      </div>
      <div className="form-field">
        <label htmlFor="new-password">Şifre</label>
        <input
          id="new-password"
          name="password"
          type="text"
          required
          minLength={8}
          autoComplete="new-password"
          placeholder="min. 8 karakter"
        />
      </div>
      <div className="form-field">
        <label htmlFor="new-first-name">Ad (opsiyonel)</label>
        <input id="new-first-name" name="firstName" type="text" maxLength={80} />
      </div>
      <div className="form-field">
        <label htmlFor="new-last-name">Soyad (opsiyonel)</label>
        <input id="new-last-name" name="lastName" type="text" maxLength={80} />
      </div>
      <div className="form-field">
        <label htmlFor="new-phone">Telefon (opsiyonel)</label>
        <input id="new-phone" name="phone" type="text" maxLength={30} />
      </div>
      <div className="form-field">
        <label className="checkbox-field">
          <input type="checkbox" name="isAdmin" /> Admin yetkisi ver
        </label>
      </div>
      <div className="form-field" style={{ justifyContent: "flex-end" }}>
        <button type="submit" className="admin-btn" disabled={pending}>
          <i className="fas fa-user-plus" />{" "}
          {pending ? "Ekleniyor..." : "Kullanıcı Ekle"}
        </button>
      </div>
    </form>
  );
}
