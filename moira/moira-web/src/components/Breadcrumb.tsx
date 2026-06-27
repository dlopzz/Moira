import Link from 'next/link';

type Crumb = { name: string; href?: string };

export default function Breadcrumb({ crumbs }: { crumbs: Crumb[] }) {
  return (
    <nav id="base-breadcrumbs" aria-label="Breadcrumbs" className="base-breadcrumbs">
      <div className="base-breadcrumb-container">
        {crumbs.map((crumb, i) => (
          <span key={i}>
            {i > 0 && <span className="bc-delimiter">/</span>}
            {crumb.href && i < crumbs.length - 1 ? (
              <Link href={crumb.href}><span>{crumb.name}</span></Link>
            ) : (
              <span className="base-bread-current">{crumb.name}</span>
            )}
          </span>
        ))}
      </div>
    </nav>
  );
}
