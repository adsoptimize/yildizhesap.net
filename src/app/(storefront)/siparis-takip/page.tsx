import type { Metadata } from "next";
import { STATIC_PAGE_META } from "@/lib/seo/meta";
import { STATIC_PAGE_COPY } from "@/lib/seo/page-copy";
import { OrderTrackForm } from "./OrderTrackForm";

const PAGE_META = STATIC_PAGE_META["siparis-takip"];
const PAGE_COPY = STATIC_PAGE_COPY["siparis-takip"];

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

      {PAGE_COPY === undefined ? null : (
        <section className="shop-section" style={{ paddingTop: 0 }}>
          <div className="container">
            <div className="category-copy">
              <h2>{PAGE_COPY.heading}</h2>
              {PAGE_COPY.paragraphs.map((paragraph) => (
                <p key={paragraph}>{paragraph}</p>
              ))}
            </div>
          </div>
        </section>
      )}
    </main>
  );
}
