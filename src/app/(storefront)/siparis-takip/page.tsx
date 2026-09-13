import type { Metadata } from "next";
import { STATIC_PAGE_META } from "@/lib/seo/meta";
import { OrderTrackForm } from "./OrderTrackForm";

const PAGE_META = STATIC_PAGE_META["siparis-takip"];

export const metadata: Metadata = {
  title: { absolute: PAGE_META.title },
  description: PAGE_META.description,
  alternates: { canonical: "/siparis-takip" },
};

export default function OrderTrackPage() {
  return (
    <main>
      <section className="page-header compact">
        <div className="container">
          <h1>{PAGE_META.title}</h1>
          <p>{PAGE_META.description}</p>
        </div>
      </section>

      <section className="shop-section">
        <div className="container">
          <OrderTrackForm />
        </div>
      </section>
    </main>
  );
}
