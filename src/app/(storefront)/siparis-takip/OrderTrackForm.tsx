"use client";

import { useActionState } from "react";
import { CopyButton } from "@/components/CopyButton";
import { trackOrderAction } from "./actions";
import { INITIAL_TRACK_STATE } from "@/lib/shop/form-state";

const TRY_FORMATTER = new Intl.NumberFormat("tr-TR", {
  style: "currency",
  currency: "TRY",
  maximumFractionDigits: 2,
});

const DATE_FORMATTER = new Intl.DateTimeFormat("tr-TR", {
  dateStyle: "short",
  timeStyle: "short",
});

const ORDER_STATUS_LABELS: Readonly<Record<string, string>> = {
  pending: "Beklemede",
  processing: "İşleniyor",
  completed: "Tamamlandı",
  cancelled: "İptal Edildi",
};

const DELIVERY_STATUS_LABELS: Readonly<Record<string, string>> = {
  pending: "Hazırlanıyor",
  partial: "Kısmi teslim",
  delivered: "Teslim edildi",
  failed: "Başarısız",
};

export function OrderTrackForm() {
  const [state, formAction, pending] = useActionState(
    trackOrderAction,
    INITIAL_TRACK_STATE,
  );

  return (
    <>
      <div className="shop-panel shop-panel-narrow">
        <form action={formAction} className="shop-form">
          {state.error === null ? null : (
            <div className="shop-alert error">{state.error}</div>
          )}

          <div className="shop-field">
            <label htmlFor="orderCode">Sipariş Numarası</label>
            <input
              id="orderCode"
              name="orderCode"
              type="text"
              placeholder="YH-XXXXXXXX"
              required
            />
          </div>

          <div className="shop-field">
            <label htmlFor="email">E-posta Adresi</label>
            <input id="email" name="email" type="email" required />
            <span className="shop-hint">
              Sipariş sırasında girdiğiniz e-posta adresi.
            </span>
          </div>

          <button type="submit" className="shop-submit" disabled={pending}>
            {pending ? "Sorgulanıyor..." : "Siparişimi Sorgula"}
          </button>
        </form>
      </div>

      {state.order === null ? null : (
        <div className="shop-panel" style={{ marginTop: 24 }}>
          <h2 className="shop-panel-title">
            <i className="fas fa-receipt" /> Sipariş {state.order.orderCode}
          </h2>

          <div className="shop-summary-row">
            <span>Ürün</span>
            <span>{state.order.productName}</span>
          </div>
          <div className="shop-summary-row">
            <span>Adet</span>
            <span>{state.order.quantity}</span>
          </div>
          <div className="shop-summary-row">
            <span>Tutar</span>
            <span>{TRY_FORMATTER.format(Number(state.order.totalPrice))}</span>
          </div>
          <div className="shop-summary-row">
            <span>Ödeme</span>
            <span>
              {state.order.paymentStatus === "paid"
                ? "Onaylandı"
                : "Bekleniyor"}
            </span>
          </div>
          <div className="shop-summary-row">
            <span>Sipariş durumu</span>
            <span className={`shop-badge ${state.order.status}`}>
              {ORDER_STATUS_LABELS[state.order.status] ?? state.order.status}
            </span>
          </div>
          <div className="shop-summary-row">
            <span>Teslimat</span>
            <span>
              {DELIVERY_STATUS_LABELS[state.order.deliveryStatus] ??
                state.order.deliveryStatus}
            </span>
          </div>
          <div className="shop-summary-row">
            <span>Tarih</span>
            <span>{DATE_FORMATTER.format(new Date(state.order.createdAt))}</span>
          </div>

          {state.order.credentials.length === 0 ? (
            <div className="shop-alert info" style={{ marginTop: 20 }}>
              {state.order.paymentStatus === "paid"
                ? "Hesaplarınız hazırlanıyor, kısa süre içinde bu sayfada görünecek."
                : "Ödeme onaylandıktan sonra hesap bilgileri burada görünecek."}
            </div>
          ) : (
            <div className="shop-credentials">
              <strong>Hesap Bilgileriniz</strong>
              <div className="shop-table-wrap" style={{ marginTop: 12 }}>
                <table className="shop-table">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Kullanıcı adı</th>
                      <th>Şifre</th>
                      <th>E-posta</th>
                      <th>E-posta şifresi</th>
                      <th>2FA</th>
                      <th />
                    </tr>
                  </thead>
                  <tbody>
                    {state.order.credentials.map((row, index) => (
                      <tr key={row.id}>
                        <td>{index + 1}</td>
                        <td className="shop-mono">{row.username}</td>
                        <td className="shop-mono">{row.password}</td>
                        <td className="shop-mono">{row.email ?? "-"}</td>
                        <td className="shop-mono">{row.emailPassword ?? "-"}</td>
                        <td className="shop-mono">
                          {row.totpSecret === null ? (
                            "-"
                          ) : (
                            <a
                              href={`/2fa?secret=${encodeURIComponent(row.totpSecret)}`}
                              className="shop-link"
                              target="_blank"
                              rel="noreferrer"
                            >
                              {row.totpSecret} <i className="fas fa-key" />
                            </a>
                          )}
                        </td>
                        <td>
                          <CopyButton
                            value={[
                              row.username,
                              row.password,
                              row.email ?? "",
                              row.emailPassword ?? "",
                              row.totpSecret ?? "",
                            ]
                              .filter((part) => part !== "")
                              .join(":")}
                          />
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <p className="shop-hint" style={{ marginTop: 12 }}>
                Bu bilgileri güvenli bir yerde saklayın ve kimseyle paylaşmayın.
              </p>
            </div>
          )}
        </div>
      )}
    </>
  );
}
