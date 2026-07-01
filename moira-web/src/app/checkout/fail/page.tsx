'use client';

import { Suspense } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import Header from '@/components/Header';
import Breadcrumb from '@/components/Breadcrumb';
import { getToken } from '@/lib/auth';

function FailContent() {
  const router      = useRouter();
  const params      = useSearchParams();
  const orderNumber = params.get('order');
  const isAuth      = !!getToken();

  return (
    <div className="woocommerce woocommerce-page">
      <div className="site-container co-page">
        <div className="co-result-card co-result-card--fail">
          <div className="co-result-icon co-result-icon--fail">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>

          <h2 className="co-result-title">El pago fue rechazado</h2>

          {orderNumber && (
            <p className="co-result-order">
              Referencia: <span>#{orderNumber}</span>
            </p>
          )}

          <p className="co-result-message">
            No pudimos procesar tu pago. Verificá los datos de la tarjeta o intentá con otra.
            Tu carrito sigue guardado.
          </p>

          <div className="co-result-actions">
            <button
              type="button"
              className="button alt"
              onClick={() => router.push('/checkout/payment')}
            >
              Volver a intentar
            </button>
            <Link href="/cart" className="button">
              Ver mi carrito
            </Link>
          </div>

          {!isAuth && (
            <p className="co-result-guest-note">
              ¿Tenés cuenta?{' '}
              <Link href="/auth/login" className="co-guest-link">
                Iniciá sesión
              </Link>{' '}
              para pagar más rápido.
            </p>
          )}
        </div>
      </div>
    </div>
  );
}

export default function CheckoutFailPage() {
  return (
    <>
      <Header />

      <section role="banner" className="entry-hero product-archive-hero-section entry-hero-layout-standard">
        <div className="entry-hero-container-inner">
          <div className="hero-section-overlay" />
          <div className="hero-container site-container">
            <header className="entry-header">
              <Breadcrumb crumbs={[{ name: 'Inicio', href: '/' }, { name: 'Checkout' }]} />
              <h1 className="page-title">Pago rechazado</h1>
            </header>
          </div>
        </div>
      </section>

      <Suspense>
        <FailContent />
      </Suspense>
    </>
  );
}
