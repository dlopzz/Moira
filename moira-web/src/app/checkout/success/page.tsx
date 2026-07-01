'use client';

import { Suspense, useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';
import Link from 'next/link';
import Header from '@/components/Header';
import Breadcrumb from '@/components/Breadcrumb';
import { api, ApiError } from '@/lib/api';
import { getToken } from '@/lib/auth';

type GuestPrefill = {
  email: string;
  firstName: string;
  lastName: string;
};

function GuestRegisterPanel({ prefill }: { prefill: GuestPrefill }) {
  const [form, setForm] = useState({
    first_name:            prefill.firstName,
    last_name:             prefill.lastName,
    email:                 prefill.email,
    date_of_birth:         '',
    password:              '',
    password_confirmation: '',
  });
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [error, setError]             = useState('');
  const [loading, setLoading]         = useState(false);
  const [done, setDone]               = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setFieldErrors({});
    setLoading(true);
    try {
      const res = await api.register(form);
      localStorage.setItem('token', res.token);
      localStorage.removeItem('guest_checkout_prefill');
      setDone(true);
    } catch (err) {
      if (err instanceof ApiError) {
        if (err.errors) setFieldErrors(err.errors);
        else setError(err.message);
      }
    } finally {
      setLoading(false);
    }
  }

  if (done) {
    return (
      <div className="co-register-done">
        <div className="co-register-done-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
        </div>
        <h3 className="co-register-done-title">¡Cuenta creada!</h3>
        <p className="co-register-done-msg">
          Te enviamos un email de verificación a <strong>{form.email}</strong>. Activá tu cuenta para acceder a tus pedidos.
        </p>
        <Link href="/profile/orders" className="button alt co-register-cta">
          Ir a mis pedidos
        </Link>
        <Link href="/" className="button co-register-cta">
          Seguir comprando
        </Link>
      </div>
    );
  }

  return (
    <div className="co-register-panel">
      <div className="co-register-divider">
        <span>¿Querés crear tu cuenta?</span>
      </div>
      <p className="co-register-subtitle">
        Guardamos tus datos para que la próxima compra sea más rápida.
      </p>

      <form onSubmit={handleSubmit} className="co-register-form">
        <div className="co-row-2">
          <div className="co-field form-row">
            <label className="co-label">Nombre <abbr title="requerido">*</abbr></label>
            <input
              className="input-text"
              value={form.first_name}
              onChange={e => setForm(f => ({ ...f, first_name: e.target.value }))}
              required
            />
            {fieldErrors.first_name?.[0] && <span className="co-field-error">{fieldErrors.first_name[0]}</span>}
          </div>
          <div className="co-field form-row">
            <label className="co-label">Apellido <abbr title="requerido">*</abbr></label>
            <input
              className="input-text"
              value={form.last_name}
              onChange={e => setForm(f => ({ ...f, last_name: e.target.value }))}
              required
            />
            {fieldErrors.last_name?.[0] && <span className="co-field-error">{fieldErrors.last_name[0]}</span>}
          </div>
        </div>

        <div className="co-field form-row">
          <label className="co-label">Email <abbr title="requerido">*</abbr></label>
          <input
            type="email"
            className="input-text"
            value={form.email}
            onChange={e => setForm(f => ({ ...f, email: e.target.value }))}
            required
          />
          {fieldErrors.email?.[0] && <span className="co-field-error">{fieldErrors.email[0]}</span>}
        </div>

        <div className="co-field form-row">
          <label className="co-label">Fecha de nacimiento <abbr title="requerido">*</abbr></label>
          <input
            type="date"
            className="input-text"
            value={form.date_of_birth}
            onChange={e => setForm(f => ({ ...f, date_of_birth: e.target.value }))}
            required
          />
          {fieldErrors.date_of_birth?.[0] && <span className="co-field-error">{fieldErrors.date_of_birth[0]}</span>}
        </div>

        <div className="co-row-2">
          <div className="co-field form-row">
            <label className="co-label">Contraseña <abbr title="requerido">*</abbr></label>
            <input
              type="password"
              className="input-text"
              value={form.password}
              onChange={e => setForm(f => ({ ...f, password: e.target.value }))}
              required
              minLength={8}
              autoComplete="new-password"
            />
            {fieldErrors.password?.[0] && <span className="co-field-error">{fieldErrors.password[0]}</span>}
          </div>
          <div className="co-field form-row">
            <label className="co-label">Repetir contraseña <abbr title="requerido">*</abbr></label>
            <input
              type="password"
              className="input-text"
              value={form.password_confirmation}
              onChange={e => setForm(f => ({ ...f, password_confirmation: e.target.value }))}
              required
              minLength={8}
              autoComplete="new-password"
            />
            {fieldErrors.password_confirmation?.[0] && <span className="co-field-error">{fieldErrors.password_confirmation[0]}</span>}
          </div>
        </div>

        {error && <p className="co-error">{error}</p>}

        <button type="submit" className="button alt co-register-submit" disabled={loading}>
          {loading ? 'Creando cuenta...' : 'Crear cuenta'}
        </button>
      </form>

      <p className="co-register-skip">
        <Link href="/" className="co-guest-link">No gracias — seguir comprando</Link>
      </p>
    </div>
  );
}

function SuccessContent() {
  const params      = useSearchParams();
  const orderNumber = params.get('order');
  const isAuth      = !!getToken();

  const [prefill, setPrefill] = useState<GuestPrefill | null>(null);

  useEffect(() => {
    if (isAuth) return;
    try {
      const raw = localStorage.getItem('guest_checkout_prefill');
      if (raw) setPrefill(JSON.parse(raw));
    } catch { /* ignore */ }
  }, [isAuth]);

  return (
    <div className="woocommerce woocommerce-page">
      <div className="site-container co-page">
        <div className={`co-result-card co-result-card--success${!isAuth && prefill ? ' co-result-card--with-register' : ''}`}>
          <div className="co-result-icon co-result-icon--success">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
          </div>

          <h2 className="co-result-title">¡Pedido confirmado!</h2>

          {orderNumber && (
            <p className="co-result-order">
              Número de orden: <span>#{orderNumber}</span>
            </p>
          )}

          <p className="co-result-message">
            Gracias por tu compra. Te enviamos un email con los detalles y te avisaremos cuando tu pedido esté en camino.
          </p>

          <div className="co-result-actions">
            {isAuth && (
              <Link href="/profile/orders" className="button alt">
                Ver mis pedidos
              </Link>
            )}
            <Link href="/" className="button">
              Seguir comprando
            </Link>
          </div>

          {!isAuth && prefill && <GuestRegisterPanel prefill={prefill} />}
        </div>
      </div>
    </div>
  );
}

export default function CheckoutSuccessPage() {
  return (
    <>
      <Header />

      <section role="banner" className="entry-hero product-archive-hero-section entry-hero-layout-standard">
        <div className="entry-hero-container-inner">
          <div className="hero-section-overlay" />
          <div className="hero-container site-container">
            <header className="entry-header">
              <Breadcrumb crumbs={[{ name: 'Inicio', href: '/' }, { name: 'Checkout' }]} />
              <h1 className="page-title">Pedido confirmado</h1>
            </header>
          </div>
        </div>
      </section>

      <Suspense>
        <SuccessContent />
      </Suspense>
    </>
  );
}
