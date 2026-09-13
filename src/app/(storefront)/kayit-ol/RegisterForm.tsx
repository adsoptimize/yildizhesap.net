"use client";

import Link from "next/link";
import { useActionState } from "react";
import { registerCustomerAction } from "./actions";
import { INITIAL_REGISTER_STATE } from "@/lib/shop/form-state";

export function CustomerRegisterForm() {
  const [state, formAction, pending] = useActionState(
    registerCustomerAction,
    INITIAL_REGISTER_STATE,
  );

  return (
    <form action={formAction} className="shop-form">
      {state.error === null ? null : (
        <div className="shop-alert error">{state.error}</div>
      )}

      <div className="shop-field-row">
        <div className="shop-field">
          <label htmlFor="firstName">Ad</label>
          <input id="firstName" name="firstName" type="text" required />
        </div>
        <div className="shop-field">
          <label htmlFor="lastName">Soyad</label>
          <input id="lastName" name="lastName" type="text" required />
        </div>
      </div>

      <div className="shop-field">
        <label htmlFor="username">Kullanıcı adı</label>
        <input
          id="username"
          name="username"
          type="text"
          autoComplete="username"
          minLength={3}
          required
        />
      </div>

      <div className="shop-field">
        <label htmlFor="email">E-posta</label>
        <input id="email" name="email" type="email" autoComplete="email" required />
      </div>

      <div className="shop-field">
        <label htmlFor="phone">Telefon (isteğe bağlı)</label>
        <input id="phone" name="phone" type="tel" autoComplete="tel" />
      </div>

      <div className="shop-field-row">
        <div className="shop-field">
          <label htmlFor="password">Şifre</label>
          <input
            id="password"
            name="password"
            type="password"
            autoComplete="new-password"
            minLength={6}
            required
          />
        </div>
        <div className="shop-field">
          <label htmlFor="passwordConfirm">Şifre (tekrar)</label>
          <input
            id="passwordConfirm"
            name="passwordConfirm"
            type="password"
            autoComplete="new-password"
            minLength={6}
            required
          />
        </div>
      </div>

      <button type="submit" className="shop-submit" disabled={pending}>
        {pending ? "Hesap oluşturuluyor..." : "Kayıt Ol"}
      </button>

      <p className="shop-hint">
        Zaten hesabınız var mı?{" "}
        <Link href="/giris-yap" className="shop-link">
          Giriş yapın
        </Link>
        .
      </p>
    </form>
  );
}
