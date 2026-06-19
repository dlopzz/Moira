'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import Header from '@/components/Header';
import { api, type CmsPage } from '@/lib/api';

export default function CmsPageClient({ slug }: { slug: string }) {
  const [page, setPage] = useState<CmsPage | null>(null);
  const [loading, setLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);

  useEffect(() => {
    api
      .getCmsPage(slug)
      .then((r) => setPage(r.data))
      .catch(() => setNotFound(true))
      .finally(() => setLoading(false));
  }, [slug]);

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <div className="max-w-3xl mx-auto px-4 py-12 text-gray-400 animate-pulse">Cargando...</div>
      </div>
    );
  }

  if (notFound || !page) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <div className="max-w-3xl mx-auto px-4 py-12">
          <p className="text-gray-500">Página no encontrada.</p>
          <Link href="/" className="text-blue-600 hover:underline text-sm mt-2 inline-block">Volver al inicio</Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <main className="max-w-3xl mx-auto px-4 py-10">
        <div className="mb-1">
          <Link href="/" className="text-sm text-gray-400 hover:text-gray-600">Inicio</Link>
          <span className="text-gray-300 mx-1">/</span>
          <span className="text-sm text-gray-600">{page.title}</span>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 px-8 py-10 mt-4">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">{page.title}</h1>
          {page.subtitle && <p className="text-gray-500 text-lg mb-6">{page.subtitle}</p>}
          {page.content && (
            <div className="cms-content text-gray-700 text-sm leading-relaxed" dangerouslySetInnerHTML={{ __html: page.content }} />
          )}
        </div>
      </main>
    </div>
  );
}
