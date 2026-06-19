'use client';

import { useEffect, useMemo, useState } from 'react';
import { useRouter } from 'next/navigation';
import Image from 'next/image';
import Link from 'next/link';
import { api, type Product, type ProductVariant, ApiError } from '@/lib/api';
import { getToken } from '@/lib/auth';
import { useCart } from '@/lib/cart-context';
import Header from '@/components/Header';
import Breadcrumb from '@/components/Breadcrumb';
import WishlistButton from '@/components/WishlistButton';
import ProductCard from '@/components/ProductCard';

export default function ProductDetailClient({ slug }: { slug: string }) {
  const router = useRouter();
  const { addItem } = useCart();
  const [product, setProduct] = useState<Product | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeImage, setActiveImage] = useState(0);
  const [adding, setAdding] = useState(false);
  const [added, setAdded] = useState(false);
  const [addError, setAddError] = useState('');
  const [selectedAttrs, setSelectedAttrs] = useState<Record<string, string>>({});

  useEffect(() => {
    api
      .getProduct(slug)
      .then((res) => setProduct(res.data))
      .finally(() => setLoading(false));
  }, [slug]);

  const isConfigurable = product?.product_type === 'configurable';
  const variants = product?.variants ?? [];

  const attributeKeys: string[] = useMemo(() => {
    if (!isConfigurable || variants.length === 0) return [];
    const keys = new Set<string>();
    variants.forEach((v) => Object.keys(v.attributes).forEach((k) => keys.add(k)));
    return Array.from(keys);
  }, [isConfigurable, variants]);

  const attrOptions = (key: string): string[] => {
    const seen = new Set<string>();
    variants.forEach((v) => { if (v.attributes[key]) seen.add(v.attributes[key]); });
    return Array.from(seen);
  };

  const activeVariant: ProductVariant | null = useMemo(() => {
    if (!isConfigurable || attributeKeys.length === 0) return null;
    const allSelected = attributeKeys.every((k) => selectedAttrs[k]);
    if (!allSelected) return null;
    return variants.find((v) => attributeKeys.every((k) => v.attributes[k] === selectedAttrs[k])) ?? null;
  }, [isConfigurable, attributeKeys, selectedAttrs, variants]);

  const allAttrsSelected = isConfigurable ? attributeKeys.every((k) => selectedAttrs[k]) : true;

  const displayPrice = (): { price: number; salePrice: number | null } => {
    if (activeVariant) {
      const effective = activeVariant.price ?? product!.sale_price ?? product!.price;
      const hasDiscount = activeVariant.price === null && product!.sale_price !== null;
      return { price: hasDiscount ? product!.price : effective, salePrice: hasDiscount ? product!.sale_price : null };
    }
    return { price: product!.price, salePrice: product!.sale_price };
  };

  const displayStock = (): number => {
    if (activeVariant) return activeVariant.stock;
    if (!isConfigurable) return product?.stock ?? 0;
    return 0;
  };

  const crumbs = [
    ...(product?.categories[0] ? [{ name: product.categories[0].name, href: `/categories/${product.categories[0].slug}` }] : []),
    ...(product ? [{ name: product.name }] : []),
  ];

  async function handleAddToCart() {
    if (!getToken()) { router.push(`/auth/login?redirect=/products/${slug}`); return; }
    if (isConfigurable && !allAttrsSelected) return;
    if (isConfigurable && !activeVariant) { setAddError('La combinación seleccionada no está disponible.'); return; }
    setAdding(true);
    setAddError('');
    try {
      await addItem(product!.id, 1, activeVariant?.id);
      setAdded(true);
      setTimeout(() => setAdded(false), 2500);
    } catch (err) {
      if (err instanceof ApiError) setAddError(err.message);
    } finally {
      setAdding(false);
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <div className="max-w-6xl mx-auto px-4 py-8 text-gray-400">Cargando...</div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <div className="max-w-6xl mx-auto px-4 py-8">
          <p className="text-gray-500">Producto no encontrado.</p>
          <Link href="/" className="text-blue-600 hover:underline text-sm mt-2 inline-block">Volver al inicio</Link>
        </div>
      </div>
    );
  }

  const { price, salePrice } = displayPrice();
  const hasDiscount = salePrice !== null && salePrice < price;
  const stock = displayStock();
  const canAdd = !adding && (!isConfigurable ? product.stock > 0 : allAttrsSelected && activeVariant !== null && activeVariant.stock > 0);

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <main className="max-w-6xl mx-auto px-4 py-8">
        <Breadcrumb crumbs={crumbs} />

        <div className="bg-white rounded-xl shadow-sm overflow-hidden">
          <div className="grid md:grid-cols-2 gap-0">
            {/* Images */}
            <div className="p-6 space-y-3">
              <div className="relative aspect-square bg-gray-100 rounded-lg overflow-hidden">
                {product.images[activeImage] ? (
                  <Image src={product.images[activeImage]} alt={product.name} fill className="object-cover" sizes="(max-width: 768px) 100vw, 50vw" priority />
                ) : (
                  <div className="absolute inset-0 flex items-center justify-center text-gray-300 text-6xl">📦</div>
                )}
              </div>
              {product.images.length > 1 && (
                <div className="flex gap-2 overflow-x-auto pb-1">
                  {product.images.map((img, i) => (
                    <button key={i} onClick={() => setActiveImage(i)} className={`relative flex-none w-16 h-16 rounded-md overflow-hidden border-2 transition-colors ${activeImage === i ? 'border-blue-500' : 'border-transparent'}`}>
                      <Image src={img} alt="" fill className="object-cover" sizes="64px" />
                    </button>
                  ))}
                </div>
              )}
            </div>

            {/* Info */}
            <div className="p-6 flex flex-col justify-between">
              <div className="space-y-4">
                <div>
                  {product.categories.length > 0 && (
                    <div className="flex gap-1 flex-wrap mb-2">
                      {product.categories.map((c) => (
                        <Link key={c.id} href={`/categories/${c.slug}`} className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full hover:bg-gray-200">{c.name}</Link>
                      ))}
                    </div>
                  )}
                  <h1 className="text-2xl font-bold text-gray-900">{product.name}</h1>
                  <p className="text-sm text-gray-400 mt-1">SKU: {activeVariant?.sku ?? product.sku}</p>
                </div>

                <div className="flex items-baseline gap-3">
                  {hasDiscount ? (
                    <>
                      <span className="text-3xl font-bold text-red-600">${salePrice!.toFixed(2)}</span>
                      <span className="text-lg text-gray-400 line-through">${price.toFixed(2)}</span>
                    </>
                  ) : (
                    <span className="text-3xl font-bold text-gray-900">${price.toFixed(2)}</span>
                  )}
                </div>

                {product.short_description && (
                  <p className="text-gray-600 text-sm leading-relaxed">{product.short_description}</p>
                )}

                {/* Variant selectors */}
                {isConfigurable && attributeKeys.length > 0 && (
                  <div className="space-y-4">
                    {attributeKeys.map((key) => (
                      <div key={key}>
                        <p className="text-sm font-medium text-gray-700 mb-2">
                          {key}{selectedAttrs[key] && <span className="font-normal text-gray-500 ml-1">: {selectedAttrs[key]}</span>}
                        </p>
                        <div className="flex flex-wrap gap-2">
                          {attrOptions(key).map((val) => {
                            const available = variants.some(
                              (v) => v.attributes[key] === val && v.stock > 0 &&
                                attributeKeys.filter((k) => k !== key).every((k) => !selectedAttrs[k] || v.attributes[k] === selectedAttrs[k]),
                            );
                            const selected = selectedAttrs[key] === val;
                            return (
                              <button
                                key={val}
                                onClick={() => setSelectedAttrs((prev) => selected
                                  ? Object.fromEntries(Object.entries(prev).filter(([k]) => k !== key))
                                  : { ...prev, [key]: val }
                                )}
                                className={`px-3 py-1.5 rounded-md text-sm font-medium border transition-all ${selected ? 'border-blue-600 bg-blue-50 text-blue-700' : available ? 'border-gray-300 text-gray-700 hover:border-gray-400' : 'border-gray-200 text-gray-300 line-through cursor-not-allowed'}`}
                                disabled={!available}
                              >
                                {val}
                              </button>
                            );
                          })}
                        </div>
                      </div>
                    ))}
                    {allAttrsSelected && !activeVariant && (
                      <p className="text-sm text-red-500">La combinación seleccionada no está disponible.</p>
                    )}
                  </div>
                )}

                {/* Stock indicator */}
                {!isConfigurable ? (
                  <p className={`text-sm font-medium ${product.stock > 0 ? 'text-green-600' : 'text-red-500'}`}>
                    {product.stock > 0 ? `Stock disponible (${product.stock} unidades)` : 'Sin stock'}
                  </p>
                ) : allAttrsSelected && activeVariant ? (
                  <p className={`text-sm font-medium ${stock > 0 ? 'text-green-600' : 'text-red-500'}`}>
                    {stock > 0 ? `Stock disponible (${stock} unidades)` : 'Sin stock'}
                  </p>
                ) : null}
              </div>

              <div className="mt-6 space-y-3">
                {addError && <p className="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">{addError}</p>}
                <button
                  onClick={handleAddToCart}
                  disabled={!canAdd}
                  className={`w-full py-3 px-6 rounded-lg font-semibold text-white transition-all ${!canAdd ? 'bg-gray-300 cursor-not-allowed' : added ? 'bg-green-600 scale-[0.99]' : 'bg-blue-600 hover:bg-blue-700 disabled:opacity-60'}`}
                >
                  {adding ? 'Agregando...' : added ? '¡Agregado al carrito!' : isConfigurable && !allAttrsSelected ? 'Seleccioná las opciones' : 'Agregar al carrito'}
                </button>
                <div className="flex items-center gap-2">
                  <WishlistButton productId={product.id} inline />
                  <span className="text-sm text-gray-500">Agregar a favoritos</span>
                </div>
              </div>
            </div>
          </div>

          {product.description && (
            <div className="px-6 pb-8 border-t border-gray-100 mt-4 pt-6">
              <h2 className="font-semibold text-gray-900 mb-3">Descripción</h2>
              <div className="text-gray-600 text-sm leading-relaxed whitespace-pre-wrap">{product.description}</div>
            </div>
          )}

          {/* Reviews */}
          <div className="px-6 pb-8 border-t border-gray-100 pt-6">
            <div className="flex items-center gap-3 mb-5">
              <h2 className="font-semibold text-gray-900">Reseñas</h2>
              {product.rating_count != null && product.rating_count > 0 && (
                <div className="flex items-center gap-1.5">
                  <span className="text-yellow-400 font-bold text-sm">{product.rating_average}</span>
                  <span className="text-yellow-400">{'★'.repeat(Math.round(product.rating_average ?? 0))}</span>
                  <span className="text-gray-400 text-sm">({product.rating_count})</span>
                </div>
              )}
            </div>
            {!product.reviews || product.reviews.length === 0 ? (
              <p className="text-gray-400 text-sm">Este producto todavía no tiene reseñas.</p>
            ) : (
              <div className="space-y-5">
                {product.reviews.map((r) => (
                  <div key={r.id} className="border border-gray-100 rounded-xl p-4">
                    <div className="flex items-start justify-between gap-2 mb-2">
                      <div>
                        <span className="text-yellow-400 text-sm">{'★'.repeat(r.rating)}{'☆'.repeat(5 - r.rating)}</span>
                        {r.title && <p className="font-medium text-gray-900 text-sm mt-0.5">{r.title}</p>}
                      </div>
                      <div className="text-right shrink-0">
                        <p className="text-xs font-medium text-gray-700">{r.customer}</p>
                        <p className="text-xs text-gray-400">{new Date(r.submitted_at).toLocaleDateString('es-AR', { day: '2-digit', month: 'short', year: 'numeric' })}</p>
                      </div>
                    </div>
                    <p className="text-gray-600 text-sm leading-relaxed">{r.body}</p>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Productos relacionados */}
        {product.related && product.related.length > 0 && (
          <div className="mt-10">
            <h2 className="text-lg font-bold text-gray-900 mb-4">Productos relacionados</h2>
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
              {product.related.map((rel) => (
                <Link key={rel.id} href={`/products/${rel.slug}`} className="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow group">
                  <div className="relative aspect-square bg-gray-100">
                    {rel.image ? (
                      <Image src={rel.image} alt={rel.name} fill className="object-cover group-hover:scale-105 transition-transform duration-300" sizes="200px" />
                    ) : (
                      <div className="absolute inset-0 flex items-center justify-center text-gray-300 text-3xl">📦</div>
                    )}
                  </div>
                  <div className="p-2">
                    <p className="text-xs font-medium text-gray-900 line-clamp-2 leading-tight">{rel.name}</p>
                    <div className="mt-1 flex items-baseline gap-1">
                      {rel.sale_price ? (
                        <>
                          <span className="text-xs font-bold text-red-600">${rel.sale_price.toFixed(2)}</span>
                          <span className="text-xs text-gray-400 line-through">${rel.price.toFixed(2)}</span>
                        </>
                      ) : (
                        <span className="text-xs font-bold text-gray-900">${rel.price.toFixed(2)}</span>
                      )}
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
