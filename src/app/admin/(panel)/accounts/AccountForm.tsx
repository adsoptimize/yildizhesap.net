"use client";

import { useActionState } from "react";
import { saveAccountAction, type AccountFormState } from "./actions";

export type AccountFormValues = {
  id: number;
  categoryId: number;
  title: string;
  seoSlug: string;
  description: string;
  price: string;
  oldPrice: string;
  stockQuantity: number;
  platform: string;
  accountType: string;
  limitInfo: string;
  technicalInfo: string;
  location: string;
  warrantyDays: number;
  isPublished: boolean;
  isVerified: boolean;
  isPremium: boolean;
  isFeatured: boolean;
  isSecure: boolean;
  instantDelivery: boolean;
  support247: boolean;
  guarantee30Days: boolean;
};

export type CategoryOption = {
  id: number;
  name: string;
};

type AccountFormProps = {
  categories: CategoryOption[];
  account?: AccountFormValues;
};

const INITIAL_STATE: AccountFormState = { error: null, success: null };

const FLAG_FIELDS = [
  { name: "isPublished", label: "Sitede yayında" },
  { name: "isVerified", label: "Doğrulanmış" },
  { name: "isPremium", label: "Premium" },
  { name: "isFeatured", label: "Öne çıkan" },
  { name: "isSecure", label: "Güvenli hesap" },
  { name: "instantDelivery", label: "Anında teslimat" },
  { name: "support247", label: "7/24 destek" },
  { name: "guarantee30Days", label: "Garanti kapsamında" },
] as const;

export function AccountForm({ categories, account }: AccountFormProps) {
  const [state, formAction, pending] = useActionState(
    saveAccountAction,
    INITIAL_STATE,
  );
  const isEdit = account !== undefined;

  const flagDefaults: Readonly<Record<string, boolean>> = {
    isPublished: account?.isPublished ?? false,
    isVerified: account?.isVerified ?? true,
    isPremium: account?.isPremium ?? false,
    isFeatured: account?.isFeatured ?? false,
    isSecure: account?.isSecure ?? false,
    instantDelivery: account?.instantDelivery ?? true,
    support247: account?.support247 ?? true,
    guarantee30Days: account?.guarantee30Days ?? true,
  };

  return (
    <form action={formAction} className="admin-form">
      {state.error === null ? null : (
        <div className="admin-alert error">{state.error}</div>
      )}
      {state.success === null ? null : (
        <div className="admin-alert success">{state.success}</div>
      )}

      {isEdit ? <input type="hidden" name="id" value={account.id} /> : null}

      <div className="form-grid">
        <div className="form-field">
          <label htmlFor="title">Ürün başlığı</label>
          <input
            id="title"
            name="title"
            type="text"
            defaultValue={account?.title ?? ""}
            required
          />
        </div>

        <div className="form-field">
          <label htmlFor="categoryId">Kategori</label>
          <select
            id="categoryId"
            name="categoryId"
            defaultValue={account?.categoryId ?? ""}
            required
          >
            <option value="">Seçin</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
        </div>

        <div className="form-field">
          <label htmlFor="seoSlug">SEO slug (URL)</label>
          <input
            id="seoSlug"
            name="seoSlug"
            type="text"
            defaultValue={account?.seoSlug ?? ""}
            placeholder="boş bırakılırsa başlıktan üretilir"
          />
        </div>

        <div className="form-field">
          <label htmlFor="price">Fiyat (TL)</label>
          <input
            id="price"
            name="price"
            type="text"
            inputMode="decimal"
            defaultValue={account?.price ?? ""}
            required
          />
        </div>

        <div className="form-field">
          <label htmlFor="oldPrice">Eski fiyat (opsiyonel)</label>
          <input
            id="oldPrice"
            name="oldPrice"
            type="text"
            inputMode="decimal"
            defaultValue={account?.oldPrice ?? ""}
          />
        </div>

        <div className="form-field">
          <label htmlFor="stockQuantity">Stok adedi</label>
          <input
            id="stockQuantity"
            name="stockQuantity"
            type="number"
            min={0}
            defaultValue={account?.stockQuantity ?? 0}
          />
        </div>

        <div className="form-field">
          <label htmlFor="platform">Platform</label>
          <input
            id="platform"
            name="platform"
            type="text"
            defaultValue={account?.platform ?? "Facebook"}
          />
        </div>

        <div className="form-field">
          <label htmlFor="accountType">Hesap türü</label>
          <input
            id="accountType"
            name="accountType"
            type="text"
            defaultValue={account?.accountType ?? ""}
          />
        </div>

        <div className="form-field">
          <label htmlFor="limitInfo">Limit bilgisi</label>
          <input
            id="limitInfo"
            name="limitInfo"
            type="text"
            defaultValue={account?.limitInfo ?? ""}
          />
        </div>

        <div className="form-field">
          <label htmlFor="location">Ülke / etiket</label>
          <input
            id="location"
            name="location"
            type="text"
            defaultValue={account?.location ?? ""}
          />
        </div>

        <div className="form-field">
          <label htmlFor="warrantyDays">Garanti (gün)</label>
          <input
            id="warrantyDays"
            name="warrantyDays"
            type="number"
            min={0}
            defaultValue={account?.warrantyDays ?? 30}
          />
        </div>
      </div>

      <div className="form-field">
        <label htmlFor="description">Ürün açıklaması</label>
        <textarea
          id="description"
          name="description"
          defaultValue={account?.description ?? ""}
        />
      </div>

      <div className="form-field">
        <label htmlFor="technicalInfo">Teknik bilgi / notlar</label>
        <textarea
          id="technicalInfo"
          name="technicalInfo"
          defaultValue={account?.technicalInfo ?? ""}
        />
      </div>

      <div className="checkbox-grid">
        {FLAG_FIELDS.map((field) => (
          <label className="checkbox-field" key={field.name}>
            <input
              type="checkbox"
              name={field.name}
              defaultChecked={flagDefaults[field.name]}
            />
            {field.label}
          </label>
        ))}
      </div>

      <div>
        <button type="submit" className="admin-btn" disabled={pending}>
          <i className="fas fa-save" />{" "}
          {pending ? "Kaydediliyor..." : isEdit ? "Değişiklikleri Kaydet" : "Ürünü Oluştur"}
        </button>
      </div>
    </form>
  );
}
