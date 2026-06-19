'use client';

import { useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import Header from '@/components/Header';
import ProductCard from '@/components/ProductCard';
import { api, type Product, type PaginationMeta, type ProductFilters } from '@/lib/api';

const SORT_OPTIONS = [
  { value: '', label: 'Relevancia' },
  { value: 'price_asc', label: 'Precio: menor a mayor' },
  { value: 'price_desc', label: 'Precio: mayor a menor' },
  { value: 'newest', label: 'Más nuevos' },
  { value: 'name', label: 'Nombre A-Z' },
];

export default function SearchPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const q = searchParams.get('q') ?? '';
  const sort = (searchParams.get('sort') ?? '') as string;
  const minPrice = searchParams.get('min_price') ?? '';
  const maxPrice = searchParams.get('max_price') ?? '';
  const page = parseInt(searchParams.get('page') ?? '1', 10);

  const [products, setProducts] = useState<Product[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [loading, setLoading] = useState(false);
  const [minInput, setMinInput] = useState(minPrice);
  const [maxInput, setMaxInput] = useState(maxPrice);

  const VALID_SORTS = ['price_asc', 'price_desc', 'newest', 'name'] as const;
  type ValidSort = (typeof VALID_SORTS)[number];
  const validSort: ValidSort | undefined = VALID_SORTS.includes(sort as ValidSort) ? (sort as ValidSort) : undefined;

  useEffect(() => {
    if (!q) return;
    setLoading(true);
    const filters: ProductFilters = {
      q,
      sort: validSort,
      min_price: minPrice ? parseFloat(minPrice) : undefined,
      max_price: maxPrice ? parseFloat(maxPrice) : undefined,
      page,
      per_page: 12,
    };
    api
      .getProducts(filters)
      .then((res) => {
        setProducts(res.data);
        setMeta(res.meta);
      })
      .finally(() => setLoading(false));
  }, [q, sort, minPrice, maxPrice, page]); // eslint-disable-line react-hooks/exhaustive-deps

  function pushParam(key: string, value: string) {
    const params = new URLSearchParams(searchParams.toString());
    if (value) params.set(key, value);
    else params.delete(key);
    params.delete('page');
    router.push(`/search?${params.toString()}`);
  }

  function applyPriceFilter() {
    const params = new URLSearchParams(searchParams.toString());
    if (minInput) params.set('min_price', minInput);
    else params.delete('min_price');
    if (maxInput) params.set('max_price', maxInput);
    else params.delete('max_price');
    params.delete('page');
    router.push(`/search?${params.toString()}`);
  }

  function setPage(p: number) {
    const params = new URLSearchParams(searchParams.toString());
    params.set('page', String(p));
    router.push(`/search?${params.toString()}`);
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <main className="max-w-6xl mx-auto px-4 py-8">
        <div className="mb-6">
          <p className="text-sm text-gray-500">
            {q ? (
              <>Resultados para <span className="font-semibold text-gray-900">&quot;{q}&quot;</span>{meta ? ` — ${meta.total} producto${meta.total !== 1 ? 's' : ''}` : ''}</>
            ) : (
              'Ingresá un término de búsqueda'
            )}
          </p>
        </div>

        {!q ? null : (
          <div className="flex gap-6">
            {/* Filters sidebar */}
            <aside className="hidden md:block w-56 shrink-0">
              <div className="bg-white border border-gray-200 rounded-xl p-4 space-y-5">
                <div>
                  <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Ordenar</p>
                  <div className="space-y-1">
                    {SORT_OPTIONS.map((opt) => (
                      <button
                        key={opt.value}
                        onClick={() => pushParam('sort', opt.value)}
                        className={`block w-full text-left px-2 py-1.5 text-sm rounded-md transition-colors ${
                          sort === opt.value ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700 hover:bg-gray-50'
                        }`}
                      >
                        {opt.label}
                      </button>
                    ))}
                  </div>
                </div>

                <div>
                  <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Precio</p>
                  <div className="space-y-2">
                    <input
                      type="number"
                      placeholder="Mínimo"
                      value={minInput}
                      onChange={(e) => setMinInput(e.target.value)}
                      className="w-full text-sm border border-gray-200 rounded-md px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <input
                      type="number"
                      placeholder="Máximo"
                      value={maxInput}
                      onChange={(e) => setMaxInput(e.target.value)}
                      className="w-full text-sm border border-gray-200 rounded-md px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <button
                      onClick={applyPriceFilter}
                      className="w-full bg-blue-600 text-white text-sm py-1.5 rounded-md hover:bg-blue-700 transition-colors"
                    >
                      Aplicar
                    </button>
                    {(minPrice || maxPrice) && (
                      <button
                        onClick={() => {
                          setMinInput('');
                          setMaxInput('');
                          const params = new URLSearchParams(searchParams.toString());
                          params.delete('min_price');
                          params.delete('max_price');
                          params.delete('page');
                          router.push(`/search?${params.toString()}`);
                        }}
                        className="w-full text-xs text-gray-400 hover:text-gray-600"
                      >
                        Limpiar filtro
                      </button>
                    )}
                  </div>
                </div>
              </div>
            </aside>

            {/* Results */}
            <div className="flex-1 min-w-0">
              {loading ? (
                <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                  {Array.from({ length: 6 }).map((_, i) => (
                    <div key={i} className="bg-white border border-gray-200 rounded-lg overflow-hidden animate-pulse">
                      <div className="aspect-square bg-gray-100" />
                      <div className="p-3 space-y-2">
                        <div className="h-3 bg-gray-100 rounded w-1/2" />
                        <div className="h-4 bg-gray-100 rounded w-3/4" />
                        <div className="h-5 bg-gray-100 rounded w-1/3" />
                      </div>
                    </div>
                  ))}
                </div>
              ) : products.length === 0 ? (
                <div className="text-center py-16">
                  <p className="text-gray-400 text-lg font-medium">Sin resultados</p>
                  <p className="text-gray-300 text-sm mt-1">Probá con otro término o quitá los filtros</p>
                  <Link href="/" className="mt-4 inline-block text-blue-600 hover:underline text-sm">
                    Volver al inicio
                  </Link>
                </div>
              ) : (
                <>
                  <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                    {products.map((p) => (
                      <ProductCard key={p.id} product={p} />
                    ))}
                  </div>

                  {meta && meta.last_page > 1 && (
                    <div className="flex justify-center items-center gap-2 mt-8">
                      <button
                        onClick={() => setPage(page - 1)}
                        disabled={page === 1}
                        className="px-3 py-1.5 text-sm border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50"
                      >
                        Anterior
                      </button>
                      <span className="text-sm text-gray-600">
                        Página {meta.current_page} de {meta.last_page}
                      </span>
                      <button
                        onClick={() => setPage(page + 1)}
                        disabled={page === meta.last_page}
                        className="px-3 py-1.5 text-sm border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50"
                      >
                        Siguiente
                      </button>
                    </div>
                  )}
                </>
              )}
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
