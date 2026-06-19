'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { api, type CmsPage, type SiteInfo } from '@/lib/api';

export default function Footer() {
  const [pages, setPages] = useState<CmsPage[]>([]);
  const [info, setInfo] = useState<SiteInfo | null>(null);

  useEffect(() => {
    api.getFooterPages().then((r) => setPages(r.data)).catch(() => {});
    api.getSiteSettings().then((r) => setInfo(r.data)).catch(() => {});
  }, []);

  const siteName = info?.name || 'Moira';
  const year = new Date().getFullYear();

  return (
    <footer className="bg-gray-900 text-gray-300 mt-auto">
      <div className="max-w-6xl mx-auto px-4 py-12">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-10">
          {/* Brand + contact */}
          <div className="space-y-3">
            <Link href="/" className="text-white font-bold text-xl tracking-tight">
              {siteName}
            </Link>
            {info?.address && (
              <p className="text-sm text-gray-400 leading-relaxed">
                {info.address}
                {info.zip_code && ` — CP ${info.zip_code}`}
              </p>
            )}
            <div className="space-y-1">
              {info?.phone && (
                <p className="text-sm text-gray-400">
                  <span className="text-gray-500 mr-1">Tel:</span>
                  <a href={`tel:${info.phone}`} className="hover:text-white transition-colors">
                    {info.phone}
                  </a>
                </p>
              )}
              {info?.email && (
                <p className="text-sm text-gray-400">
                  <span className="text-gray-500 mr-1">Email:</span>
                  <a href={`mailto:${info.email}`} className="hover:text-white transition-colors">
                    {info.email}
                  </a>
                </p>
              )}
            </div>
          </div>

          {/* CMS pages */}
          {pages.length > 0 && (
            <div>
              <h3 className="text-white text-sm font-semibold uppercase tracking-wider mb-4">
                Información
              </h3>
              <ul className="space-y-2">
                {pages.map((page) => (
                  <li key={page.id}>
                    <Link
                      href={`/pages/${page.slug}`}
                      className="text-sm text-gray-400 hover:text-white transition-colors"
                    >
                      {page.title}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* Quick links */}
          <div>
            <h3 className="text-white text-sm font-semibold uppercase tracking-wider mb-4">
              Mi cuenta
            </h3>
            <ul className="space-y-2">
              <li>
                <Link href="/profile" className="text-sm text-gray-400 hover:text-white transition-colors">
                  Mi perfil
                </Link>
              </li>
              <li>
                <Link href="/profile/orders" className="text-sm text-gray-400 hover:text-white transition-colors">
                  Mis pedidos
                </Link>
              </li>
              <li>
                <Link href="/profile/wishlist" className="text-sm text-gray-400 hover:text-white transition-colors">
                  Wishlist
                </Link>
              </li>
              <li>
                <Link href="/cart" className="text-sm text-gray-400 hover:text-white transition-colors">
                  Carrito
                </Link>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div className="border-t border-gray-800">
        <div className="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between text-xs text-gray-600">
          <span>© {year} {siteName}. Todos los derechos reservados.</span>
        </div>
      </div>
    </footer>
  );
}
