"use client";

import { useActionState } from "react";
import { saveCategoryAction, type CategoryFormState } from "./actions";

export type CategoryFormValues = {
  id: number;
  name: string;
  seoSlug: string;
  icon: string;
  description: string;
  isActive: boolean;
};

type CategoryFormProps = {
  category?: CategoryFormValues;
  onDone?: () => void;
};

const INITIAL_STATE: CategoryFormState = { error: null, success: null };

export function CategoryForm({ category }: CategoryFormProps) {
  const [state, formAction, pending] = useActionState(
    saveCategoryAction,
    INITIAL_STATE,
  );
  const isEdit = category !== undefined;

  return (
    <form action={formAction} className="admin-form">
      {state.error === null ? null : (
        <div className="admin-alert error">{state.error}</div>
      )}
      {state.success === null ? null : (
        <div className="admin-alert success">{state.success}</div>
      )}

      {isEdit ? <input type="hidden" name="id" value={category.id} /> : null}

      <div className="form-grid">
        <div className="form-field">
          <label htmlFor={`name-${category?.id ?? "new"}`}>Kategori adı</label>
          <input
            id={`name-${category?.id ?? "new"}`}
            name="name"
            type="text"
            defaultValue={category?.name ?? ""}
            required
          />
        </div>

        <div className="form-field">
          <label htmlFor={`seoSlug-${category?.id ?? "new"}`}>SEO slug</label>
          <input
            id={`seoSlug-${category?.id ?? "new"}`}
            name="seoSlug"
            type="text"
            defaultValue={category?.seoSlug ?? ""}
            placeholder="ornek-kategori-slug"
          />
        </div>

        <div className="form-field">
          <label htmlFor={`icon-${category?.id ?? "new"}`}>Font Awesome ikon</label>
          <input
            id={`icon-${category?.id ?? "new"}`}
            name="icon"
            type="text"
            defaultValue={category?.icon ?? "fas fa-folder"}
          />
        </div>
      </div>

      <div className="form-field">
        <label htmlFor={`description-${category?.id ?? "new"}`}>
          Açıklama (kategori sayfası ve meta için)
        </label>
        <textarea
          id={`description-${category?.id ?? "new"}`}
          name="description"
          defaultValue={category?.description ?? ""}
        />
      </div>

      <label className="checkbox-field">
        <input
          type="checkbox"
          name="isActive"
          defaultChecked={category?.isActive ?? true}
        />
        Sitede görünür
      </label>

      <div>
        <button type="submit" className="admin-btn" disabled={pending}>
          <i className="fas fa-save" />{" "}
          {pending ? "Kaydediliyor..." : isEdit ? "Güncelle" : "Kategori Ekle"}
        </button>
      </div>
    </form>
  );
}
