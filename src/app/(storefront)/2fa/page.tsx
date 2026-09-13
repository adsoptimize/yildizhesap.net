import type { Metadata } from "next";
import { TotpGenerator } from "./TotpGenerator";

export const metadata: Metadata = {
  title: { absolute: "2FA OTP Kod Üretici | YildizHesap" },
  description:
    "Satın aldığınız 2 adımlı doğrulama korumalı hesapların secret key'i ile 6 haneli OTP kodunuzu üretin.",
  robots: { index: false, follow: true },
};

type PageProps = {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
};

export default async function TotpPage({ searchParams }: PageProps) {
  const params = await searchParams;
  const rawSecret = params.secret;
  const secret = Array.isArray(rawSecret)
    ? (rawSecret[0] ?? "")
    : (rawSecret ?? "");

  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>2FA OTP Kod Üretici</h1>
          <p>
            2 adımlı doğrulama aktif hesaplarınızda secret key ile OTP kodlarınızı
            üretin. Kod tarayıcınızda hesaplanır, secret key sunucuya gönderilmez.
          </p>
        </div>
      </section>

      <section className="shop-section">
        <div className="container">
          <div className="shop-panel shop-panel-narrow">
            <TotpGenerator initialSecret={secret} />
          </div>
        </div>
      </section>
    </main>
  );
}
