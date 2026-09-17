import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { getCurrentCustomer } from "@/lib/auth/customer";
import { ACCOUNT_PATH, FORGOT_PASSWORD_PATH } from "@/lib/shop/constants";
import { STATIC_PAGE_META } from "@/lib/seo/meta";
import { ResetPasswordForm } from "./ResetPasswordForm";

const PAGE_META = STATIC_PAGE_META["sifre-sifirla"];

export const metadata: Metadata = {
  title: { absolute: PAGE_META.title },
  description: PAGE_META.description,
  alternates: { canonical: "/sifre-sifirla" },
  robots: { index: false, follow: false },
};

type Props = {
  searchParams: Promise<{ token?: string }>;
};

export default async function ResetPasswordPage({ searchParams }: Props) {
  const customer = await getCurrentCustomer();

  if (customer !== null) {
    redirect(ACCOUNT_PATH);
  }

  const { token } = await searchParams;
  const trimmed = token?.trim() ?? "";

  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>Yeni Şifre Belirle</h1>
          <p>Hesabınız için yeni bir şifre oluşturun.</p>
        </div>
      </section>

      <section className="shop-section">
        <div className="container">
          <div className="shop-panel shop-panel-narrow">
            {trimmed === "" ? (
              <>
                <div className="shop-alert error">
                  Bu sayfaya e-postanızdaki bağlantı ile gelmeniz gerekiyor.
                  Bağlantı eksik veya bozuk görünüyor.
                </div>
                <p className="shop-hint">
                  <Link href={FORGOT_PASSWORD_PATH} className="shop-link">
                    Yeni bir sıfırlama bağlantısı isteyin
                  </Link>
                  .
                </p>
              </>
            ) : (
              <ResetPasswordForm token={trimmed} />
            )}
          </div>
        </div>
      </section>
    </main>
  );
}
