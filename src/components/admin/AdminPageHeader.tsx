type AdminPageHeaderProps = {
  title: string;
  subtitle?: string;
  userName: string;
  actions?: React.ReactNode;
};

function initialsOf(name: string): string {
  const parts = name.trim().split(/\s+/);
  const letters = parts.slice(0, 2).map((part) => part.charAt(0).toUpperCase());
  return letters.join("") || "A";
}

export function AdminPageHeader({
  title,
  subtitle,
  userName,
  actions,
}: AdminPageHeaderProps) {
  return (
    <div className="main-header">
      <div>
        <h1 className="page-title">{title}</h1>
        {subtitle === undefined ? null : (
          <p className="page-subtitle">{subtitle}</p>
        )}
      </div>
      <div className="user-info">
        {actions}
        <div className="user-avatar">{initialsOf(userName)}</div>
      </div>
    </div>
  );
}
