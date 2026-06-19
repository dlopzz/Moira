'use client';

import { useEffect, useState, useCallback } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { api, type Category, type Product, type PaginationMeta, type ProductFilters } from '@/lib/api';
import Header from '@/components/Header';
import Breadcrumb from '@/components/Breadcrumb';
import ProductCard from '@/components/ProductCard';

const SORT_OPTIONS = [
  { value: 'name', label: 'Nombre A-Z' },
  { value: 'price_asc', label: 'Precio: menor a mayor' },
  { value: 'price_desc', label: 'Precio: mayor a menor' },
  { value: 'newest', label: 'Más recientes' },
];

export default function CategoryPageClient({ slug }: { slug: string }) {
  const router = useRouter();
  const searchParams = useSearchParams();

  const [category, setCategory] = useState<Category | null>(null);
  const [breadcrumb, setBreadcrumb] = useState<{ name: string; slug: string }[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [loading, setLoading] = useState(true);

  const sort = (searchParams.get('sort') ?? 'name') as ProductFilters['sort'];
  const minPrice = searchParams.get('min_price') ? Number(searchParams.get('min_price')) : undefined;
  const maxPrice = searchParams.get('max_price') ? Number(searchParams.get('max_price')) : undefined;
  const page = Number(searchParams.get('page') ?? 1);

  const [minInput, setMinInput] = useState(searchParams.get('min_price') ?? '');
  const [maxInput, setMaxInput] = useState(searchParams.get('max_price') ?? '');

  const updateParams = useCallback(
    (updates: Record<string, string | null>) => {
      const params = new URLSearchParams(searchParams.toString());
      for (const [k, v] of Object.entries(updates)) {
        if (v === null || v === '') {
          params.delete(k);
        } else {
          params.set(k, v);
        }
      }
      params.delete('page');
      router.push(`/categories/${slug}?${params.toString()}`);
    },
    [router, searchParams, slug],
  );

  useEffect(() => {
    api
      .getCategory(slug)
      .then((res) => {
        setCategory(res.data);
        setBreadcrumb(res.breadcrumb);
      })
      .catch(() => {});
  }, [slug]);

  useEffect(() => {
    setLoading(true);
    api
      .getProducts({ category: slug, sort, min_price: minPrice, max_price: maxPrice, page, per_page: 12 })
      .then((res) => {
        setProducts(res.data);
        setMeta(res.meta);
      })
      .finally(() => setLoading(false));
  }, [slug, sort, minPrice, maxPrice, page]);

  function applyPriceFilter() {
    updateParams({
      min_price: minInput || null,
      max_price: maxInput || null,
    });
  }

  function clearPriceFilter() {
    setMinInput('');
    setMaxInput('');
    updateParams({ min_price: null, max_price: null });
  }

  const crumbs = breadcrumb.map((b) => ({ name: b.name, href: `/categories/${b.slug}` }));

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <main className="max-w-6xl mx-auto px-4 py-8">
        <Breadcrumb crumbs={crumbs} />

        <div className="flex items-baseline gap-3 mb-6">
          <h1 className="text-2xl font-bold text-gray-900">{category?.name ?? slug}</h1>
          {meta && (
            <span className="text-sm text-gray-400">{meta.total} productos</span>
          )}
        </div>

        <div className="flex gap-6">
          {/* Sidebar filters */}
          <aside className="hidden md:block w-56 flex-none space-y-6">
            <div>
              <h3 className="text-sm font-semibold text-gray-900 mb-2">Ordenar por</h3>
              <div className="space-y-1">
                {SORT_OPTIONS.map((opt) => (
                  <button
                    key={opt.value}
                    onClick={() => updateParams({ sort: opt.value })}
                    className={`block w-full text-left text-sm px-2 py-1.5 rounded transition-colors ${
                      sort === opt.value
                        ? 'bg-blue-50 text-blue-700 font-medium'
                        : 'text-gray-600 hover:bg-gray-100'
                    }`}
                  >
                    {opt.label}
                  </button>
                ))}
              </div>
            </div>

            <div>
              <h3 className="text-sm font-semibold text-gray-900 mb-2">Precio</h3>
              <div className="space-y-2">
                <input
                  type="number"
                  placeholder="Mínimo"
                  value={minInput}
                  onChange={(e) => setMinInput(e.target.value)}
                  className="w-full border border-gray-300 rounded px-2 py-1.5 text-sm"
                  min={0}
                />
                <input
                  type="number"
                  placeholder="Máximo"
                  value={maxInput}
                  onChange={(e) => setMaxInput(e.target.value)}
                  className="w-full border border-gray-300 rounded px-2 py-1.5 text-sm"
                  min={0}
                />
                <button
                  onClick={applyPriceFilter}
                  className="w-full bg-blue-600 text-white text-sm py-1.5 rounded hover:bg-blue-700 transition-colors"
                >
                  Aplicar
                </button>
                {(minPrice || maxPrice) && (
                  <button
                    onClick={clearPriceFilter}
                    className="w-full text-sm text-gray-500 hover:text-gray-800 underline"
                  >
                    Quitar filtro
                  </button>
                )}
              </div>
            </div>
          </aside>

          {/* Products grid */}
          <div className="flex-1 min-w-0">
            {/* Mobile sort */}
            <div className="md:hidden mb-4">
              <select
                value={sort}
                onChange={(e) => updateParams({ sort: e.target.value })}
                className="border border-gray-300 rounded px-3 py-2 text-sm w-full"
              >
                {SORT_OPTIONS.map((opt) => (
                  <option key={opt.value} value={opt.value}>{opt.label}</option>
                ))}
              </select>
            </div>

            {loading ? (
              <div className="text-center text-gray-400 py-16">Cargando productos...</div>
            ) : products.length === 0 ? (
              <div className="text-center text-gray-400 py-16">No se encontraron productos.</div>
            ) : (
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
                {products.map((p) => (
                  <ProductCard key={p.id} product={p} />
                ))}
              </div>
            )}

            {/* Pagination */}
            {meta && meta.last_page > 1 && (
              <div className="flex justify-center gap-2 mt-8">
                {Array.from({ length: meta.last_page }, (_, i) => i + 1).map((p) => (
                  <button
                    key={p}
                    onClick={() => {
                      const params = new URLSearchParams(searchParams.toString());
                      params.set('page', String(p));
                      router.push(`/categories/${slug}?${params.toString()}`);
                    }}
                    className={`w-9 h-9 rounded text-sm font-medium transition-colors ${
                      page === p
                        ? 'bg-blue-600 text-white'
                        : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                    }`}
                  >
                    {p}
                  </button>
                ))}
              </div>
            )}
          </div>
        </div>
      </main>
    </div>
  );
}
