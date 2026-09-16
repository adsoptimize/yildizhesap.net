import Link from "next/link";

/**
 * Visible breadcrumb trail. The BreadcrumbList JSON-LD already ships from
 * `structured-data.ts`, but Google increasingly cross-checks that markup
 * against on-page navigation before rendering the breadcrumb in SERPs — and
 * it gives visitors a way back up the category tree.
 *
 * Server component: no interactivity, so it stays out of the client bundle.
 */

export type Crumb = {
  label: string;
  /** Omit on the current page — the last crumb renders as plain text. */
  href?: string;
};

export function Breadcrumbs({ items }: { items: readonly Crumb[] }) {
  if (items.length === 0) return null;

  return (
    <nav className="breadcrumbs" aria-label="Sayfa yolu">
      <ol>
        <li>
          <Link href="/">
            <i className="fas fa-house" aria-hidden="true" />
            <span>Ana Sayfa</span>
          </Link>
        </li>
        {items.map((item, index) => {
          const isLast = index === items.length - 1;

          return (
            <li key={`${item.label}-${index}`}>
              <i
                className="fas fa-chevron-right breadcrumbs__sep"
                aria-hidden="true"
              />
              {item.href === undefined || isLast ? (
                <span aria-current={isLast ? "page" : undefined}>
                  {item.label}
                </span>
              ) : (
                <Link href={item.href}>{item.label}</Link>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
