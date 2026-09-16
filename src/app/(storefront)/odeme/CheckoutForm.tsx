"use client";

import { useActionState, useState } from "react";
import { startCheckoutAction } from "./actions";
import { INITIAL_CHECKOUT_STATE } from "@/lib/shop/form-state";
import type { PaymentMethod } from "@/lib/shop/constants";

type CheckoutFormProps = {
  isLoggedIn: boolean;
  availableMethods: readonly PaymentMethod[];
  customerName: string;
  customerEmail: string;
};

const METHOD_LABELS: Readonly<Record<PaymentMethod, string>> = {
  cryptomus: "Kripto para (USDT, BTC, ETH ve diğerleri)",
  shopier: "Kredi / banka kartı (Shopier)",
  iyzico: "Kredi / banka kartı (Iyzico)",
};

const METHOD_ICONS: Readonly<Record<PaymentMethod, string>> = {
  cryptomus: "fab fa-bitcoin",
  shopier: "fas fa-credit-card",
  iyzico: "fas fa-credit-card",
};

const METHOD_HINTS: Readonly<Record<PaymentMethod, string>> = {
  cryptomus: "Cryptomus ödeme sayfasına yönlendirileceksiniz.",
  shopier: "Shopier güvenli ödeme sayfasına yönlendirileceksiniz.",
  iyzico: "Iyzico güvenli ödeme sayfasına yönlendirileceksiniz.",
};

export function CheckoutForm({
  isLoggedIn,
  availableMethods,
  customerName,
  customerEmail,
}: CheckoutFormProps) {
  const [state, formAction, pending] = useActionState(
    startCheckoutAction,
    INITIAL_CHECKOUT_STATE,
  );
  const [method, setMethod] = useState<PaymentMethod | "">(
    availableMethods[0] ?? "",
  );

  if (availableMethods.length === 0) {
    return (
      <div className="shop-alert error">
        Ödeme yöntemleri şu anda aktif değil. Lütfen WhatsApp veya Telegram
        üzerinden bizimle iletişime geçin.
      </div>
    );
  }

  return (
    <form action={formAction} className="shop-form">
      {state.error === null ? null : (
        <div className="shop-alert error">{state.error}</div>
      )}

      {isLoggedIn ? (
        <div className="shop-alert info">
          <strong>{customerName}</strong> hesabıyla satın alıyorsunuz. Hesap
          bilgileri {customerEmail} adresine bağlı panelinizde görünecek.
        </div>
      ) : (
        <>
          <div className="shop-field">
            <label htmlFor="guestName">Ad Soyad</label>
            <input id="guestName" name="guestName" type="text" required />
          </div>
          <div className="shop-field">
            <label htmlFor="guestEmail">E-posta</label>
            <input id="guestEmail" name="guestEmail" type="email" required />
            <span className="shop-hint">
              Sipariş takibi için gerekli. Hesap bilgilerinize bu e-posta ve
              sipariş kodu ile ulaşabilirsiniz.
            </span>
          </div>
          <div className="shop-field">
            <label htmlFor="guestPhone">Telefon (isteğe bağlı)</label>
            <input id="guestPhone" name="guestPhone" type="tel" />
          </div>
        </>
      )}

      <div className="shop-field">
        <label htmlFor="paymentMethod">Ödeme yöntemi</label>
        <select
          id="paymentMethod"
          name="paymentMethod"
          value={method}
          onChange={(event) => setMethod(event.target.value as PaymentMethod)}
          required
        >
          {availableMethods.map((item) => (
            <option key={item} value={item}>
              {METHOD_LABELS[item]}
            </option>
          ))}
        </select>
        {method === "" ? null : (
          <span className="shop-hint">
            <i className={METHOD_ICONS[method]} /> {METHOD_HINTS[method]}
          </span>
        )}
      </div>

      {isLoggedIn ? null : (
        <label className="shop-checkbox">
          <input type="checkbox" name="agreeTerms" required />
          <span>
            Satın alınan dijital ürünlerin teslim sonrası iade edilemeyeceğini ve
            kullanım şartlarını kabul ediyorum.
          </span>
        </label>
      )}

      <button type="submit" className="shop-submit" disabled={pending}>
        {pending ? "Ödeme hazırlanıyor..." : "Ödemeyi Tamamla"}
      </button>
    </form>
  );
}
