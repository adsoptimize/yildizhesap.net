"use client";

import { useState } from "react";

const FEEDBACK_MS = 1_500;

type CopyButtonProps = {
  value: string;
  label?: string;
};

export function CopyButton({ value, label = "Kopyala" }: CopyButtonProps) {
  const [copied, setCopied] = useState(false);

  return (
    <button
      type="button"
      className="shop-btn"
      onClick={async () => {
        try {
          await navigator.clipboard.writeText(value);
          setCopied(true);
          window.setTimeout(() => setCopied(false), FEEDBACK_MS);
        } catch {
          setCopied(false);
        }
      }}
    >
      <i className={copied ? "fas fa-check" : "fas fa-copy"} />{" "}
      {copied ? "Kopyalandı" : label}
    </button>
  );
}
