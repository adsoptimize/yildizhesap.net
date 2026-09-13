"use client";

import { useActionState } from "react";
import {
  INITIAL_ADMIN_FORM_STATE,
  type AdminFormState,
} from "@/lib/admin/form-state";

type StatefulFormProps = {
  action: (state: AdminFormState, formData: FormData) => Promise<AdminFormState>;
  submitLabel: string;
  submitIcon?: string;
  children: React.ReactNode;
};

/** Shared wrapper so every admin settings form gets the same feedback UX. */
export function StatefulForm({
  action,
  submitLabel,
  submitIcon = "fas fa-save",
  children,
}: StatefulFormProps) {
  const [state, formAction, pending] = useActionState(
    action,
    INITIAL_ADMIN_FORM_STATE,
  );

  return (
    <form action={formAction} className="admin-form">
      {state.error === null ? null : (
        <div className="admin-alert error">{state.error}</div>
      )}
      {state.success === null ? null : (
        <div className="admin-alert success">{state.success}</div>
      )}

      {children}

      <div>
        <button type="submit" className="admin-btn" disabled={pending}>
          <i className={submitIcon} /> {pending ? "Kaydediliyor..." : submitLabel}
        </button>
      </div>
    </form>
  );
}
