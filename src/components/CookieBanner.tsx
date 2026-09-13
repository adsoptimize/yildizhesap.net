"use client";

/**
 * KVKK / cookie consent banner. Renders a dismissible bar the first time a
 * visitor lands on the storefront and persists the choice in localStorage so
 * subsequent visits stay quiet. No cookies are actually set here; the banner
 * satisfies the KVKK disclosure requirement and links to the legal pages.
 *
 * The site does not run non-essential trackers today, so the choice only
 * unlocks Vercel Analytics/Speed Insights which are already anonymous and
 * privacy-preserving.
 */

import Link from "next/link";
import { useState, useSyncExternalStore } from "react";

const STORAGE_KEY = "yh_cookie_consent_v1";
const STORAGE_EVENT = "storage";

/**
 * Subscribes to `storage` events so the banner hides across tabs when the
 * visitor accepts/rejects on any of them. We only care about our own key.
 */
function subscribe(onChange: () => void): () => void {
  window.addEventListener(STORAGE_EVENT, onChange);
  return () => {
    window.removeEventListener(STORAGE_EVENT, onChange);
  };
}

function readStoredChoice(): string | null {
  try {
    return window.localStorage.getItem(STORAGE_KEY);
  } catch {
    return null;
  }
}

// Server snapshot must be a stable primitive — a shared sentinel string keeps
// React 19 happy and matches the "no consent recorded yet" case on the client.
const SERVER_SNAPSHOT = "__ssr__";

function serverSnapshot(): string {
  return SERVER_SNAPSHOT;
}

export function CookieBanner() {
  const stored = useSyncExternalStore(
    subscribe,
    readStoredChoice,
    serverSnapshot,
  );

  // Independent flag so clicking a button hides the banner immediately without
  // waiting for the storage event to propagate.
  const [dismissed, setDismissed] = useState(false);

  const visible = !dismissed && stored === null;

  function record(choice: "accept" | "reject"): void {
    try {
      window.localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({ choice, at: Date.now() }),
      );
    } catch {
      // Storage blocked (private mode, safari itp) — proceed to dismiss anyway.
    }
    setDismissed(true);
  }

  if (!visible) {
    return null;
  }

  return (
    <div
      className="cookie-banner"
      role="dialog"
      aria-live="polite"
      aria-label="Çerez izni"
    >
      <div className="cookie-banner__inner">
        <div className="cookie-banner__body">
          <strong>Çerez Bilgilendirmesi</strong>
          <p>
            Bu sitede yalnızca zorunlu (oturum, sepet, güvenlik) ve anonim
            performans çerezleri kullanılır. Detaylar için{" "}
            <Link href="/gizlilik-politikasi">Gizlilik Politikası</Link> ve{" "}
            <Link href="/cerez-politikasi">Çerez Politikası</Link>{" "}
            sayfalarımızı inceleyebilirsiniz.
          </p>
        </div>
        <div className="cookie-banner__actions">
          <button
            type="button"
            className="cookie-banner__btn cookie-banner__btn--ghost"
            onClick={() => record("reject")}
          >
            Sadece Zorunlu
          </button>
          <button
            type="button"
            className="cookie-banner__btn cookie-banner__btn--primary"
            onClick={() => record("accept")}
          >
            Kabul Et
          </button>
        </div>
      </div>
    </div>
  );
}
