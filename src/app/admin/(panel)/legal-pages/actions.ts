"use server";

import { revalidatePath } from "next/cache";
import { requireAdmin } from "@/lib/auth/admin";
import type { AdminFormState } from "@/lib/admin/form-state";
import { revalidateStorefront } from "@/lib/admin/revalidate";
import { prisma } from "@/lib/prisma";

/** page_type → public route, mirrors LEGAL_ROUTES on the storefront. */
const PUBLIC_ROUTE_BY_PAGE_TYPE: Readonly<Record<string, string>> = {
  kvvk: "kvkk",
  privacy: "gizlilik-politikasi",
  terms: "kullanim-kosullari",
  cookies: "cerez-politikasi",
  refund: "iade-politikasi",
  about: "hakkimizda",
};

export async function saveLegalPageAction(
  _previousState: AdminFormState,
  formData: FormData,
): Promise<AdminFormState> {
  await requireAdmin();

  const pageType = String(formData.get("pageType") ?? "").trim();
  const title = String(formData.get("title") ?? "").trim();
  const content = String(formData.get("content") ?? "").trim();
  const metaDescription = String(formData.get("metaDescription") ?? "").trim();
  const isActive = formData.get("isActive") === "on";

  if (pageType === "" || title === "" || content === "") {
    return { error: "Başlık ve içerik zorunludur.", success: null };
  }

  await prisma.legalPage.update({
    where: { pageType },
    data: {
      title,
      content,
      metaDescription: metaDescription === "" ? null : metaDescription,
      isActive,
    },
  });

  const publicRoute = PUBLIC_ROUTE_BY_PAGE_TYPE[pageType];
  revalidateStorefront(publicRoute === undefined ? [] : [publicRoute]);
  revalidatePath("/admin/legal-pages");

  return { error: null, success: `${title} güncellendi.` };
}
