import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { getCurrentCustomer } from "@/lib/auth/customer";
import { ACCOUNT_PATH } from "@/lib/shop/constants";
import { STATIC_PAGE_META } from "@/lib/seo/meta";
import { ForgotPasswordForm } from "./ForgotPasswordForm";

const PAGE_META = STATIC_PAGE_META["sifremi-unuttum"];

export const metadata: Metadata = {
  title: { absolute: PAGE_META.title },
  description: PAGE_META.description,
  alternates: { canonical: "/sifremi-unuttum" },
  robots: { index: false, follow: false },
};

export default async function ForgotPasswordPage() {
  const customer = await getCurrentCustomer();

  if (customer !== null) {
    redirect(ACCOUNT_PATH);
  }

  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>Şifremi Unuttum</h1>
          <p>
            E-posta adresinizle şifrenizi sıfırlayın ve hesabınıza yeniden
            erişin.
          </p>
        </div>
      </section>

      <section className="shop-section">
        <div className="container">
          <div className="shop-panel shop-panel-narrow">
            <ForgotPasswordForm />
          </div>
        </div>
      </section>
    </main>
  );
}
