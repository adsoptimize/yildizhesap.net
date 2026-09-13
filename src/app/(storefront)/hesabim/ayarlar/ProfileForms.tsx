"use client";

import { useActionState } from "react";
import { changePasswordAction, updateProfileAction } from "../actions";
import { INITIAL_CUSTOMER_FORM_STATE } from "@/lib/shop/form-state";

type ProfileFormProps = {
  firstName: string;
  lastName: string;
  phone: string;
  email: string;
  username: string;
};

export function ProfileForm({
  firstName,
  lastName,
  phone,
  email,
  username,
}: ProfileFormProps) {
  const [state, formAction, pending] = useActionState(
    updateProfileAction,
    INITIAL_CUSTOMER_FORM_STATE,
  );

  return (
    <form action={formAction} className="shop-form">
      {state.error === null ? null : (
        <div className="shop-alert error">{state.error}</div>
      )}
      {state.success === null ? null : (
        <div className="shop-alert success">{state.success}</div>
      )}

      <div className="shop-field-row">
        <div className="shop-field">
          <label htmlFor="firstName">Ad</label>
          <input
            id="firstName"
            name="firstName"
            type="text"
            defaultValue={firstName}
            required
          />
        </div>
        <div className="shop-field">
          <label htmlFor="lastName">Soyad</label>
          <input
            id="lastName"
            name="lastName"
            type="text"
            defaultValue={lastName}
            required
          />
        </div>
      </div>

      <div className="shop-field">
        <label htmlFor="phone">Telefon</label>
        <input id="phone" name="phone" type="tel" defaultValue={phone} />
      </div>

      <div className="shop-field-row">
        <div className="shop-field">
          <label htmlFor="username">Kullanıcı adı</label>
          <input id="username" type="text" defaultValue={username} disabled />
        </div>
        <div className="shop-field">
          <label htmlFor="email">E-posta</label>
          <input id="email" type="email" defaultValue={email} disabled />
        </div>
      </div>

      <button type="submit" className="shop-submit" disabled={pending}>
        {pending ? "Kaydediliyor..." : "Bilgilerimi Güncelle"}
      </button>
    </form>
  );
}

export function PasswordForm() {
  const [state, formAction, pending] = useActionState(
    changePasswordAction,
    INITIAL_CUSTOMER_FORM_STATE,
  );

  return (
    <form action={formAction} className="shop-form">
      {state.error === null ? null : (
        <div className="shop-alert error">{state.error}</div>
      )}
      {state.success === null ? null : (
        <div className="shop-alert success">{state.success}</div>
      )}

      <div className="shop-field">
        <label htmlFor="currentPassword">Mevcut şifre</label>
        <input
          id="currentPassword"
          name="currentPassword"
          type="password"
          autoComplete="current-password"
          required
        />
      </div>

      <div className="shop-field-row">
        <div className="shop-field">
          <label htmlFor="newPassword">Yeni şifre</label>
          <input
            id="newPassword"
            name="newPassword"
            type="password"
            autoComplete="new-password"
            minLength={6}
            required
          />
        </div>
        <div className="shop-field">
          <label htmlFor="confirmPassword">Yeni şifre (tekrar)</label>
          <input
            id="confirmPassword"
            name="confirmPassword"
            type="password"
            autoComplete="new-password"
            minLength={6}
            required
          />
        </div>
      </div>

      <button type="submit" className="shop-submit" disabled={pending}>
        {pending ? "Güncelleniyor..." : "Şifremi Değiştir"}
      </button>
    </form>
  );
}
