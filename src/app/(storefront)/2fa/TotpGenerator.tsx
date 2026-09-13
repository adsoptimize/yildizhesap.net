"use client";

import { useEffect, useState } from "react";
import { CopyButton } from "@/components/CopyButton";
import {
  generateTotp,
  secondsUntilNextCode,
  TOTP_PERIOD_SECONDS,
} from "@/lib/security/totp";

const TICK_MS = 1_000;
const EXAMPLE_SECRET = "JBSWY3DPEHPK3PXP";

type Codes = {
  current: string;
  next: string;
};

type TotpGeneratorProps = {
  initialSecret: string;
};

export function TotpGenerator({ initialSecret }: TotpGeneratorProps) {
  const [secret, setSecret] = useState(initialSecret);
  const [codes, setCodes] = useState<Codes | null>(null);
  const [secondsLeft, setSecondsLeft] = useState(TOTP_PERIOD_SECONDS);
  const [error, setError] = useState<string | null>(null);

  const hasSecret = secret.trim() !== "";

  useEffect(() => {
    if (!hasSecret) {
      return;
    }

    let active = true;

    async function refresh(): Promise<void> {
      try {
        const [current, next] = await Promise.all([
          generateTotp(secret),
          generateTotp(secret, TOTP_PERIOD_SECONDS),
        ]);

        if (active) {
          setCodes({ current, next });
          setSecondsLeft(secondsUntilNextCode());
          setError(null);
        }
      } catch {
        if (active) {
          setCodes(null);
          setError(
            "Secret key geçersiz. Yalnızca A-Z harfleri ve 2-7 rakamları kullanılabilir.",
          );
        }
      }
    }

    void refresh();
    const timer = window.setInterval(() => void refresh(), TICK_MS);

    return () => {
      active = false;
      window.clearInterval(timer);
    };
  }, [secret, hasSecret]);

  return (
    <div className="shop-form">
      <div className="shop-field">
        <label htmlFor="secret">Secret Key</label>
        <input
          id="secret"
          type="text"
          placeholder="Örnek: JBSWY3DPEHPK3PXP"
          value={secret}
          onChange={(event) => setSecret(event.target.value)}
          autoComplete="off"
          spellCheck={false}
        />
        <span className="shop-hint">
          Hesap bilgilerinizdeki 2FA anahtarını buraya yazın.{" "}
          <button
            type="button"
            className="shop-link"
            onClick={() => setSecret(EXAMPLE_SECRET)}
          >
            Örnek secret ile dene
          </button>
        </span>
      </div>

      {!hasSecret || error === null ? null : (
        <div className="shop-alert error">{error}</div>
      )}

      {!hasSecret || codes === null ? null : (
        <div className="shop-credentials">
          <div className="shop-totp">
            <strong>{codes.current}</strong>
            <CopyButton value={codes.current} label="Kodu kopyala" />
          </div>
          <p className="shop-hint">
            Yenilenmesine {secondsLeft} saniye · sonraki kod {codes.next}
          </p>
        </div>
      )}
    </div>
  );
}
