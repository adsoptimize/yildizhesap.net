import { AdminSidebar } from "@/components/admin/AdminSidebar";
import { requireAdmin } from "@/lib/auth/admin";
import { logoutAction } from "./actions";

export default async function AdminPanelLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  await requireAdmin();

  return (
    <div className="admin-container">
      <AdminSidebar logoutAction={logoutAction} />
      <div className="main-content">{children}</div>
    </div>
  );
}
