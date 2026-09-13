import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { formatCurrency } from "@/lib/admin/format";
import { buildShopierForm } from "@/lib/payments/shopier";
import { getShopierCredentials } from "@/lib/payments/settings";
import { prisma } from "@/lib/prisma";
import { ShopierRedirectForm } from "./ShopierRedirectForm";

export const metadata: Metadata = {
  title: { absolute: "Ödemeye yönlendiriliyorsunuz" },
  robots: { index: false, follow: false },
};

type PageProps = {
  params: Promise<{ code: string }>;
};

/** Splits a full name the way Shopier expects separate name/surname fields. */
function splitName(fullName: string): { firstName: string; lastName: string } {
  const parts = fullName.split(/\s+/).filter((part) => part !== "");

  if (parts.length <= 1) {
    return { firstName: parts[0] ?? "Müşteri", lastName: "-" };
  }

  return {
    firstName: parts.slice(0, -1).join(" "),
    lastName: parts[parts.length - 1] ?? "-",
  };
}

export default async function ShopierPaymentPage({ params }: PageProps) {
  const { code } = await params;

  const payment = await prisma.cryptoPayment.findUnique({
    where: { orderCode: code },
    select: {
      amount: true,
      status: true,
      email: true,
      customerName: true,
      phone: true,
      order: { select: { productName: true } },
    },
  });

  if (payment === null || payment.status !== "pending") {
    notFound();
  }

  const credentials = await getShopierCredentials();

  if (credentials === null) {
    notFound();
  }

  const { firstName, lastName } = splitName(payment.customerName ?? "");
  const form = buildShopierForm(credentials, {
    orderCode: code,
    amount: payment.amount.toString(),
    productName: payment.order.productName,
    buyer: {
      firstName,
      lastName,
      email: payment.email ?? "",
      phone: payment.phone ?? "",
    },
  });

  return (
    <main>
      <section className="shop-section">
        <div className="container">
          <div className="shop-panel shop-panel-narrow">
            <h1 className="shop-panel-title">
              <i className="fas fa-credit-card" /> Güvenli ödemeye
              yönlendiriliyorsunuz
            </h1>
            <p className="shop-muted">
              Sipariş kodu: <strong>{code}</strong>
            </p>
            <p className="shop-muted" style={{ marginBottom: 20 }}>
              Tutar: <strong>{formatCurrency(payment.amount.toString())}</strong>
            </p>
            <ShopierRedirectForm actionUrl={form.actionUrl} fields={form.fields} />
            <p className="shop-hint" style={{ marginTop: 16 }}>
              Sayfa açılmazsa yukarıdaki butona dokunun.
            </p>
          </div>
        </div>
      </section>
    </main>
  );
}
