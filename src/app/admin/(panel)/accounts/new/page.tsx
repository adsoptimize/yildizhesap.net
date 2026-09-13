import Link from "next/link";
import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { requireAdmin } from "@/lib/auth/admin";
import { prisma } from "@/lib/prisma";
import { AccountForm } from "../AccountForm";

export default async function NewAccountPage() {
  const admin = await requireAdmin();

  const categories = await prisma.category.findMany({
    orderBy: { name: "asc" },
    select: { id: true, name: true },
  });

  return (
    <>
      <AdminPageHeader
        title="Yeni Ürün"
        subtitle="Kaydettikten sonra özellik ve stok girişi yapabilirsin"
        userName={`${admin.firstName} ${admin.lastName}`}
        actions={
          <Link href="/admin/accounts" className="admin-btn secondary">
            <i className="fas fa-arrow-left" /> Listeye dön
          </Link>
        }
      />

      <div className="content-card">
        <div className="card-body">
          <AccountForm categories={categories} />
        </div>
      </div>
    </>
  );
}
