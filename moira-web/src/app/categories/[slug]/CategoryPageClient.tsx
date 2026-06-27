'use client';

import { useEffect, useState, useCallback } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { api, type Category, type Product, type PaginationMeta, type ProductFilters } from '@/lib/api';
import Header from '@/components/Header';
import Breadcrumb from '@/components/Breadcrumb';
import ProductCard from '@/components/ProductCard';

const SORT_OPTIONS = [
  { value: 'name', label: 'Nombre A-Z' },
  { value: 'name_desc', label: 'Nombre Z-A' },
  { value: 'price_asc', label: 'Precio: menor a mayor' },
  { value: 'price_desc', label: 'Precio: mayor a menor' },
  { value: 'newest', label: 'Más recientes' },
];

const COLOR_MAP: Record<string, string> = {
  negro: '#1a1a1a',
  blanco: '#ffffff',
  gris: '#9ca3af',
  rojo: '#dc2626',
  azul: '#2563eb',
  marrón: '#92400e',
  beige: '#d4b896',
  plateado: '#c0c0c0',
  dorado: '#d4a017',
  verde: '#16a34a',
  rosa: '#ec4899',
  naranja: '#ea580c',
  amarillo: '#eab308',
  violeta: '#7c3aed',
};

const ATTR_LABELS: Record<string, string> = {
  color: 'Color',
  talle: 'Talle',
  talla: 'Talla',
  talle_ropa: 'Talle',
};

function parseAttributesFromParams(sp: URLSearchParams): Record<string, string> {
  const attrs: Record<string, string> = {};
  sp.forEach((value, key) => {
    const match = key.match(/^attr\[(.+)\]$/);
    if (match) attrs[match[1]] = value;
  });
  return attrs;
}

export default function CategoryPageClient({ slug }: { slug: string }) {
  const router = useRouter();
  const searchParams = useSearchParams();

  const [category, setCategory] = useState<Category | null>(null);
  const [breadcrumb, setBreadcrumb] = useState<{ name: string; slug: string }[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [availableFilters, setAvailableFilters] = useState<Record<string, string[]>>({});
  const [view, setView] = useState<'grid' | 'list'>('grid');

  useEffect(() => {
    const saved = localStorage.getItem('product-view') as 'grid' | 'list' | null;
    if (saved === 'grid' || saved === 'list') setView(saved);
  }, []);

  function changeView(v: 'grid' | 'list') {
    setView(v);
    localStorage.setItem('product-view', v);
  }

  const sort = (searchParams.get('sort') ?? 'name') as ProductFilters['sort'];
  const minPrice = searchParams.get('min_price') ? Number(searchParams.get('min_price')) : undefined;
  const maxPrice = searchParams.get('max_price') ? Number(searchParams.get('max_price')) : undefined;
  const page = Number(searchParams.get('page') ?? 1);
  const activeAttributes = parseAttributesFromParams(searchParams);

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

  function toggleAttribute(key: string, value: string) {
    const paramKey = `attr[${key}]`;
    const current = searchParams.get(paramKey);
    updateParams({ [paramKey]: current === value ? null : value });
  }

  useEffect(() => {
    api
      .getCategory(slug)
      .then((res) => {
        setCategory(res.data);
        setBreadcrumb(res.breadcrumb);
      })
      .catch(() => {});

    api
      .getProductFilters({ category: slug })
      .then((res) => setAvailableFilters(res.data))
      .catch(() => {});
  }, [slug]);

  useEffect(() => {
    setLoading(true);
    api
      .getProducts({
        category: slug,
        sort,
        min_price: minPrice,
        max_price: maxPrice,
        page,
        per_page: 12,
        attributes: Object.keys(activeAttributes).length > 0 ? activeAttributes : undefined,
      })
      .then((res) => {
        setProducts(res.data);
        setMeta(res.meta);
      })
      .finally(() => setLoading(false));
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [slug, sort, minPrice, maxPrice, page, searchParams]);

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

  const crumbs = breadcrumb
    .filter((b) => b.slug !== 'moira' && b.name.toLowerCase() !== 'moira')
    .map((b) => ({ name: b.name, href: `/categories/${b.slug}` }));

  return (
    <div className="min-h-screen">
      <Header />

      <section role="banner" className="entry-hero product-archive-hero-section entry-hero-layout-standard">
        <div className="entry-hero-container-inner">
          <div className="hero-section-overlay"></div>
          <div className="hero-container site-container">
            <header className="entry-header product-archive-title title-align-inherit title-tablet-align-inherit title-mobile-align-inherit">
              <Breadcrumb crumbs={crumbs} />
              <h1 className="page-title archive-title">{category?.name ?? slug}</h1>
            </header>
          </div>
        </div>
      </section>

      <div id="primary" className="woocommerce content-area has-sidebar has-left-sidebar">
        <div className="content-container site-container">

            {/* Sidebar */}
            <aside id="secondary" role="complementary" className="primary-sidebar widget-area sidebar-link-style-plain">
              <div className="sidebar-inner-wrap">

                {/* Precio */}
                <div className="widget">
                  <h2 className="widget-title">Filtrar por precio</h2>
                  <div className="widget-content">
                    <div className="widget-content-inner price-filter-wrap">
                      <input
                        type="number"
                        placeholder="Mínimo"
                        value={minInput}
                        onChange={(e) => setMinInput(e.target.value)}
                        min={0}
                      />
                      <input
                        type="number"
                        placeholder="Máximo"
                        value={maxInput}
                        onChange={(e) => setMaxInput(e.target.value)}
                        min={0}
                      />
                      <button onClick={applyPriceFilter} className="price-filter-btn">
                        Aplicar
                      </button>
                      {(minPrice || maxPrice) && (
                        <button onClick={clearPriceFilter} className="price-filter-clear">
                          Quitar filtro
                        </button>
                      )}
                    </div>
                  </div>
                </div>

                {/* Atributos dinámicos — estructura idéntica a Avanam tmcore widget */}
                {Object.entries(availableFilters).map(([attrKey, values]) => (
                  <div key={attrKey} className="widget tmcore-wp-widget-product-layered-nav tmcore-wp-widget-filter">
                    <h2 className="gamma widget-title">{ATTR_LABELS[attrKey] ?? attrKey}</h2>
                    <div className="widget-content">
                      <div className="widget-content-inner">
                        {attrKey === 'color' ? (
                          <ul className="show-labels-off show-display-inline show-items-count-off pa_color list-style-color">
                            {values.map((color) => {
                              const bg = COLOR_MAP[color] ?? '#888';
                              const active = activeAttributes[attrKey] === color;
                              return (
                                <li key={color} className={`wc-layered-nav-term${active ? ' chosen' : ''}`}>
                                  <button
                                    aria-label={color}
                                    onClick={() => toggleAttribute(attrKey, color)}
                                    className="filter-link term-link hint--bounce hint--top"
                                  >
                                    <div className="term-shape">
                                      <span className="term-shape-bg" style={{ background: bg }}></span>
                                      <span className="term-shape-border"></span>
                                    </div>
                                    <span className="term-name">{color}</span>
                                  </button>
                                </li>
                              );
                            })}
                          </ul>
                        ) : (
                          <ul className="show-labels-on show-display-inline show-items-count-off pa_size list-style-text">
                            {values.map((value) => {
                              const active = activeAttributes[attrKey] === value;
                              return (
                                <li key={value} className={`wc-layered-nav-term${active ? ' chosen' : ''}`}>
                                  <button
                                    aria-label={String(value)}
                                    onClick={() => toggleAttribute(attrKey, String(value))}
                                    className="filter-link term-link"
                                  >
                                    <span className="term-name">{value}</span>
                                  </button>
                                </li>
                              );
                            })}
                          </ul>
                        )}
                        {activeAttributes[attrKey] && (
                          <button
                            onClick={() => updateParams({ [`attr[${attrKey}]`]: null })}
                            className="price-filter-clear"
                          >
                            Quitar filtro
                          </button>
                        )}
                      </div>
                    </div>
                  </div>
                ))}

              </div>
            </aside>

            {/* Main */}
            <main id="main" className="site-main" role="main">

              <div id="sticky_filter" className="base-shop-top-row">
                <div className="base-shop-top-item base-woo-results-count">
                  {meta && (
                    <p className="woocommerce-result-count">
                      {meta.last_page === 1
                        ? <>Mostrando los <span className="result">{meta.total}</span> resultado{meta.total !== 1 ? 's' : ''}</>
                        : <>Mostrando {(meta.current_page - 1) * meta.per_page + 1}–<span className="showing">{Math.min(meta.current_page * meta.per_page, meta.total)}</span> de <span className="result">{meta.total}</span> resultados</>
                      }
                    </p>
                  )}
                </div>
                <div className="base-shop-top-item base-woo-ordering">
                  <form className="woocommerce-ordering">
                    <select
                      className="orderby"
                      value={sort}
                      onChange={(e) => updateParams({ sort: e.target.value })}
                      aria-label="Ordenar tienda"
                    >
                      {SORT_OPTIONS.map((opt) => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                      ))}
                    </select>
                  </form>
                </div>
                <div className="base-shop-top-item base-woo-toggle">
                  <div className="base-product-toggle-container base-product-toggle-outer">
                    <button
                      title="Vista grilla"
                      onClick={() => changeView('grid')}
                      className={`base-toggle-shop-layout base-toggle-grid${view === 'grid' ? ' toggle-active' : ''}`}
                      data-archive-toggle="grid"
                    >
                      <span className="base-svg-iconset">
                        <svg className="base-svg-icon base-grid-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                          <title>Grid</title>
                          <rect x="3.25" y="1.75" width="1.5" height="12.5" rx="0.75" fill="currentColor"/>
                          <rect x="7.25" y="1.75" width="1.5" height="12.5" rx="0.75" fill="currentColor"/>
                          <rect x="11.25" y="1.75" width="1.5" height="12.5" rx="0.75" fill="currentColor"/>
                        </svg>
                      </span>
                    </button>
                    <button
                      title="Vista lista"
                      onClick={() => changeView('list')}
                      className={`base-toggle-shop-layout base-toggle-list${view === 'list' ? ' toggle-active' : ''}`}
                      data-archive-toggle="list"
                    >
                      <span className="base-svg-iconset">
                        <svg className="base-svg-icon base-list-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                          <title>List</title>
                          <rect x="15.25" y="4.25" width="1.5" height="12.5" rx="0.75" transform="rotate(90 15.25 4.25)" fill="currentColor"/>
                          <rect x="15.25" y="8.25" width="1.5" height="12.5" rx="0.75" transform="rotate(90 15.25 8.25)" fill="currentColor"/>
                          <rect x="15.25" y="12.25" width="1.5" height="12.5" rx="0.75" transform="rotate(90 15.25 12.25)" fill="currentColor"/>
                        </svg>
                      </span>
                    </button>
                  </div>
                </div>
              </div>

              {/* Active filters bar — mismo HTML que Avanam */}
              {(Object.keys(activeAttributes).length > 0 || minPrice || maxPrice) && (
                <div id="active-filters-bar" className="active-filters-bar">
                  <div className="active-filters-list">
                    {Object.entries(activeAttributes).map(([key, value]) => (
                      <button
                        key={key}
                        aria-label={`Quitar ${ATTR_LABELS[key] ?? key}`}
                        onClick={() => updateParams({ [`attr[${key}]`]: null })}
                        className="remove-filter-link hint--bounce hint--top"
                      >
                        <div className="filter-link-text">{value}</div>
                      </button>
                    ))}
                    {(minPrice || maxPrice) && (
                      <button
                        aria-label="Quitar filtro de precio"
                        onClick={clearPriceFilter}
                        className="remove-filter-link hint--bounce hint--top"
                      >
                        <div className="filter-link-text">
                          {minPrice && maxPrice
                            ? `$${minPrice} – $${maxPrice}`
                            : minPrice
                            ? `Desde $${minPrice}`
                            : `Hasta $${maxPrice}`}
                        </div>
                      </button>
                    )}
                    <button
                      aria-label="Quitar todos los filtros"
                      onClick={() => {
                        const keys: Record<string, null> = { min_price: null, max_price: null };
                        Object.keys(activeAttributes).forEach((k) => { keys[`attr[${k}]`] = null; });
                        setMinInput('');
                        setMaxInput('');
                        updateParams(keys);
                      }}
                      className="remove-all-filters-link"
                    >
                      <div className="filter-link-text">Quitar todos</div>
                    </button>
                  </div>
                </div>
              )}

              {loading ? (
                <div className="woo-loading">Cargando productos...</div>
              ) : products.length === 0 ? (
                <div className="woo-empty">No se encontraron productos.</div>
              ) : view === 'list' ? (
                <ul className="products content-wrap product-archive products-list-view woo-archive-btn-button woocommerce-product-list">
                  {products.map((p) => (
                    <li key={p.id} className="product loop-entry content-bg">
                      <ProductCard product={p} view="list" />
                    </li>
                  ))}
                </ul>
              ) : (
                <ul className="products content-wrap product-archive grid-cols grid-ss-col-2 grid-sm-col-3 grid-lg-col-4 woo-archive-action-on-hover woo-archive-btn-button woo-archive-image-hover-slide align-buttons-bottom">
                  {products.map((p) => (
                    <li key={p.id} className="entry content-bg loop-entry product">
                      <ProductCard product={p} />
                    </li>
                  ))}
                </ul>
              )}

              {meta && meta.last_page > 1 && (
                <div className="woocommerce-pagination">
                  {Array.from({ length: meta.last_page }, (_, i) => i + 1).map((p) => (
                    <button
                      key={p}
                      onClick={() => {
                        const params = new URLSearchParams(searchParams.toString());
                        params.set('page', String(p));
                        router.push(`/categories/${slug}?${params.toString()}`);
                      }}
                      className={p === page ? 'page-numbers current' : 'page-numbers'}
                    >
                      {p}
                    </button>
                  ))}
                </div>
              )}

            </main>
        </div>
      </div>
    </div>
  );
}
