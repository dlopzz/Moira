'use client';

import { useState, useEffect } from 'react';
import ProductCard from '@/components/ProductCard';
import type { ProductTab } from '@/lib/api';

const PER_VIEW_DESKTOP = 4;
const PER_VIEW_MOBILE = 3;

const ArrowPath = () => (
  <path d="m15.5 0.932-4.3 4.38 14.5 14.6-14.5 14.5 4.3 4.4 14.6-14.6 4.4-4.3-4.4-4.4-14.6-14.6z" />
);

type Props = { title: string | null; tabs: ProductTab[]; };

export default function ProductTabsSection({ title, tabs }: Props) {
  const [activeIdx, setActiveIdx] = useState(0);
  const [offset, setOffset] = useState(0);
  const [perView, setPerView] = useState(PER_VIEW_DESKTOP);

  useEffect(() => {
    const mq = window.matchMedia('(max-width: 767px)');
    const update = () => {
      setPerView(mq.matches ? PER_VIEW_MOBILE : PER_VIEW_DESKTOP);
      setOffset(0);
    };
    update();
    mq.addEventListener('change', update);
    return () => mq.removeEventListener('change', update);
  }, []);

  if (!tabs.length) return null;

  const activeTab = tabs[activeIdx];
  const products = activeTab.products;
  const isMobile = perView === PER_VIEW_MOBILE;
  const effectivePerView = isMobile
    ? perView
    : Math.max(2, Math.min(products.length, perView));
  const cardPct = 100 / effectivePerView;
  const maxOffset = Math.max(0, products.length - effectivePerView);

  const switchTab = (i: number) => { setActiveIdx(i); setOffset(0); };

  const titleWords = (title ?? '').trim().split(/\s+/);
  const firstWord = titleWords[0] ?? '';
  const restWords = titleWords.slice(1).join(' ');

  const prevArrow = (
    <button className="splide__arrow splide__arrow--prev"
      onClick={() => setOffset(o => Math.max(0, o - 1))}
      disabled={offset === 0} aria-label="Anterior">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" focusable="false"
        style={{ transform: 'scaleX(-1)' }}>
        <ArrowPath />
      </svg>
    </button>
  );

  const nextArrow = (
    <button className="splide__arrow splide__arrow--next"
      onClick={() => setOffset(o => Math.min(maxOffset, o + 1))}
      disabled={offset >= maxOffset} aria-label="Siguiente">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" focusable="false">
        <ArrowPath />
      </svg>
    </button>
  );

  return (
    <section className="product-tabs-section site-container">
      {title && (
        <div className="product-tabs-section__title-row">
          <h2 className="product-tabs-section__title">
            <span className="product-tabs-section__title-light">{firstWord}</span>
            {restWords && <> <span className="product-tabs-section__title-bold">{restWords}</span></>}
          </h2>
        </div>
      )}

      {/* Desktop: arrows flank tabs. Mobile: nav solo, arrows van en slider-area */}
      <div className="product-tabs-section__controls">
        {!isMobile && prevArrow}
        <nav className="product-tabs-section__nav" aria-label="Categorías de productos">
          {tabs.map((tab, i) => (
            <span key={i} className="product-tabs-section__nav-item">
              {i > 0 && <span className="product-tabs-section__sep" aria-hidden="true">|</span>}
              <button
                className={`product-tabs-section__tab${i === activeIdx ? ' product-tabs-section__tab--active' : ''}`}
                onClick={() => switchTab(i)}>
                {tab.label}
              </button>
            </span>
          ))}
        </nav>
        {!isMobile && nextArrow}
      </div>

      {/* Slider area. Mobile: flechas absolutas sobre el viewport */}
      <div className="product-tabs-section__slider-area">
        {isMobile && maxOffset > 0 && prevArrow}
        <div className="product-tabs-section__viewport woocommerce">
          {products.length > 0 ? (
            <ul
              className="products woo-archive-action-on-hover woo-archive-btn-button woo-archive-image-hover-slide align-buttons-bottom product-tabs-section__track"
              style={{ transform: `translateX(-${offset * cardPct}%)`, width: '100%', display: 'flex', flexWrap: 'nowrap' }}>
              {products.map(p => (
                <li key={p.id}
                  className="entry content-bg loop-entry product product-tabs-section__card-wrap"
                  style={{ flex: `0 0 ${cardPct}%`, width: `${cardPct}%`, maxWidth: `${cardPct}%` }}>
                  <ProductCard product={p} showSaleBadge={false} />
                </li>
              ))}
            </ul>
          ) : (
            <p className="product-tabs-section__empty">No hay productos disponibles.</p>
          )}
        </div>
        {isMobile && maxOffset > 0 && nextArrow}
      </div>
    </section>
  );
}
