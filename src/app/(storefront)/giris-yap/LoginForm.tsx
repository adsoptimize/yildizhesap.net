"use client";

import Link from "next/link";
import { useActionState } from "react";
import { loginCustomerAction } from "./actions";
import { INITIAL_LOGIN_STATE } from "@/lib/shop/form-state";

export function CustomerLoginForm() {
  const [state, formAction, pending] = useActionState(
    loginCustomerAction,
    INITIAL_LOGIN_STATE,
  );

  return (
    <form action={formAction} className="shop-form">
      {state.error === null ? null : (
        <div className="shop-alert error">{state.error}</div>
      )}

      <div className="shop-field">
        <label htmlFor="identifier">E-posta veya kullanıcı adı</label>
        <input
          id="identifier"
          name="identifier"
          type="text"
          autoComplete="username"
          required
        />
      </div>

      <div className="shop-field">
        <label htmlFor="password">Şifre</label>
        <input
          id="password"
          name="password"
          type="password"
          autoComplete="current-password"
          required
        />
      </div>

      <button type="submit" className="shop-submit" disabled={pending}>
        {pending ? "Giriş yapılıyor..." : "Giriş Yap"}
      </button>

      <p className="shop-hint">
        Hesabınız yok mu?{" "}
        <Link href="/kayit-ol" className="shop-link">
          Ücretsiz kayıt olun
        </Link>
        .
      </p>
    </form>
  );
}
