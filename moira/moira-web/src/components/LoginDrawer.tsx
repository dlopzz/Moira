'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { api, ApiError } from '@/lib/api';
import { saveToken } from '@/lib/auth';

const API = process.env.NEXT_PUBLIC_API_URL!;

const CloseSvg = () => (
  <span className="base-svg-iconset">
    <svg className="base-svg-icon base-close-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
      <title>Toggle Menu Close</title>
      <path d="M5.293 6.707l5.293 5.293-5.293 5.293c-0.391 0.391-0.391 1.024 0 1.414s1.024 0.391 1.414 0l5.293-5.293 5.293 5.293c0.391 0.391 1.024 0.391 1.414 0s0.391-1.024 0-1.414l-5.293-5.293 5.293-5.293c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-5.293 5.293-5.293-5.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414z" />
    </svg>
  </span>
);

interface LoginDrawerProps {
  visible: boolean;
  open: boolean;
  onClose: () => void;
  onLoginSuccess: () => void;
}

export default function LoginDrawer({ visible, open, onClose, onLoginSuccess }: LoginDrawerProps) {
  const router = useRouter();
  const [form, setForm] = useState({ email: '', password: '' });
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [emailUnverified, setEmailUnverified] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setMessage('');
    setEmailUnverified(false);
    setLoading(true);
    try {
      const res = await api.login(form);
      saveToken(res.token);
      onLoginSuccess();
      onClose();
      router.push('/profile');
    } catch (err) {
      if (err instanceof ApiError) {
        setMessage(err.message);
        if ((err as ApiError & { email_unverified?: boolean }).email_unverified) {
          setEmailUnverified(true);
        }
      }
    } finally {
      setLoading(false);
    }
  }

  if (!visible) {
    return null;
  }

  return (
    <div
      id="login-drawer"
      className={`popup-drawer popup-drawer-layout-fullwidth show-drawer${open ? ' active' : ''}`}
      data-drawer-target-string="#login-drawer"
    >
      <div className="drawer-overlay" onClick={onClose} data-drawer-target-string="#login-drawer" />
      <div className="drawer-inner">
        <div className="drawer-header">
          <button
            className="login-toggle-close drawer-toggle"
            aria-label="Close Login"
            aria-expanded={open}
            onClick={onClose}
          >
            <CloseSvg />
          </button>
        </div>
        <div className="drawer-content widget_login_form">
          <div className="drawer-content_inner widget_login_form_inner">

            {message && (
              <div className="woocommerce-notices-wrapper">
                <p style={{ color: 'var(--color-alert, #dc2626)', marginBottom: '0.8em', fontSize: '0.875em' }}>
                  {message}
                </p>
                {emailUnverified && (
                  <Link href="/auth/verify-email" onClick={onClose} style={{ fontSize: '0.8em', color: 'var(--global-palette-highlight)' }}>
                    Reenviar email de verificación
                  </Link>
                )}
              </div>
            )}

            <a
              href={`${API}/auth/google`}
              className="woocommerce-button button"
              style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.5em', marginBottom: '1em', textAlign: 'center' }}
            >
              <svg viewBox="0 0 24 24" style={{ width: 16, height: 16, flexShrink: 0 }}>
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
              </svg>
              Continuar con Google
            </a>

            <form className="woocommerce-form woocommerce-form-login login" onSubmit={handleSubmit}>
              <p className="form-row form-row-first">
                <label htmlFor="login-email">
                  Email&nbsp;<span className="required" aria-hidden="true">*</span>
                </label>
                <input
                  type="email"
                  className="input-text"
                  id="login-email"
                  value={form.email}
                  onChange={(e) => setForm({ ...form, email: e.target.value })}
                  autoComplete="email"
                  required
                  aria-required="true"
                />
              </p>
              <p className="form-row form-row-last">
                <label htmlFor="login-password">
                  Contraseña&nbsp;<span className="required" aria-hidden="true">*</span>
                </label>
                <input
                  type="password"
                  className="input-text woocommerce-Input"
                  id="login-password"
                  value={form.password}
                  onChange={(e) => setForm({ ...form, password: e.target.value })}
                  autoComplete="current-password"
                  required
                  aria-required="true"
                />
              </p>
              <div className="clear" />
              <p className="form-row">
                <button
                  type="submit"
                  className="woocommerce-button button woocommerce-form-login__submit"
                  disabled={loading}
                >
                  {loading ? 'Ingresando...' : 'Iniciar sesión'}
                </button>
              </p>
              <p className="lost_password">
                <Link href="/auth/forgot-password" onClick={onClose}>
                  ¿Olvidaste la contraseña?
                </Link>
              </p>
            </form>

            <hr className="register-divider" />
            <p className="register-field">
              ¿No tenés cuenta?{' '}
              <Link href="/auth/register" className="register-link" onClick={onClose}>
                Registrate
              </Link>
            </p>

          </div>
        </div>
      </div>
    </div>
  );
}
