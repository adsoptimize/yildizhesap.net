import { redirect } from "next/navigation";
import { getSessionUser } from "@/lib/auth/session";
import { LoginForm } from "./LoginForm";

export default async function AdminLoginPage() {
  const user = await getSessionUser();

  if (user !== null && user.isAdmin) {
    redirect("/admin");
  }

  return (
    <div className="admin-login-wrapper">
      <div className="admin-login-card">
        <h1>Yönetim Paneli</h1>
        <p>Devam etmek için yönetici hesabınızla giriş yapın.</p>
        <LoginForm />
      </div>
    </div>
  );
}
