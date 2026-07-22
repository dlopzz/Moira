'use client';

import { useEffect, useMemo, useState } from 'react';
import { useRouter } from 'next/navigation';
import Image from 'next/image';
import Link from 'next/link';
import { api, type Product, type ProductVariant, ApiError, imageUrl, imageThumbUrl, formatPrice } from '@/lib/api';
import type { WishlistItem } from '@/lib/wishlist-context';
import { useCart } from '@/lib/cart-context';
import Header from '@/components/Header';
import Breadcrumb from '@/components/Breadcrumb';
import WishlistButton from '@/components/WishlistButton';

export default function ProductDetailClient({ slug }: { slug: string }) {
  const router = useRouter();
  const { addItem } = useCart();
  const [product, setProduct] = useState<Product | null>(null);
  const [loading, setLoading] = useState(true);
  const [qty, setQty] = useState(1);
  const [adding, setAdding] = useState(false);
  const [added, setAdded] = useState(false);
  const [addError, setAddError] = useState('');
  const [selectedAttrs, setSelectedAttrs] = useState<Record<string, string>>({});
  const [activeTab, setActiveTab] = useState<'description' | 'reviews'>('description');
  const [activeImage, setActiveImage] = useState(0);
  // Por defecto la imagen sigue a la variante activa, pero un click en la
  // galería debe poder mostrar otra foto hasta que la variante cambie de
  // nuevo — userClearedVariantImage guarda esa preferencia manual.
  const [userClearedVariantImage, setUserClearedVariantImage] = useState(false);
  const [shareOpen, setShareOpen] = useState(false);
  const [copied, setCopied] = useState(false);

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

  // Auto-select first option per attribute (like WooCommerce does)
  useEffect(() => {
    if (attributeKeys.length === 0 || variants.length === 0) return;
    setSelectedAttrs((prev) => {
      if (Object.keys(prev).length > 0) return prev;
      const defaults: Record<string, string> = {};
      attributeKeys.forEach((key) => {
        const seen = new Set<string>();
        variants.forEach((v) => { if (v.attributes[key]) seen.add(v.attributes[key]); });
        const opts = Array.from(seen);
        if (opts.length > 0) defaults[key] = opts[0];
      });
      return defaults;
    });
  }, [attributeKeys, variants]);

  const activeVariant: ProductVariant | null = useMemo(() => {
    if (!isConfigurable || attributeKeys.length === 0) return null;
    const allSelected = attributeKeys.every((k) => selectedAttrs[k]);
    if (!allSelected) return null;
    return variants.find((v) => attributeKeys.every((k) => v.attributes[k] === selectedAttrs[k])) ?? null;
  }, [isConfigurable, attributeKeys, selectedAttrs, variants]);

  // Reinicia la preferencia manual cuando cambia la variante activa. Se hace
  // durante el render (no en un efecto) para que no haya un frame intermedio
  // mostrando la imagen vieja antes de corregirse.
  const [prevVariant, setPrevVariant] = useState(activeVariant);
  if (activeVariant !== prevVariant) {
    setPrevVariant(activeVariant);
    setUserClearedVariantImage(false);
    // Ajusta la cantidad al stock de la nueva variante para no exceder el tope.
    const newStock = activeVariant ? activeVariant.stock : (!isConfigurable ? (product?.stock ?? 0) : 0);
    if (qty > newStock) setQty(Math.max(1, newStock));
  }
  const variantImage = userClearedVariantImage ? null : imageUrl(activeVariant?.image);

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
    ...(product?.categories[0]
      ? [{ name: product.categories[0].name, href: `/categories/${product.categories[0].slug}` }]
      : []),
    ...(product ? [{ name: product.name }] : []),
  ];

  async function handleAddToCart() {
    if (isConfigurable && !allAttrsSelected) return;
    if (isConfigurable && !activeVariant) { setAddError('La combinación seleccionada no está disponible.'); return; }
    setAdding(true);
    setAddError('');
    try {
      await addItem(product!.id, qty, activeVariant?.id);
      setAdded(true);
      setTimeout(() => setAdded(false), 2500);
    } catch (err) {
      if (err instanceof ApiError) setAddError(err.message);
    } finally {
      setAdding(false);
    }
  }

  async function handleBuyNow() {
    if (!canAdd || adding) return;
    if (isConfigurable && !activeVariant) { setAddError('La combinación seleccionada no está disponible.'); return; }
    setAdding(true);
    setAddError('');
    try {
      await addItem(product!.id, qty, activeVariant?.id);
      router.push('/checkout/shipping');
    } catch (err) {
      if (err instanceof ApiError) setAddError(err.message);
      setAdding(false);
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen">
        <Header />
        <div className="site-container" style={{ paddingTop: '2rem', color: 'var(--global-palette4)' }}>Cargando...</div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen">
        <Header />
        <div className="site-container" style={{ paddingTop: '2rem' }}>
          <p style={{ color: 'var(--global-palette4)' }}>Producto no encontrado.</p>
          <Link href="/" style={{ color: 'var(--global-palette3)', fontSize: '13px' }}>Volver al inicio</Link>
        </div>
      </div>
    );
  }

  const { price, salePrice } = displayPrice();
  const hasDiscount = salePrice !== null && salePrice < price;
  const stock = displayStock();
  const canAdd = !adding && (!isConfigurable ? product.stock > 0 : allAttrsSelected && activeVariant !== null && activeVariant.stock > 0);
  const images = product.images.slice(0, 4);
  const hasThumbs = images.length > 1;
  const hasDescription = Boolean(product.description);
  const hasReviews = product.reviews && product.reviews.length > 0;
  const showTabs = hasDescription || hasReviews;

  return (
    <>
      <Header />

      {/* Breadcrumb hero */}
      <div className="entry-hero single-product-hero-section">
        <div className="entry-hero-container-inner">
          <div className="hero-section-overlay" />
          <div className="hero-container site-container">
            <header className="entry-header">
              <Breadcrumb crumbs={crumbs} />
            </header>
          </div>
        </div>
      </div>

      {/* Main product area */}
      <div className="woocommerce single-product-content-area">
        <div className="site-container single-product-container">

          {/* Gallery + Summary */}
          <div id="wrap-summary" className="wrap-summary">

            {/* Product images */}
            <div className="base-product-image-wrap product-images">
              <div className={`woocommerce-product-gallery woocommerce-product-gallery--with-images${hasThumbs ? ' gallery-has-thumbnails' : ''}`}>
                <div className="product_image">

                  {/* Main image — variant image overrides gallery selection */}
                  <div className="base-product-gallery-main">
                    <div className="product-main-image-inner">
                      {(variantImage ?? imageUrl(images[activeImage])) ? (
                        <Image
                          src={variantImage ?? imageUrl(images[activeImage])!}
                          alt={product.name}
                          fill
                          style={{ objectFit: 'cover' }}
                          priority
                          sizes="(max-width: 1024px) 100vw, 600px"
                        />
                      ) : (
                        <div className="product-image-placeholder" />
                      )}
                    </div>
                  </div>

                  {/* Thumbnail column */}
                  {hasThumbs && (
                    <div className="base-product-gallery-thumbnails">
                      <div className="splide__track">
                        <ul className="splide__list">
                          {images.map((img, i) => (
                            <li
                              key={i}
                              className={`bt-woo-gallery-thumbnail splide__slide${i === activeImage ? ' is-active' : ''}`}
                              onClick={() => { setActiveImage(i); setUserClearedVariantImage(true); }}
                              onKeyDown={(e) => {
                                if (e.key === 'Enter') { setActiveImage(i); setUserClearedVariantImage(true); }
                              }}
                              role="button"
                              tabIndex={0}
                              aria-label={`Ver imagen ${i + 1}`}
                            >
                              <Image
                                src={imageThumbUrl(img) ?? imageUrl(img)!}
                                alt=""
                                width={171}
                                height={171}
                                style={{ objectFit: 'cover', width: '100%', height: '100%', display: 'block' }}
                              />
                            </li>
                          ))}
                        </ul>
                      </div>
                    </div>
                  )}

                </div>
              </div>
            </div>

            {/* Summary */}
            <div className="summary entry-summary">
              {hasDiscount && <span className="onsale">¡Oferta!</span>}
              <h1 className="product_title entry-title">{product.name}</h1>

              <div className="wrap_price_rating">
                <p className="price">
                  {hasDiscount ? (
                    <>
                      <del aria-hidden="true">
                        <span className="woocommerce-Price-amount amount"><bdi>${formatPrice(price)}</bdi></span>
                      </del>
                      {' '}
                      <ins aria-hidden="true">
                        <span className="woocommerce-Price-amount amount"><bdi>${formatPrice(salePrice!)}</bdi></span>
                      </ins>
                    </>
                  ) : (
                    <span className="woocommerce-Price-amount amount"><bdi>${formatPrice(price)}</bdi></span>
                  )}
                </p>
              </div>

              {product.short_description && (
                <div className="woocommerce-product-details__short-description">
                  <p>{product.short_description}</p>
                </div>
              )}

              {/* Variant selectors */}
              {isConfigurable && attributeKeys.length > 0 && (
                <div className="variations-wrap">
                  {attributeKeys.map((key) => (
                    <div key={key} className="variation-row">
                      <p className="variation-label">
                        {key}{selectedAttrs[key] && <span style={{ fontWeight: 400, textTransform: 'none' }}>: {selectedAttrs[key]}</span>}
                      </p>
                      <div className="variation-options">
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
                              className={`variation-option${selected ? ' selected' : ''}${!available ? ' unavailable' : ''}`}
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
                    <p style={{ color: 'var(--danger)', fontSize: '13px' }}>La combinación seleccionada no está disponible.</p>
                  )}
                </div>
              )}

              {/* Stock */}
              {(!isConfigurable || allAttrsSelected) && (
                <div className={`stock ${stock > 0 ? 'in-stock' : 'out-of-stock'} entry-product-stock`}>
                  {stock > 0 ? `${stock} ${stock === 1 ? 'disponible' : 'disponibles'}` : 'Sin stock'}
                </div>
              )}

              {addError && <p className="single-product-error">{addError}</p>}

              {/* Form */}
              <form className="cart" onSubmit={(e) => { e.preventDefault(); handleAddToCart(); }}>
                <div className="quantity spinners-added">
                  <button
                    type="button"
                    className="quantity-btn minus"
                    onClick={() => setQty((q) => Math.max(1, q - 1))}
                    aria-label="Reducir cantidad"
                  >−</button>
                  <input
                    type="number"
                    className="input-text qty text"
                    value={qty}
                    min={1}
                    max={stock}
                    onChange={(e) => setQty(Math.min(Math.max(stock, 1), Math.max(1, parseInt(e.target.value) || 1)))}
                    aria-label="Cantidad"
                  />
                  <button
                    type="button"
                    className="quantity-btn plus"
                    onClick={() => setQty((q) => Math.min(stock, q + 1))}
                    disabled={qty >= stock}
                    aria-label="Aumentar cantidad"
                  >+</button>
                </div>
                <button
                  type="submit"
                  className={`single_add_to_cart_button button alt${!canAdd ? ' disabled' : ''}`}
                  disabled={!canAdd}
                >
                  {adding ? 'Agregando...' : added ? '¡Agregado al carrito!' : isConfigurable && !allAttrsSelected ? 'Seleccioná las opciones' : 'Agregar al carrito'}
                </button>
                <button
                  type="button"
                  className={`single_add_to_cart_button button alt button-buy-now${!canAdd ? ' disabled' : ''}`}
                  disabled={!canAdd || adding}
                  onClick={handleBuyNow}
                >
                  <span>Compralo ahora</span>
                </button>
              </form>

              <div className="wrap_after_button">
                <div className="wrap_compare_wishlist">
                  <WishlistButton product={{ id: product.id, name: product.name, slug: product.slug, price: product.price, sale_price: product.sale_price, image: product.images[0] ?? null }} inline />
                </div>
                <div className="wrap_ask_share">
                  <button
                    type="button"
                    className="woosc-btn woosc-btn-has-icon woosc-btn-icon-text"
                    onClick={() => setShareOpen(true)}
                    aria-label="Compartir"
                  >
                    <span className="woosc-btn-icon">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="13.0 15.8 61.3 68.2" width="16" height="16" fill="currentColor">
                        <path d="M62.1,59.8c-3.7,0-6.9,1.6-9.1,4.2l-17-8.8c0.8-1.6,1.2-3.4,1.2-5.3s-0.5-3.7-1.2-5.3l17-8.8c2.2,2.6,5.5,4.2,9.1,4.2c6.6,0,12.1-5.4,12.1-12.1c0-6.6-5.4-12.1-12.1-12.1S50,21.5,50,28.1c0,1.6,0.3,3,0.8,4.4l-17.3,8.9c-2.2-2.2-5.2-3.5-8.5-3.5c-6.5,0-12,5.5-12,12.1s5.4,12.1,12.1,12.1c3.3,0,6.3-1.3,8.5-3.5l17.3,8.9c-0.5,1.4-0.8,2.8-0.8,4.4c0,6.6,5.4,12.1,12.1,12.1s12.1-5.4,12.1-12.1S68.7,59.8,62.1,59.8z" />
                      </svg>
                    </span>
                    <span className="woosc-btn-text">Compartir</span>
                  </button>
                </div>
              </div>

              <fieldset className="single-product-payments payments-color-scheme-inherit">
                <ul>
                  <li className="single-product-payments-visa">
                    <span className="base-svg-iconset">
                      <svg width="100%" height="100%" viewBox="0 0 750 471" xmlns="http://www.w3.org/2000/svg" style={{ fillRule: 'evenodd', clipRule: 'evenodd' }}>
                        <title>Visa</title>
                        <path d="M750,40c0,-22.077 -17.923,-40 -40,-40l-670,0c-22.077,0 -40,17.923 -40,40l0,391c0,22.077 17.923,40 40,40l670,0c22.077,0 40,-17.923 40,-40l0,-391Z" fill="rgb(14,69,149)"/>
                        <path d="M278.197,334.228l33.361,-195.763l53.36,0l-33.385,195.763l-53.336,0Zm246.11,-191.54c-10.572,-3.966 -27.136,-8.222 -47.822,-8.222c-52.725,0 -89.865,26.55 -90.18,64.603c-0.298,28.13 26.513,43.822 46.753,53.186c20.77,9.594 27.752,15.714 27.654,24.283c-0.132,13.121 -16.587,19.116 -31.923,19.116c-21.357,0 -32.703,-2.966 -50.226,-10.276l-6.876,-3.111l-7.49,43.824c12.464,5.464 35.51,10.198 59.438,10.443c56.09,0 92.501,-26.246 92.916,-66.882c0.2,-22.268 -14.016,-39.216 -44.8,-53.188c-18.65,-9.055 -30.072,-15.099 -29.951,-24.268c0,-8.137 9.667,-16.839 30.556,-16.839c17.45,-0.27 30.089,3.535 39.937,7.5l4.781,2.26l7.234,-42.43m137.307,-4.222l-41.231,0c-12.774,0 -22.332,3.487 -27.942,16.234l-79.245,179.404l56.032,0c0,0 9.161,-24.123 11.233,-29.418c6.124,0 60.554,0.084 68.337,0.084c1.596,6.853 6.491,29.334 6.491,29.334l49.513,0l-43.188,-195.638Zm-65.418,126.407c4.413,-11.279 21.26,-54.723 21.26,-54.723c-0.316,0.522 4.38,-11.334 7.075,-18.684l3.606,16.879c0,0 10.217,46.728 12.352,56.528l-44.293,0Zm-363.293,-126.406l-52.24,133.496l-5.567,-27.13c-9.725,-31.273 -40.025,-65.155 -73.898,-82.118l47.766,171.203l56.456,-0.065l84.004,-195.386l-56.521,0Z" fill="white"/>
                        <path d="M131.92,138.465l-86.041,0l-0.681,4.073c66.938,16.204 111.231,55.363 129.618,102.414l-18.71,-89.96c-3.23,-12.395 -12.597,-16.094 -24.186,-16.526"/>
                      </svg>
                    </span>
                  </li>
                  <li className="single-product-payments-mastercard">
                    <span className="base-svg-iconset">
                      <svg width="100%" height="100%" viewBox="0 0 750 471" xmlns="http://www.w3.org/2000/svg">
                        <title>Mastercard</title>
                        <rect width="750" height="471" rx="40" fill="#252525"/>
                        <circle cx="290" cy="236" r="150" fill="#EB001B"/>
                        <circle cx="460" cy="236" r="150" fill="#F79E1B"/>
                      </svg>
                    </span>
                  </li>
                  <li className="single-product-payments-amex">
                    <span className="base-svg-iconset">
                      <svg width="100%" height="100%" viewBox="0 0 750 471" xmlns="http://www.w3.org/2000/svg">
                        <title>American Express</title>
                        <rect width="750" height="471" rx="40" fill="#2E77BC"/>
                        <text x="375" y="315" textAnchor="middle" fill="white" fontSize="195" fontWeight="bold" fontFamily="Arial,sans-serif">AMEX</text>
                      </svg>
                    </span>
                  </li>
                </ul>
              </fieldset>

              <div className="product_meta">
                {product.sku && (
                  <span className="sku_wrapper">
                    SKU: <span className="sku">{activeVariant?.sku ?? product.sku}</span>
                  </span>
                )}
              </div>
            </div>
          </div>

          {/* Tabs */}
          {showTabs && (
            <div className="woocommerce-tabs wc-tabs-wrapper">
              <ul className="tabs wc-tabs" role="tablist">
                {hasDescription && (
                  <li
                    role="presentation"
                    className={`description_tab${activeTab === 'description' ? ' active' : ''}`}
                    id="tab-title-description"
                  >
                    <a
                      href="#tab-description"
                      role="tab"
                      onClick={(e) => { e.preventDefault(); setActiveTab('description'); }}
                    >
                      Descripción
                    </a>
                  </li>
                )}
                {hasReviews && (
                  <li
                    role="presentation"
                    className={`reviews_tab${activeTab === 'reviews' ? ' active' : ''}`}
                    id="tab-title-reviews"
                  >
                    <a
                      href="#tab-reviews"
                      role="tab"
                      onClick={(e) => { e.preventDefault(); setActiveTab('reviews'); }}
                    >
                      Reseñas ({product.reviews!.length})
                    </a>
                  </li>
                )}
              </ul>

              {activeTab === 'description' && hasDescription && (
                <div
                  className="woocommerce-Tabs-panel woocommerce-Tabs-panel--description panel entry-content wc-tab"
                  id="tab-description"
                  role="tabpanel"
                  aria-labelledby="tab-title-description"
                >
                  <div
                    className="product-description-content"
                    dangerouslySetInnerHTML={{ __html: product.description! }}
                  />
                </div>
              )}

              {activeTab === 'reviews' && hasReviews && (
                <div
                  className="woocommerce-Tabs-panel woocommerce-Tabs-panel--reviews panel entry-content wc-tab"
                  id="tab-reviews"
                  role="tabpanel"
                  aria-labelledby="tab-title-reviews"
                >
                  <div className="woocommerce-Reviews" id="reviews">
                    {product.rating_count != null && product.rating_count > 0 && (
                      <div className="review-rating-summary">
                        <span>{product.rating_average?.toFixed(1)}</span>
                        <span style={{ color: '#f0ad00', marginLeft: '0.5em' }}>
                          {'★'.repeat(Math.round(product.rating_average ?? 0))}
                        </span>
                        <span style={{ marginLeft: '0.5em' }}>({product.rating_count} reseñas)</span>
                      </div>
                    )}
                    <ol className="commentlist" style={{ listStyle: 'none', padding: 0, margin: 0 }}>
                      {product.reviews!.map((r) => (
                        <li key={r.id} className="review-item">
                          <div className="review-meta">
                            <strong>{r.customer}</strong>
                            <span style={{ color: '#f0ad00', marginLeft: '0.5em' }}>
                              {'★'.repeat(r.rating)}{'☆'.repeat(5 - r.rating)}
                            </span>
                            <time style={{ marginLeft: 'auto', fontSize: '12px', color: 'var(--global-palette6)' }}>
                              {new Date(r.submitted_at).toLocaleDateString('es-AR', { day: '2-digit', month: 'short', year: 'numeric' })}
                            </time>
                          </div>
                          {r.title && <p className="review-title">{r.title}</p>}
                          <p className="review-body">{r.body}</p>
                        </li>
                      ))}
                    </ol>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Related products */}
          {product.related && product.related.length > 0 && (
            <section className="related products">
              <h2>Productos relacionados</h2>
              <ul className="products content-wrap product-archive grid-cols grid-ss-col-2 grid-sm-col-3 grid-lg-col-4 woo-archive-action-on-hover woo-archive-btn-button woo-archive-image-hover-fade align-buttons-bottom">
                {product.related.map((rel) => (
                  <li key={rel.id} className="entry content-bg loop-entry product">
                    <div className="product-thumbnail">
                      <Link
                        href={`/products/${rel.slug}`}
                        className="woocommerce-loop-image-link woocommerce-LoopProduct-link product-has-hover-image"
                        aria-label={rel.name}
                      >
                        {rel.image ? (
                          <Image
                            src={imageUrl(rel.image)!}
                            alt={rel.name}
                            fill
                            className="loop-product-image"
                            sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 25vw"
                            style={{ objectFit: 'cover' }}
                          />
                        ) : (
                          <div className="product-image-placeholder" />
                        )}
                      </Link>
                    </div>
                    <div className="product-details content-bg entry-content-wrap">
                      <h2 className="woocommerce-loop-product__title">
                        <Link href={`/products/${rel.slug}`} className="woocommerce-LoopProduct-link-title">
                          {rel.name}
                        </Link>
                      </h2>
                      <span className="price">
                        {rel.sale_price !== null && rel.sale_price < rel.price ? (
                          <>
                            <del aria-hidden="true">
                              <span className="woocommerce-Price-amount amount"><bdi>${formatPrice(rel.price)}</bdi></span>
                            </del>
                            {' '}
                            <ins aria-hidden="true">
                              <span className="woocommerce-Price-amount amount"><bdi>${formatPrice(rel.sale_price)}</bdi></span>
                            </ins>
                          </>
                        ) : (
                          <span className="woocommerce-Price-amount amount"><bdi>${formatPrice(rel.price)}</bdi></span>
                        )}
                      </span>
                      <div className="product-action-wrap">
                        <Link href={`/products/${rel.slug}`} className="button add_to_cart_button">
                          Ver producto
                        </Link>
                      </div>
                    </div>
                  </li>
                ))}
              </ul>
            </section>
          )}

        </div>
      </div>

      {shareOpen && (
        <div className="share-popup-overlay" onClick={() => setShareOpen(false)}>
          <div className="share-popup-inner" onClick={(e) => e.stopPropagation()} role="dialog" aria-modal="true" aria-label="Compartir">
            <div className="share-popup-header">
              <h4 className="share-popup-title">Compartir</h4>
              <button
                type="button"
                className="share-popup-close"
                onClick={() => setShareOpen(false)}
                aria-label="Cerrar"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M5.293 6.707l5.293 5.293-5.293 5.293c-0.391 0.391-0.391 1.024 0 1.414s1.024 0.391 1.414 0l5.293-5.293 5.293 5.293c0.391 0.391 1.024 0.391 1.414 0s0.391-1.024 0-1.414l-5.293-5.293 5.293-5.293c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-5.293 5.293-5.293-5.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414z" />
                </svg>
              </button>
            </div>
            <div className="share-popup-content">
              <div className="share-url-row">
                <input
                  type="text"
                  className="share-url-input"
                  value={typeof window !== 'undefined' ? window.location.href : ''}
                  readOnly
                  onClick={(e) => (e.target as HTMLInputElement).select()}
                />
                <button
                  type="button"
                  className="share-copy-btn button"
                  onClick={async () => {
                    try {
                      await navigator.clipboard.writeText(window.location.href);
                    } catch {
                      const el = document.querySelector('.share-url-input') as HTMLInputElement;
                      el?.select();
                      document.execCommand('copy');
                    }
                    setCopied(true);
                    setTimeout(() => setCopied(false), 2000);
                  }}
                >
                  {copied ? 'Copiado!' : 'Copiar'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
