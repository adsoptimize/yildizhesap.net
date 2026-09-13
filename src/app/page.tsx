import type { Metadata } from "next";
import Link from "next/link";
import { HOME_META } from "@/lib/seo/meta";
import { CATEGORY_SEO_SLUGS } from "@/lib/seo/slugs";

export const metadata: Metadata = {
  title: { absolute: HOME_META.title },
  description: HOME_META.description,
  other: {
    "ai-intent":
      "Facebook reklam hesabı satın al ile sosyal medya hesaplarını güvenli ve hızlı şekilde edinmek isteyen kullanıcıları bilgilendirme",
  },
};

export default function HomePage() {
  const categoryLinks = Object.values(CATEGORY_SEO_SLUGS);

  return (
    <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-8 px-6 py-16">
      <div className="space-y-3">
        <p className="text-sm font-medium tracking-wide text-zinc-500">
          YildizHesap
        </p>
        <h1 className="text-3xl font-semibold tracking-tight text-zinc-900">
          {HOME_META.title}
        </h1>
        <p className="text-base leading-relaxed text-zinc-600">
          {HOME_META.description}
        </p>
      </div>

      <section className="space-y-3" aria-labelledby="seo-routes">
        <h2 id="seo-routes" className="text-lg font-medium text-zinc-900">
          SEO URL iskeleti (Faz 1)
        </h2>
        <p className="text-sm text-zinc-600">
          Legacy PHP <code className="rounded bg-zinc-100 px-1">legacy/</code>{" "}
          altında arşivlendi. Neon Postgres + Prisma şeması hazır. Sayfa
          içerikleri sonraki fazlarda bire bir taşınacak.
        </p>
        <ul className="grid gap-2 text-sm">
          <li>
            <Link className="text-blue-700 underline" href="/tum-hesaplar">
              /tum-hesaplar
            </Link>
          </li>
          {categoryLinks.map((slug) => (
            <li key={slug}>
              <Link className="text-blue-700 underline" href={`/${slug}`}>
                /{slug}
              </Link>
            </li>
          ))}
        </ul>
      </section>
    </main>
  );
}
