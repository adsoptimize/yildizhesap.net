"use client";

import Link from "next/link";
import { useActionState } from "react";
import { resetPasswordAction } from "./actions";
import { LOGIN_PATH, MIN_PASSWORD_LENGTH } from "@/lib/shop/constants";
import { INITIAL_RESET_PASSWORD_STATE } from "@/lib/shop/form-state";

type Props = {
  token: string;
};

export function ResetPasswordForm({ token }: Props) {
  const [state, formAction, pending] = useActionState(
    resetPasswordAction,
    INITIAL_RESET_PASSWORD_STATE,
  );

  if (state.done) {
    return (
      <>
        <div className="shop-alert success">
          <i className="fas fa-circle-check" /> Şifreniz güncellendi. Yeni
          şifrenizle giriş yapabilirsiniz.
        </div>
        <p className="shop-hint">
          Güvenlik için hesabınızdaki açık tüm oturumlar kapatıldı, bu yüzden
          diğer cihazlarınızda yeniden giriş yapmanız gerekecek.
        </p>
        <Link href={LOGIN_PATH} className="shop-submit" style={{ marginTop: 8 }}>
          Giriş Yap
        </Link>
      </>
    );
  }

  return (
    <form action={formAction} className="shop-form">
      {state.error === null ? null : (
        <div className="shop-alert error">{state.error}</div>
      )}

      <input type="hidden" name="token" value={token} />

      <div className="shop-field">
        <label htmlFor="password">Yeni şifre</label>
        <input
          id="password"
          name="password"
          type="password"
          autoComplete="new-password"
          minLength={MIN_PASSWORD_LENGTH}
          required
        />
      </div>

      <div className="shop-field">
        <label htmlFor="passwordConfirm">Yeni şifre (tekrar)</label>
        <input
          id="passwordConfirm"
          name="passwordConfirm"
          type="password"
          autoComplete="new-password"
          minLength={MIN_PASSWORD_LENGTH}
          required
        />
      </div>

      <button type="submit" className="shop-submit" disabled={pending}>
        {pending ? "Kaydediliyor..." : "Şifremi Güncelle"}
      </button>
    </form>
  );
}
