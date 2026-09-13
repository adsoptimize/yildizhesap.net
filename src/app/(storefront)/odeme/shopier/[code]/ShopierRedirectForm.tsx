"use client";

import { useEffect, useRef } from "react";

type ShopierRedirectFormProps = {
  actionUrl: string;
  fields: Readonly<Record<string, string>>;
};

/** Shopier only accepts a form POST, so the page auto-submits on load. */
export function ShopierRedirectForm({
  actionUrl,
  fields,
}: ShopierRedirectFormProps) {
  const formRef = useRef<HTMLFormElement>(null);

  useEffect(() => {
    formRef.current?.submit();
  }, []);

  return (
    <form ref={formRef} action={actionUrl} method="post">
      {Object.entries(fields).map(([name, value]) => (
        <input key={name} type="hidden" name={name} value={value} />
      ))}
      <button type="submit" className="shop-submit">
        Ödeme sayfasına git
      </button>
    </form>
  );
}
