import { AdminPageHeader } from "@/components/admin/AdminPageHeader";
import { StatefulForm } from "@/components/admin/StatefulForm";
import { requireAdmin } from "@/lib/auth/admin";
import { prisma } from "@/lib/prisma";
import { saveSiteSettingsAction } from "./actions";

const CATEGORY_LABELS: Readonly<Record<string, string>> = {
  header: "Header & Logo",
  hero: "Anasayfa Hero",
  footer: "Footer",
  general: "Genel & İletişim",
  theme: "Tema",
  security: "Güvenlik & Limitler",
  ads: "Reklam",
};

const TEXTAREA_TYPES = new Set(["textarea", "json"]);

export default async function AdminSiteSettingsPage() {
  const admin = await requireAdmin();

  const settings = await prisma.siteSetting.findMany({
    orderBy: [{ category: "asc" }, { orderIndex: "asc" }, { settingKey: "asc" }],
    select: {
      settingKey: true,
      settingValue: true,
      settingType: true,
      description: true,
      category: true,
    },
  });

  const grouped = new Map<string, typeof settings>();

  for (const setting of settings) {
    const bucket = grouped.get(setting.category) ?? [];
    bucket.push(setting);
    grouped.set(setting.category, bucket);
  }

  return (
    <>
      <AdminPageHeader
        title="Site Ayarları"
        subtitle="Header, hero, footer ve iletişim içerikleri buradan yönetilir"
        userName={`${admin.firstName} ${admin.lastName}`}
      />

      <StatefulForm action={saveSiteSettingsAction} submitLabel="Ayarları Kaydet">
        {[...grouped.entries()].map(([category, items]) => (
          <div className="content-card" key={category}>
            <div className="card-header">
              <h2 className="card-title">{CATEGORY_LABELS[category] ?? category}</h2>
            </div>
            <div className="card-body">
              <div className={items.length > 1 ? "form-grid" : "admin-form"}>
                {items.map((setting) => {
                  const fieldName = `setting__${setting.settingKey}`;
                  const value = setting.settingValue ?? "";

                  if (setting.settingType === "boolean") {
                    return (
                      <div className="form-field" key={setting.settingKey}>
                        <label htmlFor={fieldName}>
                          {setting.description ?? setting.settingKey}
                        </label>
                        <input type="hidden" name="boolean_key" value={setting.settingKey} />
                        <label className="checkbox-field">
                          <input
                            id={fieldName}
                            name={fieldName}
                            type="checkbox"
                            value="1"
                            defaultChecked={value === "1"}
                          />
                          Aktif
                        </label>
                      </div>
                    );
                  }

                  return (
                    <div className="form-field" key={setting.settingKey}>
                      <label htmlFor={fieldName}>
                        {setting.description ?? setting.settingKey}
                      </label>
                      {TEXTAREA_TYPES.has(setting.settingType ?? "") ? (
                        <textarea id={fieldName} name={fieldName} defaultValue={value} />
                      ) : (
                        <input
                          id={fieldName}
                          name={fieldName}
                          type="text"
                          defaultValue={value}
                        />
                      )}
                      <span style={{ fontSize: "0.7rem", color: "#94a3b8" }}>
                        {setting.settingKey}
                      </span>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        ))}
      </StatefulForm>
    </>
  );
}
