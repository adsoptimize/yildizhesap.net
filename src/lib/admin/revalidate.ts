import { revalidatePath } from "next/cache";

const ALWAYS_REVALIDATED = ["/", "/tum-hesaplar", "/hizmetler", "/sitemap.xml"];

/** Storefront pages are ISR-cached, so admin writes must flush them. */
export function revalidateStorefront(slugs: readonly (string | null)[] = []): void {
  for (const path of ALWAYS_REVALIDATED) {
    revalidatePath(path);
  }

  for (const slug of slugs) {
    if (slug !== null && slug !== "") {
      revalidatePath(`/${slug}`);
    }
  }
}
