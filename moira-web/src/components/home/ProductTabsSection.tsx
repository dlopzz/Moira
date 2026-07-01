'use client';

import { useState } from 'react';
import ProductCard from '@/components/ProductCard';
import type { ProductTab } from '@/lib/api';

type Props = {
  title: string | null;
  tabs: ProductTab[];
};

export default function ProductTabsSection({ title, tabs }: Props) {
  const [activeIdx, setActiveIdx] = useState(0);

  if (!tabs.length) return null;

  const activeTab = tabs[activeIdx];

  return (
    <section className="home-tabs-section site-container">
      {title && (
        <div className="home-tabs-section__header">
          <span className="home-tabs-section__divider" />
          <h2 className="home-tabs-section__title" dangerouslySetInnerHTML={{ __html: title }} />
        </div>
      )}

      <div className="home-tabs-section__tabs" role="tablist">
        {tabs.map((tab, i) => (
          <button
            key={i}
            role="tab"
            aria-selected={i === activeIdx}
            className={`home-tabs-section__tab${i === activeIdx ? ' home-tabs-section__tab--active' : ''}`}
            onClick={() => setActiveIdx(i)}
          >
            {tab.label}
          </button>
        ))}
      </div>

      <div className="woocommerce">
        {activeTab.products.length > 0 ? (
          <ul className="products content-wrap product-archive grid-cols grid-ss-col-2 grid-sm-col-3 grid-lg-col-4 woo-archive-action-on-hover woo-archive-btn-button woo-archive-image-hover-slide align-buttons-bottom">
            {activeTab.products.map(p => (
              <li key={p.id} className="entry content-bg loop-entry product">
                <ProductCard product={p} showSaleBadge={false} />
              </li>
            ))}
          </ul>
        ) : (
          <p className="home-tabs-section__empty">No hay productos disponibles en este momento.</p>
        )}
      </div>
    </section>
  );
}
