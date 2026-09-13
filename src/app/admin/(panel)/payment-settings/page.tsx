import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { StatefulForm } from "@/components/admin/StatefulForm";
import { requireAdmin } from "@/lib/auth/admin";
import { formatCurrency, formatDateTime } from "@/lib/admin/format";
import { prisma } from "@/lib/prisma";
import { savePaymentSettingsAction } from "./actions";

type PaymentField = {
  key: string;
  label: string;
  kind: "text" | "secret" | "boolean";
};

/** Same keys the legacy admin/crypto-settings.php page managed. */
const PAYMENT_GROUPS: readonly { title: string; fields: readonly PaymentField[] }[] = [
  {
    title: "Cryptomus (kripto ödeme)",
    fields: [
      { key: "cryptomus_enabled", label: "Cryptomus aktif", kind: "boolean" },
      { key: "cryptomus_test_mode", label: "Test modu", kind: "boolean" },
      { key: "cryptomus_merchant_uuid", label: "Merchant UUID", kind: "text" },
      { key: "cryptomus_payment_key", label: "Payment key", kind: "secret" },
      { key: "cryptomus_payout_key", label: "Payout key", kind: "secret" },
      { key: "cryptomus_webhook_secret", label: "Webhook secret", kind: "secret" },
    ],
  },
  {
    title: "Shopier (kredi kartı)",
    fields: [
      { key: "shopier_enabled", label: "Shopier aktif", kind: "boolean" },
      { key: "shopier_test_mode", label: "Test modu", kind: "boolean" },
      { key: "shopier_username", label: "API kullanıcı adı", kind: "text" },
      { key: "shopier_key", label: "API key", kind: "secret" },
    ],
  },
];

const RECENT_PAYMENT_LIMIT = 10;

export default async function AdminPaymentSettingsPage() {
  const admin = await requireAdmin();

  const [settings, payments] = await Promise.all([
    prisma.cryptoSetting.findMany({
      select: { settingKey: true, settingValue: true },
    }),
    prisma.cryptoPayment.findMany({
      orderBy: { createdAt: "desc" },
      take: RECENT_PAYMENT_LIMIT,
      select: {
        id: true,
        orderId: true,
        amount: true,
        currency: true,
        status: true,
        createdAt: true,
        user: { select: { username: true } },
      },
    }),
  ]);

  const valueByKey = new Map(
    settings.map((setting) => [setting.settingKey, setting.settingValue ?? ""]),
  );

  return (
    <>
      <AdminPageHeader
        title="Ödeme Ayarları"
        subtitle="Anahtarlar maskelenir; alanı boş bırakırsan mevcut değer korunur"
        userName={`${admin.firstName} ${admin.lastName}`}
      />

      <StatefulForm action={savePaymentSettingsAction} submitLabel="Ayarları Kaydet">
        {PAYMENT_GROUPS.map((group) => (
          <div className="content-card" key={group.title}>
            <div className="card-header">
              <h2 className="card-title">{group.title}</h2>
            </div>
            <div className="card-body">
              <div className="form-grid">
                {group.fields.map((field) => {
                  const fieldName = `payment__${field.key}`;
                  const stored = valueByKey.get(field.key) ?? "";

                  if (field.kind === "boolean") {
                    return (
                      <div className="form-field" key={field.key}>
                        <label htmlFor={fieldName}>{field.label}</label>
                        <input type="hidden" name="boolean_key" value={field.key} />
                        <label className="checkbox-field">
                          <input
                            id={fieldName}
                            name={fieldName}
                            type="checkbox"
                            value="1"
                            defaultChecked={stored === "1"}
                          />
                          Aktif
                        </label>
                      </div>
                    );
                  }

                  if (field.kind === "secret") {
                    return (
                      <div className="form-field" key={field.key}>
                        <label htmlFor={fieldName}>{field.label}</label>
                        <input type="hidden" name="secret_key" value={field.key} />
                        <input
                          id={fieldName}
                          name={fieldName}
                          type="password"
                          autoComplete="new-password"
                          placeholder={stored === "" ? "tanımlı değil" : "•••••• (kayıtlı)"}
                        />
                      </div>
                    );
                  }

                  return (
                    <div className="form-field" key={field.key}>
                      <label htmlFor={fieldName}>{field.label}</label>
                      <input
                        id={fieldName}
                        name={fieldName}
                        type="text"
                        defaultValue={stored}
                      />
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        ))}
      </StatefulForm>

      <div className="content-card">
        <div className="card-header">
          <h2 className="card-title">Son Kripto Ödemeleri</h2>
        </div>
        <div className="card-body table-scroll">
          {payments.length === 0 ? (
            <div className="admin-empty">Kayıt yok.</div>
          ) : (
            <table className="data-table">
              <thead>
                <tr>
                  <th>Sipariş</th>
                  <th>Kullanıcı</th>
                  <th>Tutar</th>
                  <th>Durum</th>
                  <th>Tarih</th>
                </tr>
              </thead>
              <tbody>
                {payments.map((payment) => (
                  <tr key={payment.id}>
                    <td>{payment.orderId}</td>
                    <td>{payment.user.username}</td>
                    <td>
                      {payment.currency === "TRY"
                        ? formatCurrency(payment.amount.toString())
                        : `${payment.amount.toString()} ${payment.currency}`}
                    </td>
                    <td>
                      <span className="admin-badge info">{payment.status}</span>
                    </td>
                    <td>{formatDateTime(payment.createdAt)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </>
  );
}
