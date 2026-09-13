"use client";

import { useActionState } from "react";
import { loginAction, type LoginState } from "./actions";

const INITIAL_STATE: LoginState = { error: null };

export function LoginForm() {
  const [state, formAction, pending] = useActionState(loginAction, INITIAL_STATE);

  return (
    <form action={formAction} className="admin-form">
      {state.error === null ? null : (
        <div className="admin-alert error">{state.error}</div>
      )}

      <div className="form-field">
        <label htmlFor="identifier">Kullanıcı adı veya e-posta</label>
        <input
          id="identifier"
          name="identifier"
          type="text"
          autoComplete="username"
          required
        />
      </div>

      <div className="form-field">
        <label htmlFor="password">Şifre</label>
        <input
          id="password"
          name="password"
          type="password"
          autoComplete="current-password"
          required
        />
      </div>

      <button type="submit" className="admin-btn" disabled={pending}>
        <i className="fas fa-sign-in-alt" /> {pending ? "Giriş yapılıyor..." : "Giriş Yap"}
      </button>
    </form>
  );
}
