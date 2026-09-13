"use client";

import { useEffect, useState } from "react";
import type { PopupAdResponse } from "@/app/api/popup-ad/route";

/** Matches the legacy modal delay so the page paints before the ad appears. */
const SHOW_DELAY_MS = 800;

export function PopupAd() {
  const [payload, setPayload] = useState<PopupAdResponse | null>(null);
  const [closed, setClosed] = useState(false);

  useEffect(() => {
    let active = true;
    let timer: number | undefined;

    async function loadAd(): Promise<void> {
      try {
        const response = await fetch("/api/popup-ad", { cache: "no-store" });

        if (!response.ok) {
          return;
        }

        const data = (await response.json()) as PopupAdResponse;

        if (!active || data.content === null || data.contentHash === null) {
          return;
        }

        timer = window.setTimeout(() => {
          if (!active) {
            return;
          }

          setPayload(data);

          void fetch("/api/popup-ad", {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({ contentHash: data.contentHash }),
          });
        }, SHOW_DELAY_MS);
      } catch {
        // No ad when the request fails.
      }
    }

    void loadAd();

    return () => {
      active = false;

      if (timer !== undefined) {
        window.clearTimeout(timer);
      }
    };
  }, []);

  if (payload === null || payload.content === null || closed) {
    return null;
  }

  return (
    <div className="popup-ad-modal" role="dialog" aria-label="Duyuru">
      <div
        className="popup-ad-overlay"
        onClick={() => setClosed(true)}
        aria-hidden="true"
      />
      <div className="popup-ad-content">
        <button
          type="button"
          className="popup-ad-close"
          onClick={() => setClosed(true)}
          aria-label="Reklamı kapat"
        >
          <i className="fas fa-times" />
        </button>
        <div
          className="popup-ad-body"
          dangerouslySetInnerHTML={{ __html: payload.content }}
        />
      </div>
    </div>
  );
}
