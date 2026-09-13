import { notFound } from "next/navigation";
import { requireCustomer } from "@/lib/auth/customer";
import { prisma } from "@/lib/prisma";
import { PasswordForm, ProfileForm } from "./ProfileForms";

export default async function CustomerSettingsPage() {
  const customer = await requireCustomer();

  const profile = await prisma.user.findUnique({
    where: { id: customer.id },
    select: {
      username: true,
      email: true,
      firstName: true,
      lastName: true,
      phone: true,
    },
  });

  if (profile === null) {
    notFound();
  }

  return (
    <>
      <div className="shop-panel">
        <h2 className="shop-panel-title">
          <i className="fas fa-user-pen" /> Profil Bilgileri
        </h2>
        <ProfileForm
          firstName={profile.firstName}
          lastName={profile.lastName}
          phone={profile.phone ?? ""}
          email={profile.email}
          username={profile.username}
        />
      </div>

      <div className="shop-panel">
        <h2 className="shop-panel-title">
          <i className="fas fa-lock" /> Şifre Değiştir
        </h2>
        <PasswordForm />
      </div>
    </>
  );
}
