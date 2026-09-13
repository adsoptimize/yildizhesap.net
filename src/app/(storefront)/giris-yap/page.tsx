import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { getCurrentCustomer } from "@/lib/auth/customer";
import { ACCOUNT_PATH } from "@/lib/shop/constants";
import { STATIC_PAGE_META } from "@/lib/seo/meta";
import { CustomerLoginForm } from "./LoginForm";

const PAGE_META = STATIC_PAGE_META["giris-yap"];

export const metadata: Metadata = {
  title: { absolute: PAGE_META.title },
  description: PAGE_META.description,
  alternates: { canonical: "/giris-yap" },
  robots: { index: false, follow: true },
};

export default async function CustomerLoginPage() {
  const customer = await getCurrentCustomer();

  if (customer !== null) {
    redirect(ACCOUNT_PATH);
  }

  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>Giriş Yap</h1>
          <p>Siparişlerinizi ve satın aldığınız hesapları görüntülemek için giriş yapın.</p>
        </div>
      </section>

      <section className="shop-section">
        <div className="container">
          <div className="shop-panel shop-panel-narrow">
            <CustomerLoginForm />
          </div>
        </div>
      </section>
    </main>
  );
}
