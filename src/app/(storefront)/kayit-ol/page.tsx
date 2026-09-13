import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { getCurrentCustomer } from "@/lib/auth/customer";
import { ACCOUNT_PATH } from "@/lib/shop/constants";
import { STATIC_PAGE_META } from "@/lib/seo/meta";
import { CustomerRegisterForm } from "./RegisterForm";

const PAGE_META = STATIC_PAGE_META["kayit-ol"];

export const metadata: Metadata = {
  title: { absolute: PAGE_META.title },
  description: PAGE_META.description,
  alternates: { canonical: "/kayit-ol" },
  robots: { index: false, follow: true },
};

export default async function CustomerRegisterPage() {
  const customer = await getCurrentCustomer();

  if (customer !== null) {
    redirect(ACCOUNT_PATH);
  }

  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>Kayıt Ol</h1>
          <p>
            Ücretsiz hesap oluşturun; siparişleriniz ve hesap bilgileriniz
            panelinizde saklanır.
          </p>
        </div>
      </section>

      <section className="shop-section">
        <div className="container">
          <div className="shop-panel shop-panel-narrow">
            <CustomerRegisterForm />
          </div>
        </div>
      </section>
    </main>
  );
}
