"use client";

import Link from "next/link";
import { useActionState } from "react";
import { requestPasswordResetAction } from "./actions";
import { INITIAL_FORGOT_PASSWORD_STATE } from "@/lib/shop/form-state";

export function ForgotPasswordForm() {
  const [state, formAction, pending] = useActionState(
    requestPasswordResetAction,
    INITIAL_FORGOT_PASSWORD_STATE,
  );

  if (state.submitted) {
    return (
      <>
        <div className="shop-alert success">
          <i className="fas fa-envelope-open-text" /> E-posta adresiniz kayıtlıysa
          şifre sıfırlama bağlantısı gönderildi. Gelen kutunuzu kontrol edin.
        </div>
        <p className="shop-hint">
          Bağlantı 60 dakika geçerlidir ve yalnızca bir kez kullanılabilir.
          E-posta birkaç dakika içinde gelmezse spam klasörünü kontrol edin.
        </p>
        <p className="shop-hint">
          <Link href="/giris-yap" className="shop-link">
            Giriş sayfasına dön
          </Link>
        </p>
      </>
    );
  }

  return (
    <form action={formAction} className="shop-form">
      {state.error === null ? null : (
        <div className="shop-alert error">{state.error}</div>
      )}

      <p className="shop-hint" style={{ marginTop: 0 }}>
        Hesabınızın e-posta adresini girin; yeni şifre belirlemeniz için bir
        bağlantı gönderelim.
      </p>

      <div className="shop-field">
        <label htmlFor="email">E-posta adresi</label>
        <input
          id="email"
          name="email"
          type="email"
          autoComplete="email"
          required
        />
      </div>

      <button type="submit" className="shop-submit" disabled={pending}>
        {pending ? "Gönderiliyor..." : "Sıfırlama Bağlantısı Gönder"}
      </button>

      <p className="shop-hint">
        Şifrenizi hatırladınız mı?{" "}
        <Link href="/giris-yap" className="shop-link">
          Giriş yapın
        </Link>
        .
      </p>
    </form>
  );
}
