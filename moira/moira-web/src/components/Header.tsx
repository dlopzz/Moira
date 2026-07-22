'use client';

import { useEffect, useMemo, useState } from 'react';
import { imageUrl, type Category } from '@/lib/api';
import { getToken } from '@/lib/auth';
import { useCart } from '@/lib/cart-context';
import { useSiteInfo } from '@/lib/site-info-context';
import { useCategories } from '@/lib/categories-context';
import LoginDrawer from '@/components/LoginDrawer';

const ArrowDown = () => (
  <span className="dropdown-nav-toggle">
    <span className="base-svg-iconset svg-baseline">
      <svg aria-hidden="true" className="base-svg-icon base-arrow-down-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
        <title>Expand</title><path d="M5.293 9.707l6 6c0.391 0.391 1.024 0.391 1.414 0l6-6c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-5.293 5.293-5.293-5.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414z"/>
      </svg>
    </span>
  </span>
);

const IconEmail = () => (
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" className="contact-icon-image">
    <path d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
  </svg>
);

const ChevronSvg = () => (
  <span className="base-svg-iconset">
    <svg aria-hidden="true" className="base-svg-icon base-arrow-down-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
      <title>Expand</title><path d="M5.293 9.707l6 6c0.391 0.391 1.024 0.391 1.414 0l6-6c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-5.293 5.293-5.293-5.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414z"/>
    </svg>
  </span>
);

function MobileNavItem({
  item, expanded, onToggle, depth = 0,
}: {
  item: Category; expanded: Set<number>; onToggle: (id: number) => void; depth?: number;
}) {
  const children = item.children ?? [];
  const isExp = expanded.has(item.id);
  return (
    <li className={`menu-item${children.length > 0 ? ' menu-item-has-children' : ''}`}>
      {children.length > 0 ? (
        <div className="drawer-nav-drop-wrap">
          <a href={`/categories/${item.slug}`}>{item.name}</a>
          <button className="drawer-sub-toggle" onClick={() => onToggle(item.id)} aria-expanded={isExp}>
            <span className="screen-reader-text">Toggle child menu</span>
            <ChevronSvg />
          </button>
        </div>
      ) : (
        <a href={`/categories/${item.slug}`}>{item.name}</a>
      )}
      {children.length > 0 && (
        <ul className={`sub-menu${isExp ? ' show-drawer' : ''}`}>
          {children.map(child => (
            <MobileNavItem key={child.id} item={child} expanded={expanded} onToggle={onToggle} depth={depth + 1} />
          ))}
          {depth === 0 && item.image_url && (
            <li className="menu-item mobile-nav-cat-image-item">
              <img src={item.image_url} alt={item.name} className="mobile-nav-cat-image" />
            </li>
          )}
        </ul>
      )}
    </li>
  );
}

export default function Header() {
  const { cart, toggleCart } = useCart();
  const cartCount = cart?.summary.items_count ?? 0;
  const rawCategories = useCategories();
  const categories = useMemo(
    () => rawCategories?.[0]?.children ?? rawCategories ?? [],
    [rawCategories]
  );
  const siteInfo = useSiteInfo();
  const [drawerVisible, setDrawerVisible] = useState(false);
  const [drawerOpen, setDrawerOpen]     = useState(false);
  const [expanded, setExpanded]         = useState<Set<number>>(new Set());
  const [isLoggedIn, setIsLoggedIn]     = useState(false);
  const [loginDrawerVisible, setLoginDrawerVisible] = useState(false);
  const [loginDrawerOpen, setLoginDrawerOpen]       = useState(false);

  useEffect(() => {
    setIsLoggedIn(!!getToken());
  }, []);

  useEffect(() => {
    const positionMegaMenus = () => {
      const subMenus = document.querySelectorAll<HTMLElement>(
        '.site-header-wrap .base-menu-mega-width-content > ul.sub-menu'
      );
      subMenus.forEach(sub => {
        const parent = sub.parentElement;
        const row = sub.closest<HTMLElement>('.site-header-row');
        if (!parent || !row) return;
        sub.style.left = '';
        sub.style.width = row.offsetWidth + 'px';
        sub.style.left = -Math.abs(parent.getBoundingClientRect().left - row.getBoundingClientRect().left) + 'px';
      });
    };
    let timer: ReturnType<typeof setTimeout>;
    window.addEventListener('resize', () => { clearTimeout(timer); timer = setTimeout(positionMegaMenus, 500); });
    window.addEventListener('load', () => { clearTimeout(timer); timer = setTimeout(positionMegaMenus, 100); });
    positionMegaMenus();
    return () => window.removeEventListener('resize', positionMegaMenus);
  }, [categories]);

  const openDrawer = () => {
    setDrawerVisible(true);
    requestAnimationFrame(() => requestAnimationFrame(() => setDrawerOpen(true)));
    document.body.style.overflow = 'hidden';
  };

  const closeDrawer = () => {
    setDrawerOpen(false);
    setTimeout(() => setDrawerVisible(false), 300);
    document.body.style.overflow = '';
  };

  const openLoginDrawer = () => {
    setLoginDrawerVisible(true);
    requestAnimationFrame(() => requestAnimationFrame(() => setLoginDrawerOpen(true)));
    document.body.style.overflow = 'hidden';
  };

  const closeLoginDrawer = () => {
    setLoginDrawerOpen(false);
    setTimeout(() => setLoginDrawerVisible(false), 300);
    document.body.style.overflow = '';
  };

  const handleAccountClick = () => {
    if (isLoggedIn) {
      window.location.href = '/profile';
    } else {
      openLoginDrawer();
    }
  };

  const toggleItem = (id: number) => {
    setExpanded(prev => {
      const next = new Set(prev);
      next.has(id) ? next.delete(id) : next.add(id);
      return next;
    });
  };

  return (
    <>
      <header id="masthead" className="site-header" role="banner">
        <div id="main-header" className="site-header-wrap">
          <div className="site-header-inner-wrap">
            <div className="site-header-upper-wrap">
              <div className="site-header-upper-inner-wrap">

                {/* ROW 1: Top bar */}
                <div className="site-top-header-wrap site-header-row-container site-header-focus-item site-header-row-layout-fullwidth">
                  <div className="site-header-row-container-inner">
                    <div className="site-container">
                      <div className="site-top-header-inner-wrap site-header-row site-header-row-has-sides site-header-row-no-center">

                        {/* Left: social icons */}
                        <div className="site-header-top-section-left site-header-section site-header-section-left">
                          <div className="site-header-item site-header-focus-item">
                            <div className="header-social-wrap">
                              <div className="header-social-inner-wrap element-social-inner-wrap social-show-label-false social-style-outline">
                                {siteInfo?.social_facebook && (
                                  <a href={siteInfo.social_facebook} aria-label="Facebook" target="_blank" rel="noopener noreferrer" className="social-button header-social-item social-link-facebook">
                                    <span className="base-svg-iconset">
                                      <svg className="base-svg-icon base-facebook-alt2-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="16" height="28" viewBox="0 0 16 28"><title>Facebook</title><path d="M14.984 0.187v4.125h-2.453c-1.922 0-2.281 0.922-2.281 2.25v2.953h4.578l-0.609 4.625h-3.969v11.859h-4.781v-11.859h-3.984v-4.625h3.984v-3.406c0-3.953 2.422-6.109 5.953-6.109 1.687 0 3.141 0.125 3.563 0.187z"/></svg>
                                    </span>
                                  </a>
                                )}
                                {siteInfo?.social_instagram && (
                                  <a href={siteInfo.social_instagram} aria-label="Instagram" target="_blank" rel="noopener noreferrer" className="social-button header-social-item social-link-instagram">
                                    <span className="base-svg-iconset">
                                      <svg className="base-svg-icon base-instagram-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><title>Instagram</title><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                    </span>
                                  </a>
                                )}
                                {siteInfo?.social_whatsapp && (
                                  <a href={siteInfo.social_whatsapp} aria-label="WhatsApp" target="_blank" rel="noopener noreferrer" className="social-button header-social-item social-link-whatsapp">
                                    <span className="base-svg-iconset">
                                      <svg className="base-svg-icon base-whatsapp-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><title>WhatsApp</title><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    </span>
                                  </a>
                                )}
                              </div>
                            </div>
                          </div>
                        </div>

                        {/* Right: promo text */}
                        {siteInfo?.promo_text && (
                          <div className="site-header-top-section-right site-header-section site-header-section-right">
                            <div className="site-header-item site-header-focus-item">
                              <div className="header-html inner-link-style-normal">
                                <div className="header-html-inner">
                                  <p>{siteInfo.promo_text}</p>
                                </div>
                              </div>
                            </div>
                          </div>
                        )}

                      </div>
                    </div>
                  </div>
                </div>

                {/* ROW 2: Main nav */}
                <div className="site-main-header-wrap site-header-row-container site-header-focus-item site-header-row-layout-fullwidth">
                  <div className="site-header-row-container-inner">
                    <div className="site-container">
                      <div className="site-main-header-inner-wrap site-header-row site-header-row-has-sides site-header-row-no-center">

                        {/* Left: logo + nav */}
                        <div className="site-header-main-section-left site-header-section site-header-section-left">
                          <div className="site-header-item site-header-focus-item" data-section="title_tagline">
                            <div className="site-branding branding-layout-standard site-brand-logo-only">
                              <a className="brand" href="/" rel="home">
                                {siteInfo === undefined ? (
                                  <div style={{ width: 156, height: 35 }} />
                                ) : (
                                  <img
                                    src={imageUrl(siteInfo?.logo) ?? '/logo.svg'}
                                    className="logo"
                                    alt={siteInfo?.name ?? 'Logo'}
                                    style={{ height: '35px', width: 'auto' }}
                                  />
                                )}
                              </a>
                            </div>
                          </div>
                          <div className="site-header-item site-header-focus-item site-header-item-main-navigation header-navigation-layout-stretch-false">
                            <nav id="site-navigation" className="main-navigation header-navigation nav--toggle-sub header-navigation-style-standard header-navigation-dropdown-animation-fade-up" role="navigation" aria-label="Primary Navigation">
                              <div className="primary-menu-container header-menu-container">
                                <ul id="primary-menu" className="menu">
                                  {categories.map((cat) => {
                                    const children = cat.children ?? [];
                                    const showImage = children.length > 0 && !!cat.image_url;
                                    const cols = Math.min(children.length + (showImage ? 1 : 0), 6);
                                    const hasDrop = children.length > 0;
                                    return (
                                      <li key={cat.id} id={`menu-item-cat-${cat.id}`}
                                        className={`menu-item menu-item-type-taxonomy menu-item-object-product_cat${hasDrop ? ` menu-item-has-children base-menu-mega-enabled base-menu-mega-width-content base-menu-mega-columns-${cols} base-menu-mega-layout-equal` : ''}`}>
                                        <a href={`/categories/${cat.slug}`}>
                                          <span className="nav-drop-title-wrap">{cat.name}{hasDrop && <ArrowDown />}</span>
                                        </a>
                                        {hasDrop && (
                                          <ul className="sub-menu">
                                            {children.map(child => {
                                              const grandchildren = child.children ?? [];
                                              return (
                                                <li key={child.id} className={`menu-item${grandchildren.length > 0 ? ' menu-item-has-children' : ''}`}>
                                                  <a href={`/categories/${child.slug}`}>
                                                    <span className="nav-drop-title-wrap">{child.name}</span>
                                                  </a>
                                                  {grandchildren.length > 0 && (
                                                    <ul className="sub-menu">
                                                      {grandchildren.map(gc => (
                                                        <li key={gc.id} className="menu-item">
                                                          <a href={`/categories/${gc.slug}`}>{gc.name}</a>
                                                        </li>
                                                      ))}
                                                    </ul>
                                                  )}
                                                </li>
                                              );
                                            })}
                                            {showImage && (
                                              <li className="menu-item mega-menu-image-col">
                                                <img src={cat.image_url!} alt={cat.name} className="mega-menu-image" />
                                              </li>
                                            )}
                                          </ul>
                                        )}
                                      </li>
                                    );
                                  })}
                                </ul>
                              </div>
                            </nav>
                          </div>
                        </div>

                        {/* Right: divider + account + cart */}
                        <div className="site-header-main-section-right site-header-section site-header-section-right">
                          <div className="site-header-item site-header-focus-item">
                            <div className="header-divider2" />
                          </div>
                          <div className="site-header-item site-header-focus-item">
                            <div className="header-account-wrap header-account-control-wrap header-account-action-modal header-account-style-icon_title_label">
                              <button className="drawer-toggle header-account-button" onClick={handleAccountClick} aria-expanded={loginDrawerOpen}>
                                <span className="base-svg-iconset">
                                  <svg className="thebase-svg-icon thebase-account-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 680 800"><title>Account</title><path d="M620,771c.66-8.78,1.16-17.58,2-26.34,2.66-27.06,9.23-52.82,26.25-74.82a94.58,94.58,0,0,1,51-34.12c48.28-13.21,92.92-34.28,134.68-61.67,14.65-9.61,32.71-2.6,35.6,14.15,1.71,9.88-2.21,17.94-10.77,22.89-22.17,12.82-44.22,25.94-67,37.57-25.38,13-52.27,22.29-79.78,29.9-22.87,6.32-35.15,22.94-41,44.92-6.8,25.59-6.63,51.76-5.49,77.94.29,6.64,1.31,13.26,1.76,19.9.3,4.43,2.41,6.94,6.38,9,31.24,16.29,64,28.78,97.75,38.67,71.15,20.83,143.87,28.82,217.78,25.83,83-3.36,163-20,238.85-54.79,7.25-3.33,14.34-7,21.36-10.84,1.52-.82,3.32-2.63,3.52-4.18,4.59-34.24,5.48-68.5-3.14-102.23-6.07-23.72-20.74-39.68-45.4-45.54a266.78,266.78,0,0,1-26.66-8.3,515.26,515.26,0,0,1-115.4-57.55c-15.5-10.35-14.87-31.22,1-39.22,8.34-4.19,16.3-2.92,24.05,2.21a480.55,480.55,0,0,0,104.25,52.07c8.3,3,16.69,5.74,25.21,7.94,39.41,10.19,63.84,35.68,75.05,74.08,3.52,12.06,4.84,24.77,7.17,37.17.28,1.46.62,2.9.93,4.35v63c-1,6.89-2.19,13.76-3,20.68-1.75,15.07-8,26.63-22.72,33.08-20.71,9.06-40.68,19.92-61.68,28.18-64.18,25.26-131.08,38.56-199.78,43.23-8,.54-15.89,1.22-23.83,1.83H933c-2-.27-3.92-.72-5.88-.8a707.29,707.29,0,0,1-146.88-21.31c-46.87-12-92-28.55-134.57-51.86-11.26-6.16-18.36-14.38-20.33-27-.75-4.76-1.84-9.48-2.36-14.26-1.13-10.56-2-21.16-3-31.74Z" transform="translate(-620 -140)"/><path d="M973,140c9.52,1.49,19.16,2.42,28.53,4.55,66.23,15.08,108.45,55.84,123.16,121.82,14.84,66.59,12,133.13-11.71,197.77-12.17,33.11-31.8,61-61.49,81.06-24,16.21-50.81,23.83-79.38,25.5-32,1.86-62.82-3-91.33-18.44-33.85-18.39-56.07-47-70.71-82-16.15-38.52-22-79.06-22.28-120.53-.19-32.38,1.23-64.7,11.25-95.86,20.14-62.69,63.42-99,127.76-111,6.68-1.23,13.46-2,20.2-2.94ZM831.94,343.33c.48,34.44,3.77,66,14,96.46,5.33,15.88,12.53,30.81,22.68,44.22,19.13,25.28,44.87,38.67,76.13,41.46,33,2.95,64.24-1.82,90.68-24,16.62-13.91,27.59-31.84,35.58-51.75,10-24.82,14.7-50.79,16.45-77.33,2.09-31.69,1.57-63.36-5.63-94.44-8.87-38.25-29.78-67.32-67.07-82.59-23.44-9.6-48-12.44-73-9.42-48.32,5.81-82.25,30.36-98.78,77C833.46,289.8,832.89,317.84,831.94,343.33Z" transform="translate(-620 -140)"/></svg>
                                </span>
                                <div className="header-account-content">
                                  <span className="header-account-title">CUENTA</span>
                                </div>
                              </button>
                            </div>
                          </div>
                          <div className="site-header-item site-header-focus-item">
                            <div className="header-cart-wrap base-header-cart">
                              <span className="header-cart-empty-check header-cart-is-empty-true"></span>
                              <div className="header-cart-inner-wrap cart-show-label-true cart-style-slide">
                                <button className="header-cart-button" onClick={toggleCart} aria-label="Abrir carrito">
                                  <span className="base-svg-iconset">
                                    <svg className="thebase-svg-icon thebase-shopping-cart-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="0.5" viewBox="0 0 639 800"><title>Shopping Cart</title><path d="M926.19,672.43c2-2.86,2.93-4.71,4.32-6.1q55.41-55.55,110.89-111c6.14-6.16,13.14-9,21.71-6.38a19.59,19.59,0,0,1,9.92,30.72,48.17,48.17,0,0,1-4.39,4.77q-63,63.11-126.12,126.18c-11.24,11.23-22,11.21-33.29-.05q-29-29-58-57.94c-5.29-5.26-8.25-11.32-6.86-18.86s6-13,13.33-15.47c7.83-2.63,14.83-.64,20.63,5.13q21.63,21.48,43.11,43.11C922.76,667.81,923.78,669.38,926.19,672.43Z" transform="translate(-641 -140)"/><path d="M1280,828v17c-1.56,6.42-2.56,13-4.76,19.22-13.84,38.83-42.54,61.08-81.29,71.7-6.84,1.87-14,2.74-21,4.08H748c-7-1.34-14.1-2.22-21-4.08-37.83-10.29-66.14-31.87-80.48-69.35-2.63-6.9-3.75-14.37-5.57-21.57V828c1.29-12.55,2.74-25.09,3.86-37.66,4.18-46.94,8.21-93.89,12.38-140.83q8.25-92.79,16.6-185.58,6.51-72.9,12.95-145.79c1.39-15.45,8.38-21.69,24.06-21.7q43.47,0,87,0c1.92,0,3.85-.16,5.79-.25.42-5.48.7-10.45,1.21-15.39A156.33,156.33,0,0,1,918.75,145.83c9.56-2.64,19.49-3.92,29.25-5.83h24a35.13,35.13,0,0,0,3.87.83,156.57,156.57,0,0,1,139.22,131.6c1.21,7.83,1.71,15.77,2.58,24h48.07c15.66,0,31.32-.14,47,.05,13,.16,20.36,7.3,21.39,20.14.65,8.13,1.41,16.26,2.14,24.39q6.81,75.87,13.6,151.75,6.75,76.14,13.38,152.28,6.91,78.38,13.93,156.74C1277.94,810.54,1279,819.27,1280,828ZM725.24,336.45c-.16,1.35-.28,2.17-.35,3l-7.29,81.62q-4.79,53.74-9.59,107.5-4.23,47.53-8.4,95.06-4.79,54-9.62,108c-2.83,31.86-5.32,63.75-8.63,95.55-2,18.74,3.32,34.62,16.2,47.94C714.5,892.65,735.77,900,759.77,900q200.72.07,401.43-.06a95.46,95.46,0,0,0,20.31-2.09c22.06-4.85,40.1-15.83,51.73-35.88,7.14-12.3,7.3-25.47,6-39.22-3.52-35.63-6.38-71.33-9.55-107q-5.23-59-10.56-118-4.74-53.5-9.4-107-5.34-60.46-10.75-120.94c-1-11.07-2-22.14-3.07-33.24h-78.79v5.89c0,21.17.12,42.33-.07,63.49a19.67,19.67,0,0,1-25.52,18.94c-8.76-2.62-14.14-10.26-14.18-20.65-.09-20.66,0-41.33,0-62,0-1.92-.17-3.84-.27-5.78H843.73v5.73c0,20.16.06,40.32-.06,60.49a33,33,0,0,1-1.33,9.81,19.59,19.59,0,0,1-21.16,13.13c-9.85-1.55-17.08-9.29-17.16-19.13-.19-21.49-.08-43-.09-64.48v-5.61ZM843.5,296.1h233.19c4.33-32.1-19.35-76.72-53-97.52-41.07-25.4-83.45-26.14-124.64-1C862.94,219.65,845.26,253.36,843.5,296.1Z" transform="translate(-641 -140)"/></svg>
                                  </span>
                                  <span className="header-cart-total">{cartCount}</span>
                                  <div className="header-cart-content">
                                    <span className="header-cart-title">CARRITO</span>
                                  </div>
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>

                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            {/* ROW 3: Bottom bar */}
            <div className="site-bottom-header-wrap site-header-row-container site-header-focus-item site-header-row-layout-fullwidth">
              <div className="site-header-row-container-inner">
                <div className="site-container">
                  <div className="site-bottom-header-inner-wrap site-header-row site-header-row-has-sides site-header-row-no-center">
                    <div className="site-header-bottom-section-left site-header-section site-header-section-left" />
                    <div className="site-header-bottom-section-right site-header-section site-header-section-right">
                      {(siteInfo?.phone || siteInfo?.email) && (
                        <div className="site-header-item site-header-focus-item">
                          <div className="header-contact-wrap">
                            <div className="header-contact-inner-wrap element-contact-inner-wrap inner-link-style-plain">
                              <a
                                className="contact-button header-contact-item has-custom-image"
                                href={siteInfo?.phone ? `tel:${siteInfo.phone}` : `mailto:${siteInfo?.email}`}
                              >
                                {siteInfo?.phone ? (
                                  <img src="/phone.png" alt="" className="contact-icon-image" />
                                ) : (
                                  <IconEmail />
                                )}
                                <div className="contact-content">
                                  <span className="contact-title">ATENCIÓN AL CLIENTE : </span>
                                  <span className="contact-label">&nbsp;{siteInfo?.phone || siteInfo?.email}</span>
                                </div>
                              </a>
                            </div>
                          </div>
                        </div>
                      )}
                      <div className="site-header-item site-header-focus-item">
                        <div className="header-search-advanced header-item-search-advanced">
                          <form role="search" method="get" action="/search" className="search-form">
                            <div className="input-container">
                              <input type="search" className="search-field" placeholder="Buscar productos…" name="q" autoComplete="off" />
                            </div>
                            <button type="submit" className="search-submit">
                              <span className="search-btn-icon">
                                <span className="base-svg-iconset">
                                  <svg className="thebase-svg-icon thebase-search2-svg" stroke="currentColor" strokeWidth="0px" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><title>Search</title><path d="M16.041 15.856c-0.034 0.026-0.067 0.055-0.099 0.087s-0.060 0.064-0.087 0.099c-1.258 1.213-2.969 1.958-4.855 1.958-1.933 0-3.682-0.782-4.95-2.050s-2.050-3.017-2.050-4.95 0.782-3.682 2.050-4.95 3.017-2.050 4.95-2.050 3.682 0.782 4.95 2.050 2.050 3.017 2.050 4.95c0 1.886-0.745 3.597-1.959 4.856zM21.707 20.293l-3.675-3.675c1.231-1.54 1.968-3.493 1.968-5.618 0-2.485-1.008-4.736-2.636-6.364s-3.879-2.636-6.364-2.636-4.736 1.008-6.364 2.636-2.636 3.879-2.636 6.364 1.008 4.736 2.636 6.364 3.879 2.636 6.364 2.636c2.125 0 4.078-0.737 5.618-1.968l3.675 3.675c0.391 0.391 1.024 0.391 1.414 0s0.391-1.024 0-1.414z"/></svg>
                                </span>
                              </span>
                            </button>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        {/* ── MOBILE HEADER ── */}
        <div id="mobile-header" className="site-mobile-header-wrap">
          <div className="site-header-inner-wrap">
            <div className="site-header-upper-wrap">
              <div className="site-header-upper-inner-wrap">

                {/* Mobile top bar: centered promo text */}
                {siteInfo?.promo_text && (
                <div className="site-top-header-wrap site-header-row-container site-header-focus-item site-header-row-layout-fullwidth">
                  <div className="site-header-row-container-inner">
                    <div className="site-container">
                      <div className="site-top-header-inner-wrap site-header-row site-header-row-only-center-column site-header-row-center-column">
                        <div className="site-header-top-section-center site-header-section site-header-section-center">
                          <div className="site-header-item site-header-focus-item">
                            <div className="mobile-html">
                              <div className="mobile-html-inner">{siteInfo.promo_text}</div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                )}

                {/* Mobile main bar: hamburger + logo | search + account + cart */}
                <div className="site-main-header-wrap site-header-row-container site-header-focus-item site-header-row-layout-fullwidth">
                  <div className="site-header-row-container-inner">
                    <div className="site-container">
                      <div className="site-main-header-inner-wrap site-header-row site-header-row-has-sides site-header-row-no-center">

                        {/* Left: hamburger + logo */}
                        <div className="site-header-main-section-left site-header-section site-header-section-left">
                          <div className="site-header-item site-header-focus-item">
                            <div className="mobile-toggle-open-container">
                              <button
                                className="menu-toggle-open menu-toggle-style-default"
                                aria-label="Open menu"
                                aria-expanded={drawerOpen}
                                onClick={openDrawer}
                              >
                                <span className="menu-toggle-icon">
                                  <span className="base-svg-iconset">
                                    <svg aria-hidden="true" className="base-svg-icon base-menu-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                      <title>Toggle Menu</title>
                                      <path d="M3 13h18c0.552 0 1-0.448 1-1s-0.448-1-1-1h-18c-0.552 0-1 0.448-1 1s0.448 1 1 1zM3 7h18c0.552 0 1-0.448 1-1s-0.448-1-1-1h-18c-0.552 0-1 0.448-1 1s0.448 1 1 1zM3 19h18c0.552 0 1-0.448 1-1s-0.448-1-1-1h-18c-0.552 0-1 0.448-1 1s0.448 1 1 1z" />
                                    </svg>
                                  </span>
                                </span>
                              </button>
                            </div>
                          </div>
                          <div className="site-header-item site-header-focus-item">
                            <div className="site-branding mobile-site-branding branding-layout-standard site-brand-logo-only">
                              <a className="brand" href="/" rel="home">
                                {siteInfo === undefined ? (
                                  <div style={{ width: 156, height: 35 }} />
                                ) : (
                                  <img
                                    src={imageUrl(siteInfo?.logo) ?? '/logo.svg'}
                                    className="logo"
                                    alt={siteInfo?.name ?? 'Logo'}
                                    style={{ height: '35px', width: 'auto' }}
                                  />
                                )}
                              </a>
                            </div>
                          </div>
                        </div>

                        {/* Right: search + account + cart */}
                        <div className="site-header-main-section-right site-header-section site-header-section-right">
                          <div className="site-header-item site-header-focus-item">
                            <div className="search-toggle-open-container">
                              <button className="search-toggle-open search-toggle-style-default" aria-label="View Search Form">
                                <span className="search-toggle-icon">
                                  <span className="base-svg-iconset">
                                    <svg aria-hidden="true" className="base-svg-icon base-search-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                      <title>Search</title>
                                      <path d="M16.041 15.856c-0.034 0.026-0.067 0.055-0.099 0.087s-0.060 0.064-0.087 0.099c-1.258 1.213-2.969 1.958-4.855 1.958-1.933 0-3.682-0.782-4.95-2.050s-2.050-3.017-2.050-4.95 0.782-3.682 2.050-4.95 3.017-2.050 4.95-2.050 3.682 0.782 4.95 2.050 2.050 3.017 2.050 4.95c0 1.886-0.745 3.597-1.959 4.856zM21.707 20.293l-3.675-3.675c1.231-1.54 1.968-3.493 1.968-5.618 0-2.485-1.008-4.736-2.636-6.364s-3.879-2.636-6.364-2.636-4.736 1.008-6.364 2.636-2.636 3.879-2.636 6.364 1.008 4.736 2.636 6.364 3.879 2.636 6.364 2.636c2.125 0 4.078-0.737 5.618-1.968l3.675 3.675c0.391 0.391 1.024 0.391 1.414 0s0.391-1.024 0-1.414z"/>
                                    </svg>
                                  </span>
                                </span>
                              </button>
                            </div>
                          </div>
                          <div className="site-header-item site-header-focus-item">
                            <div className="header-mobile-account-wrap header-account-control-wrap header-account-action-link header-account-style-icon">
                              <button className="header-account-button" onClick={handleAccountClick} aria-expanded={loginDrawerOpen}>
                                <span className="base-svg-iconset">
                                  <svg className="thebase-svg-icon thebase-account-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 680 800"><title>Account</title><path d="M620,771c.66-8.78,1.16-17.58,2-26.34,2.66-27.06,9.23-52.82,26.25-74.82a94.58,94.58,0,0,1,51-34.12c48.28-13.21,92.92-34.28,134.68-61.67,14.65-9.61,32.71-2.6,35.6,14.15,1.71,9.88-2.21,17.94-10.77,22.89-22.17,12.82-44.22,25.94-67,37.57-25.38,13-52.27,22.29-79.78,29.9-22.87,6.32-35.15,22.94-41,44.92-6.8,25.59-6.63,51.76-5.49,77.94.29,6.64,1.31,13.26,1.76,19.9.3,4.43,2.41,6.94,6.38,9,31.24,16.29,64,28.78,97.75,38.67,71.15,20.83,143.87,28.82,217.78,25.83,83-3.36,163-20,238.85-54.79,7.25-3.33,14.34-7,21.36-10.84,1.52-.82,3.32-2.63,3.52-4.18,4.59-34.24,5.48-68.5-3.14-102.23-6.07-23.72-20.74-39.68-45.4-45.54a266.78,266.78,0,0,1-26.66-8.3,515.26,515.26,0,0,1-115.4-57.55c-15.5-10.35-14.87-31.22,1-39.22,8.34-4.19,16.3-2.92,24.05,2.21a480.55,480.55,0,0,0,104.25,52.07c8.3,3,16.69,5.74,25.21,7.94,39.41,10.19,63.84,35.68,75.05,74.08,3.52,12.06,4.84,24.77,7.17,37.17.28,1.46.62,2.9.93,4.35v63c-1,6.89-2.19,13.76-3,20.68-1.75,15.07-8,26.63-22.72,33.08-20.71,9.06-40.68,19.92-61.68,28.18-64.18,25.26-131.08,38.56-199.78,43.23-8,.54-15.89,1.22-23.83,1.83H933c-2-.27-3.92-.72-5.88-.8a707.29,707.29,0,0,1-146.88-21.31c-46.87-12-92-28.55-134.57-51.86-11.26-6.16-18.36-14.38-20.33-27-.75-4.76-1.84-9.48-2.36-14.26-1.13-10.56-2-21.16-3-31.74Z" transform="translate(-620 -140)"/><path d="M973,140c9.52,1.49,19.16,2.42,28.53,4.55,66.23,15.08,108.45,55.84,123.16,121.82,14.84,66.59,12,133.13-11.71,197.77-12.17,33.11-31.8,61-61.49,81.06-24,16.21-50.81,23.83-79.38,25.5-32,1.86-62.82-3-91.33-18.44-33.85-18.39-56.07-47-70.71-82-16.15-38.52-22-79.06-22.28-120.53-.19-32.38,1.23-64.7,11.25-95.86,20.14-62.69,63.42-99,127.76-111,6.68-1.23,13.46-2,20.2-2.94ZM831.94,343.33c.48,34.44,3.77,66,14,96.46,5.33,15.88,12.53,30.81,22.68,44.22,19.13,25.28,44.87,38.67,76.13,41.46,33,2.95,64.24-1.82,90.68-24,16.62-13.91,27.59-31.84,35.58-51.75,10-24.82,14.7-50.79,16.45-77.33,2.09-31.69,1.57-63.36-5.63-94.44-8.87-38.25-29.78-67.32-67.07-82.59-23.44-9.6-48-12.44-73-9.42-48.32,5.81-82.25,30.36-98.78,77C833.46,289.8,832.89,317.84,831.94,343.33Z" transform="translate(-620 -140)"/></svg>
                                </span>
                              </button>
                            </div>
                          </div>
                          <div className="site-header-item site-header-focus-item">
                            <div className="header-mobile-cart-wrap base-header-cart">
                              <div className="header-cart-inner-wrap">
                                <button className="header-cart-button" onClick={toggleCart} aria-label="Abrir carrito">
                                  <span className="base-svg-iconset">
                                    <svg className="thebase-svg-icon thebase-shopping-cart-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="0.5" viewBox="0 0 639 800"><title>Shopping Cart</title><path d="M926.19,672.43c2-2.86,2.93-4.71,4.32-6.1q55.41-55.55,110.89-111c6.14-6.16,13.14-9,21.71-6.38a19.59,19.59,0,0,1,9.92,30.72,48.17,48.17,0,0,1-4.39,4.77q-63,63.11-126.12,126.18c-11.24,11.23-22,11.21-33.29-.05q-29-29-58-57.94c-5.29-5.26-8.25-11.32-6.86-18.86s6-13,13.33-15.47c7.83-2.63,14.83-.64,20.63,5.13q21.63,21.48,43.11,43.11C922.76,667.81,923.78,669.38,926.19,672.43Z" transform="translate(-641 -140)"/><path d="M1280,828v17c-1.56,6.42-2.56,13-4.76,19.22-13.84,38.83-42.54,61.08-81.29,71.7-6.84,1.87-14,2.74-21,4.08H748c-7-1.34-14.1-2.22-21-4.08-37.83-10.29-66.14-31.87-80.48-69.35-2.63-6.9-3.75-14.37-5.57-21.57V828c1.29-12.55,2.74-25.09,3.86-37.66,4.18-46.94,8.21-93.89,12.38-140.83q8.25-92.79,16.6-185.58,6.51-72.9,12.95-145.79c1.39-15.45,8.38-21.69,24.06-21.7q43.47,0,87,0c1.92,0,3.85-.16,5.79-.25.42-5.48.7-10.45,1.21-15.39A156.33,156.33,0,0,1,918.75,145.83c9.56-2.64,19.49-3.92,29.25-5.83h24a35.13,35.13,0,0,0,3.87.83,156.57,156.57,0,0,1,139.22,131.6c1.21,7.83,1.71,15.77,2.58,24h48.07c15.66,0,31.32-.14,47,.05,13,.16,20.36,7.3,21.39,20.14.65,8.13,1.41,16.26,2.14,24.39q6.81,75.87,13.6,151.75,6.75,76.14,13.38,152.28,6.91,78.38,13.93,156.74C1277.94,810.54,1279,819.27,1280,828ZM725.24,336.45c-.16,1.35-.28,2.17-.35,3l-7.29,81.62q-4.79,53.74-9.59,107.5-4.23,47.53-8.4,95.06-4.79,54-9.62,108c-2.83,31.86-5.32,63.75-8.63,95.55-2,18.74,3.32,34.62,16.2,47.94C714.5,892.65,735.77,900,759.77,900q200.72.07,401.43-.06a95.46,95.46,0,0,0,20.31-2.09c22.06-4.85,40.1-15.83,51.73-35.88,7.14-12.3,7.3-25.47,6-39.22-3.52-35.63-6.38-71.33-9.55-107q-5.23-59-10.56-118-4.74-53.5-9.4-107-5.34-60.46-10.75-120.94c-1-11.07-2-22.14-3.07-33.24h-78.79v5.89c0,21.17.12,42.33-.07,63.49a19.67,19.67,0,0,1-25.52,18.94c-8.76-2.62-14.14-10.26-14.18-20.65-.09-20.66,0-41.33,0-62,0-1.92-.17-3.84-.27-5.78H843.73v5.73c0,20.16.06,40.32-.06,60.49a33,33,0,0,1-1.33,9.81,19.59,19.59,0,0,1-21.16,13.13c-9.85-1.55-17.08-9.29-17.16-19.13-.19-21.49-.08-43-.09-64.48v-5.61ZM843.5,296.1h233.19c4.33-32.1-19.35-76.72-53-97.52-41.07-25.4-83.45-26.14-124.64-1C862.94,219.65,845.26,253.36,843.5,296.1Z" transform="translate(-641 -140)"/></svg>
                                  </span>
                                  <span className="header-cart-total">{cartCount}</span>
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>

                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

      </header>

      {/* ── LOGIN DRAWER ── */}
      <LoginDrawer
        visible={loginDrawerVisible}
        open={loginDrawerOpen}
        onClose={closeLoginDrawer}
        onLoginSuccess={() => setIsLoggedIn(true)}
      />

      {/* ── MOBILE DRAWER ── */}
      {drawerVisible && (
        <div
          id="mobile-drawer"
          className={`popup-drawer popup-drawer-layout-sidepanel popup-drawer-animation-fade popup-drawer-side-left show-drawer${drawerOpen ? ' active' : ''}`}
          data-drawer-target-string="#mobile-drawer"
        >
          <div className="drawer-overlay" onClick={closeDrawer} />
          <div className="drawer-inner">
            <div className="drawer-header">
              <button
                className="menu-toggle-close drawer-toggle"
                aria-label="Close menu"
                aria-expanded={drawerOpen}
                onClick={closeDrawer}
              >
                <span className="toggle-close-bar" />
                <span className="toggle-close-bar" />
              </button>
            </div>
            <div className="drawer-content mobile-drawer-content content-align-left content-valign-top">
              <div className="site-header-item site-header-focus-item site-header-item-mobile-navigation">
                <nav id="mobile-site-navigation" className="mobile-navigation drawer-navigation drawer-navigation-parent-toggle-false" role="navigation" aria-label="Primary Mobile Navigation">
                  <div className="mobile-menu-container drawer-menu-container">
                    <ul id="mobile-menu" className="menu has-collapse-sub-nav">
                      {categories.map(cat => (
                        <MobileNavItem key={cat.id} item={cat} expanded={expanded} onToggle={toggleItem} />
                      ))}
                    </ul>
                  </div>
                </nav>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
