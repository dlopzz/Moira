'use client';

import { useEffect, useState } from 'react';
import { api, type CmsPage, type SiteInfo, type Category } from '@/lib/api';

const IconPin = () => (
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round">
    <path d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
  </svg>
);
const IconPhone = () => (
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round">
    <path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
  </svg>
);
const IconEmail = () => (
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round">
    <path d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
  </svg>
);

export default function Footer() {
  const [pages, setPages]           = useState<CmsPage[]>([]);
  const [info, setInfo]             = useState<SiteInfo | null>(null);
  const [categories, setCategories] = useState<Category[]>([]);

  useEffect(() => {
    api.getFooterPages().then(r  => setPages(r.data)).catch(() => {});
    api.getSiteSettings().then(r => setInfo(r.data)).catch(() => {});
    api.getCategories().then(r   => setCategories(r.data.slice(0, 5))).catch(() => {});
  }, []);

  const year = new Date().getFullYear();

  return (
    <footer id="colophon" className="site-footer" role="contentinfo">
      <div className="site-footer-wrap">

        {/* ── Middle row: 4 columns ─────────────────────────────────── */}
        <div className="site-middle-footer-wrap site-footer-row-container site-footer-focus-item site-footer-row-layout-standard site-footer-row-tablet-layout-default site-footer-row-mobile-layout-default">
          <div className="site-footer-row-container-inner">
            <div className="site-container">
              <div className="site-middle-footer-inner-wrap site-footer-row site-footer-row-columns-4 site-footer-row-column-layout-center-half site-footer-row-tablet-column-layout-two-grid site-footer-row-mobile-column-layout-row ft-ro-dir-row ft-ro-collapse-normal ft-ro-t-dir-default ft-ro-m-dir-column ft-ro-lstyle-noline">

                {/* Col 1: Logo + descripción */}
                <div className="site-footer-middle-section-1 site-footer-section footer-section-inner-items-1">
                  <div className="footer-widget-area widget-area site-footer-focus-item footer-widget1 content-align-default content-tablet-align-default content-mobile-align-default content-valign-default content-tablet-valign-default content-mobile-valign-default">
                    <div className="footer-widget-area-inner site-info-inner">
                      <div className="wp-widget-group__inner-blocks">
                        <figure className="wp-block-image size-full footer-brand-logo">
                          <img src="/logo.svg" alt="Moira Bikinis" width={156} height={35} />
                        </figure>
                        <p className="footer-brand-description">
                          {info?.description || 'Lorem Ipsum has been the industry\'s standard text ever since the 1500s, when an unknown printer took a galley of it to make.'}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Col 2: INFORMATION */}
                <div className="site-footer-middle-section-2 site-footer-section footer-section-inner-items-1">
                  <div className="footer-widget-area widget-area site-footer-focus-item footer-widget2 content-align-default content-tablet-align-default content-mobile-align-default content-valign-default content-tablet-valign-default content-mobile-valign-default">
                    <div className="footer-widget-area-inner site-info-inner">
                      <h2 className="widget-title">INFORMATION</h2>
                      <ul className="wp-block-list">
                        {pages.length > 0
                          ? pages.map(p => (
                              <li key={p.id}><a href={`/pages/${p.slug}`}>{p.title}</a></li>
                            ))
                          : <>
                              <li><a href="/pages/contacto">Contact Us</a></li>
                              <li><a href="/pages/nosotros">About Us</a></li>
                            </>
                        }
                      </ul>
                    </div>
                  </div>
                </div>

                {/* Col 3: OUR SERVICES */}
                <div className="site-footer-middle-section-3 site-footer-section footer-section-inner-items-1">
                  <div className="footer-widget-area widget-area site-footer-focus-item footer-widget3 content-align-default content-tablet-align-default content-mobile-align-default content-valign-default content-tablet-valign-default content-mobile-valign-default">
                    <div className="footer-widget-area-inner site-info-inner">
                      <h2 className="widget-title">OUR SERVICES</h2>
                      <ul className="wp-block-list">
                        {categories.length > 0
                          ? categories.map(c => (
                              <li key={c.id}><a href={`/categories/${c.slug}`}>{c.name}</a></li>
                            ))
                          : ['Return Policy', 'Terms Of Use', 'Security', 'Privacy', 'Sitemap'].map(t => (
                              <li key={t}><a href={`/pages/${t.toLowerCase().replace(/\s+/g, '-')}`}>{t}</a></li>
                            ))
                        }
                      </ul>
                    </div>
                  </div>
                </div>

                {/* Col 4: CONTACT US */}
                <div className="site-footer-middle-section-4 site-footer-section footer-section-inner-items-1">
                  <div className="footer-widget-area widget-area site-footer-focus-item footer-widget4 content-align-default content-tablet-align-default content-mobile-align-default content-valign-default content-tablet-valign-default content-mobile-valign-default">
                    <div className="footer-widget-area-inner site-info-inner">
                      <h2 className="widget-title">CONTACT US</h2>
                      <div className="contact-info-container">
                        <p className="address">
                          <span className="contact-icon"><IconPin /></span>
                          {info?.address
                            ? `${info.address}${info.zip_code ? `, CP ${info.zip_code}` : ''}`
                            : '99 New Theme St. XY, USA 12345, Beside the Sun point land.'}
                        </p>
                        <p className="phone">
                          <span className="contact-icon"><IconPhone /></span>
                          <a href={`tel:${info?.phone || '+00123456789'}`}>
                            {info?.phone || '+00 123-456-789'}
                          </a>
                        </p>
                        <p className="email">
                          <span className="contact-icon"><IconEmail /></span>
                          <a href={`mailto:${info?.email || 'demo@example.com'}`}>
                            {info?.email || 'demo@example.com'}
                          </a>
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        {/* ── Bottom row: copyright + payment ──────────────────────────── */}
        <div className="site-bottom-footer-wrap site-footer-row-container site-footer-focus-item site-footer-row-layout-standard site-footer-row-tablet-layout-default site-footer-row-mobile-layout-default">
          <div className="site-footer-row-container-inner">
            <div className="site-container">
              <div className="site-bottom-footer-inner-wrap site-footer-row site-footer-row-columns-2 site-footer-row-column-layout-row site-footer-row-tablet-column-layout-default site-footer-row-mobile-column-layout-row ft-ro-dir-row ft-ro-collapse-normal ft-ro-t-dir-default ft-ro-m-dir-default ft-ro-lstyle-noline">

                {/* Left: copyright */}
                <div className="site-footer-bottom-section-1 site-footer-section footer-section-inner-items-1">
                  <div className="footer-widget-area site-info site-footer-focus-item content-align-default content-tablet-align-center content-mobile-align-default content-valign-default content-tablet-valign-default content-mobile-valign-default">
                    <div className="footer-widget-area-inner site-info-inner">
                      <div className="footer-html inner-link-style-normal">
                        <div className="footer-html-inner">
                          <p>&copy; {year} Moira Bikinis</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Right: payment methods */}
                <div className="site-footer-bottom-section-2 site-footer-section footer-section-inner-items-1">
                  <div className="footer-widget-area widget-area site-footer-focus-item footer-widget6 content-align-right content-tablet-align-center content-mobile-align-default content-valign-default content-tablet-valign-default content-mobile-valign-default">
                    <div className="footer-widget-area-inner site-info-inner">
                      <section className="widget widget_block widget_media_image">
                        <figure className="wp-block-image size-full">
                          <img src="/payment.png" alt="Métodos de pago" width={282} height={25} />
                        </figure>
                      </section>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

      </div>
    </footer>
  );
}
