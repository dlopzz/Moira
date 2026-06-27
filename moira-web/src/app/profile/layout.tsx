'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { api, ApiError } from '@/lib/api';
import { clearToken } from '@/lib/auth';
import Header from '@/components/Header';
import Breadcrumb from '@/components/Breadcrumb';

const NAV = [
  { href: '/profile',            slug: 'dashboard',       label: 'Mi cuenta' },
  { href: '/profile/orders',     slug: 'orders',          label: 'Pedidos' },
  { href: '/profile/reviews',    slug: 'reviews',         label: 'Mis reseñas' },
  { href: '/profile/wishlist',   slug: 'wishlist',        label: 'Wishlist' },
  { href: '/profile/addresses',  slug: 'edit-address',    label: 'Direcciones' },
  { href: '/profile/edit-account', slug: 'edit-account',   label: 'Detalles de cuenta' },
];

function currentLabel(pathname: string): string {
  return NAV.find((n) => n.href === pathname)?.label ?? 'Mi cuenta';
}

function initials(name: string): string {
  return name.split(' ').filter(Boolean).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
}

export default function ProfileLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const [userName, setUserName] = useState('');

  useEffect(() => {
    api.getProfile().then((res) => setUserName(res.data.name)).catch(() => {});
  }, []);

  useEffect(() => {
    document.body.classList.add('woocommerce-account', 'woocommerce-page', 'base-account-nav-right', 'content-style-unboxed', 'content-width-normal', 'content-title-style-above');
    return () => {
      document.body.classList.remove('woocommerce-account', 'woocommerce-page', 'base-account-nav-right', 'content-style-unboxed', 'content-width-normal', 'content-title-style-above');
    };
  }, []);

  async function handleLogout() {
    try {
      await api.logout();
    } catch (e) {
      if (e instanceof ApiError && e.status !== 401) throw e;
    }
    clearToken();
    router.push('/');
  }

  const pageTitle = currentLabel(pathname);

  return (
    <div className="min-h-screen">
      <Header />

      <section role="banner" className="entry-hero page-hero-section entry-hero-layout-standard">
        <div className="entry-hero-container-inner">
          <div className="hero-section-overlay"></div>
          <div className="hero-container site-container">
            <header className="entry-header page-title title-align-inherit title-tablet-align-inherit title-mobile-align-inherit">
              <Breadcrumb crumbs={[{ name: 'Inicio', href: '/' }, { name: pageTitle }]} />
              <h1 className="page-title archive-title">{pageTitle}</h1>
            </header>
          </div>
        </div>
      </section>

      <div id="primary" className="content-area">
        <div className="content-container site-container">
          <main id="main" className="site-main" role="main">
            <div className="content-wrap">
              <article className="entry content-bg single-entry">
                <div className="entry-content-wrap">
                  <div className="entry-content single-content">

            <div className="woocommerce woocommerce-account">

              {/* Sidebar — nav derecho en desktop */}
              <div className="account-navigation-wrap">
                <div className="base-account-avatar">
                  <div className="base-customer-image">
                    <div className="base-customer-initials">
                      {userName ? initials(userName) : ''}
                    </div>
                  </div>
                  <div className="base-customer-name">{userName}</div>
                </div>

                <nav className="woocommerce-MyAccount-navigation" aria-label="Páginas de cuenta">
                  <ul>
                    {NAV.map((item) => (
                      <li
                        key={item.href}
                        className={`woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--${item.slug} menu-item${pathname === item.href ? ' is-active' : ''}`}
                      >
                        <Link href={item.href} aria-current={pathname === item.href ? 'page' : undefined}>{item.label}</Link>
                      </li>
                    ))}
                    <li className="woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--customer-logout menu-item">
                      <button onClick={handleLogout}>Cerrar sesión</button>
                    </li>
                  </ul>
                </nav>
              </div>

              {/* Contenido — izquierdo en desktop */}
              <div className="woocommerce-MyAccount-content">
                <div className="woocommerce-notices-wrapper"></div>
                {children}
              </div>

            </div>

                  </div>{/* entry-content */}
                </div>{/* entry-content-wrap */}
              </article>
            </div>{/* content-wrap */}
          </main>
        </div>
      </div>
    </div>
  );
}
