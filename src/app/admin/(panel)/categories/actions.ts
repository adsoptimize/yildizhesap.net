"use server";

import { requireAdmin } from "@/lib/auth/admin";
import { revalidateStorefront } from "@/lib/admin/revalidate";
import { prisma } from "@/lib/prisma";
import { slugify } from "@/lib/seo/slugify";

export type CategoryFormState = {
  error: string | null;
  success: string | null;
};

function readCategoryInput(formData: FormData) {
  const name = String(formData.get("name") ?? "").trim();
  const rawSeoSlug = String(formData.get("seoSlug") ?? "").trim();
  const icon = String(formData.get("icon") ?? "").trim();
  const description = String(formData.get("description") ?? "").trim();
  const isActive = formData.get("isActive") === "on";

  return {
    name,
    seoSlug: rawSeoSlug === "" ? slugify(name) : slugify(rawSeoSlug),
    icon: icon === "" ? "fas fa-folder" : icon,
    description: description === "" ? null : description,
    isActive,
  };
}

export async function saveCategoryAction(
  _previousState: CategoryFormState,
  formData: FormData,
): Promise<CategoryFormState> {
  await requireAdmin();

  const rawId = String(formData.get("id") ?? "").trim();
  const input = readCategoryInput(formData);

  if (input.name === "") {
    return { error: "Kategori adı zorunludur.", success: null };
  }

  if (input.seoSlug === "") {
    return { error: "SEO slug üretilemedi, elle girin.", success: null };
  }

  const conflict = await prisma.category.findFirst({
    where: {
      OR: [{ seoSlug: input.seoSlug }, { slug: input.seoSlug }],
      ...(rawId === "" ? {} : { id: { not: Number(rawId) } }),
    },
    select: { id: true },
  });

  if (conflict !== null) {
    return { error: `"${input.seoSlug}" başka bir kategoride kullanılıyor.`, success: null };
  }

  if (rawId === "") {
    await prisma.category.create({
      data: {
        name: input.name,
        slug: input.seoSlug,
        seoSlug: input.seoSlug,
        icon: input.icon,
        description: input.description,
        isActive: input.isActive,
      },
    });
    revalidateStorefront([input.seoSlug]);
    return { error: null, success: "Kategori eklendi." };
  }

  const id = Number(rawId);
  const existing = await prisma.category.findUnique({
    where: { id },
    select: { seoSlug: true },
  });

  await prisma.category.update({
    where: { id },
    data: {
      name: input.name,
      seoSlug: input.seoSlug,
      icon: input.icon,
      description: input.description,
      isActive: input.isActive,
    },
  });

  revalidateStorefront([input.seoSlug, existing?.seoSlug ?? null]);
  return { error: null, success: "Kategori güncellendi." };
}

export async function toggleCategoryAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const id = Number(formData.get("id"));
  const category = await prisma.category.findUnique({
    where: { id },
    select: { isActive: true, seoSlug: true },
  });

  if (category === null) {
    return;
  }

  await prisma.category.update({
    where: { id },
    data: { isActive: !category.isActive },
  });

  revalidateStorefront([category.seoSlug]);
}

export async function deleteCategoryAction(formData: FormData): Promise<void> {
  await requireAdmin();

  const id = Number(formData.get("id"));
  const accountCount = await prisma.account.count({ where: { categoryId: id } });

  if (accountCount > 0) {
    return;
  }

  const category = await prisma.category.findUnique({
    where: { id },
    select: { seoSlug: true },
  });

  await prisma.category.delete({ where: { id } });
  revalidateStorefront([category?.seoSlug ?? null]);
}
