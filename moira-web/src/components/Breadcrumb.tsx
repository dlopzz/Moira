import Link from 'next/link';

type Crumb = { name: string; href?: string };

export default function Breadcrumb({ crumbs }: { crumbs: Crumb[] }) {
  return (
    <nav className="text-sm text-gray-500 mb-4">
      <ol className="flex flex-wrap items-center gap-1">
        <li>
          <Link href="/" className="hover:text-gray-800">Inicio</Link>
        </li>
        {crumbs.map((crumb, i) => (
          <li key={i} className="flex items-center gap-1">
            <span>/</span>
            {crumb.href && i < crumbs.length - 1 ? (
              <Link href={crumb.href} className="hover:text-gray-800">{crumb.name}</Link>
            ) : (
              <span className="text-gray-800 font-medium">{crumb.name}</span>
            )}
          </li>
        ))}
      </ol>
    </nav>
  );
}
