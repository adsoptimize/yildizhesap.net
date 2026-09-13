import type { Metadata } from "next";
import { requireCustomer } from "@/lib/auth/customer";
import { AccountNav } from "./AccountNav";
import { logoutCustomerAction } from "./actions";

export const metadata: Metadata = {
  title: { absolute: "Hesabım | yildizhesap.net" },
  robots: { index: false, follow: false },
};

export default async function AccountLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  const customer = await requireCustomer();

  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>Hesabım</h1>
          <p>
            {customer.firstName} {customer.lastName} · {customer.email}
          </p>
        </div>
      </section>

      <section className="shop-section">
        <div className="container">
          <div
            style={{
              alignItems: "center",
              display: "flex",
              flexWrap: "wrap",
              gap: 12,
              justifyContent: "space-between",
            }}
          >
            <AccountNav />
            <form action={logoutCustomerAction}>
              <button type="submit" className="shop-btn danger">
                <i className="fas fa-right-from-bracket" /> Çıkış Yap
              </button>
            </form>
          </div>
          {children}
        </div>
      </section>
    </main>
  );
}
