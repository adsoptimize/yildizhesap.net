"use client";

/**
 * Contact form UI. Uses React `useActionState` to invoke the server action
 * without leaving the SSG page, mirroring how other guest flows are wired
 * (login, register, order tracking).
 */

import { useActionState } from "react";
import {
  INITIAL_CONTACT_FORM_STATE,
  submitContactAction,
} from "@/app/(storefront)/iletisim/actions";

export function ContactForm() {
  const [state, formAction, pending] = useActionState(
    submitContactAction,
    INITIAL_CONTACT_FORM_STATE,
  );

  return (
    <form action={formAction} className="contact-form" noValidate>
      <div className="contact-form__row">
        <label htmlFor="contact-name">Adınız</label>
        <input
          id="contact-name"
          name="name"
          type="text"
          required
          maxLength={80}
          autoComplete="name"
          placeholder="Adınız ve soyadınız"
        />
        {state.fieldErrors?.name === undefined ? null : (
          <span className="contact-form__field-error">
            {state.fieldErrors.name}
          </span>
        )}
      </div>

      <div className="contact-form__row">
        <label htmlFor="contact-email">E-posta</label>
        <input
          id="contact-email"
          name="email"
          type="email"
          required
          maxLength={120}
          autoComplete="email"
          placeholder="ornek@eposta.com"
        />
        {state.fieldErrors?.email === undefined ? null : (
          <span className="contact-form__field-error">
            {state.fieldErrors.email}
          </span>
        )}
      </div>

      <div className="contact-form__row">
        <label htmlFor="contact-subject">Konu (opsiyonel)</label>
        <input
          id="contact-subject"
          name="subject"
          type="text"
          maxLength={160}
          placeholder="Kısa bir başlık"
        />
        {state.fieldErrors?.subject === undefined ? null : (
          <span className="contact-form__field-error">
            {state.fieldErrors.subject}
          </span>
        )}
      </div>

      <div className="contact-form__row">
        <label htmlFor="contact-message">Mesajınız</label>
        <textarea
          id="contact-message"
          name="message"
          required
          rows={6}
          maxLength={2_000}
          placeholder="Sorunuzu veya talebinizi buraya yazın."
        />
        {state.fieldErrors?.message === undefined ? null : (
          <span className="contact-form__field-error">
            {state.fieldErrors.message}
          </span>
        )}
      </div>

      {state.status === "error" && state.message !== "" ? (
        <div className="contact-form__banner contact-form__banner--error">
          {state.message}
        </div>
      ) : null}

      {state.status === "success" ? (
        <div className="contact-form__banner contact-form__banner--success">
          {state.message}
        </div>
      ) : null}

      <button
        type="submit"
        className="btn btn-primary"
        disabled={pending}
      >
        {pending ? "Gönderiliyor..." : "Mesajı Gönder"}
      </button>
    </form>
  );
}
