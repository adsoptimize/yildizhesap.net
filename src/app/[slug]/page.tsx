import type { Metadata } from "next";
import { notFound } from "next/navigation";
import {
  ALL_ACCOUNTS_META,
  CATEGORY_META,
  PRODUCT_META_TITLES,
  STATIC_PAGE_META,
} from "@/lib/seo/meta";
import {
  CATEGORY_SEO_SLUGS,
  PRODUCT_SEO_SLUGS,
  STATIC_SEO_ROUTES,
} from "@/lib/seo/slugs";

type PageProps = {
  params: Promise<{ slug: string }>;
};

function resolveMeta(slug: string): { title: string; description: string } | null {
  if (slug === "tum-hesaplar") {
    return ALL_ACCOUNTS_META;
  }

  const staticMeta = STATIC_PAGE_META[slug];
  if (staticMeta) {
    return staticMeta;
  }

  if ((STATIC_SEO_ROUTES as readonly string[]).includes(slug)) {
    return {
      title: slug,
      description: "YildizHesap",
    };
  }

  const categoryId = Number(
    Object.entries(CATEGORY_SEO_SLUGS).find(([, value]) => value === slug)?.[0],
  );
  if (categoryId && CATEGORY_META[categoryId]) {
    return CATEGORY_META[categoryId];
  }

  const productId = Number(
    Object.entries(PRODUCT_SEO_SLUGS).find(([, value]) => value === slug)?.[0],
  );
  if (productId && PRODUCT_META_TITLES[productId]) {
    return {
      title: PRODUCT_META_TITLES[productId],
      description: `${PRODUCT_META_TITLES[productId]}. Güvenli alışveriş, hızlı teslimat.`,
    };
  }

  return null;
}

export async function generateStaticParams() {
  return [
    ...STATIC_SEO_ROUTES.map((slug) => ({ slug })),
    ...Object.values(CATEGORY_SEO_SLUGS).map((slug) => ({ slug })),
    ...Object.values(PRODUCT_SEO_SLUGS).map((slug) => ({ slug })),
  ];
}

export async function generateMetadata({
  params,
}: PageProps): Promise<Metadata> {
  const { slug } = await params;
  const meta = resolveMeta(slug);
  if (!meta) {
    return { title: "Sayfa Bulunamadı" };
  }

  return {
    title: { absolute: meta.title },
    description: meta.description,
    openGraph: {
      title: meta.title,
      description: meta.description,
      url: `/${slug}`,
    },
  };
}

export default async function SeoSlugPage({ params }: PageProps) {
  const { slug } = await params;
  const meta = resolveMeta(slug);
  if (!meta) {
    notFound();
  }

  return (
    <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-4 px-6 py-16">
      <p className="text-sm text-zinc-500">/{slug}</p>
      <h1 className="text-3xl font-semibold tracking-tight text-zinc-900">
        {meta.title}
      </h1>
      <p className="text-base text-zinc-600">{meta.description}</p>
      <p className="text-sm text-zinc-500">
        Placeholder — içerik ve admin özellikleri sonraki fazlarda legacy PHP’den
        bire bir aktarılacak. SEO title/description kilitli.
      </p>
    </main>
  );
}
