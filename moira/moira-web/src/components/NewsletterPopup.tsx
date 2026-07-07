'use client';

import { useEffect, useRef, useState } from 'react';
import { usePathname } from 'next/navigation';
import { api, ApiError } from '@/lib/api';
import { wasRecentlyDismissed, markDismissed, isPermanentlyDismissed } from '@/lib/dismissible-cooldown';

const DISMISSED_KEY = 'newsletter_dismissed_at';
// Sin fecha de vencimiento real: alguien que ya se suscribió no debe volver a
// ver el popup, a diferencia de un simple "cerrar" que solo pausa por 7 días.
const SUBSCRIBED_KEY = 'newsletter_subscribed_at';
const COOLDOWN_MS = 7 * 24 * 60 * 60 * 1000;
const TRIGGER_DELAY_MS = 7000;

export default function NewsletterPopup() {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);
  const [email, setEmail] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [loading, setLoading] = useState(false);
  const closeTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    if (isPermanentlyDismissed(SUBSCRIBED_KEY)) return;
    if (wasRecentlyDismissed(DISMISSED_KEY, COOLDOWN_MS)) return;
    if (/^\/(checkout|auth)/.test(pathname)) return;

    const timer = setTimeout(() => setOpen(true), TRIGGER_DELAY_MS);
    return () => clearTimeout(timer);
  }, [pathname]);

  function dismiss() {
    markDismissed(DISMISSED_KEY);
    setOpen(false);
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await api.subscribeNewsletter(email);
      setSuccess(true);
      markDismissed(SUBSCRIBED_KEY);
      closeTimer.current = setTimeout(() => setOpen(false), 3000);
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.errors?.email?.[0] ?? err.message);
      } else {
        setError('Ocurrió un error. Intentá nuevamente.');
      }
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    return () => {
      if (closeTimer.current) clearTimeout(closeTimer.current);
    };
  }, []);

  return (
    <div
      className={`newsletter-popup${open ? ' newsletter-popup--open' : ''}`}
      role="dialog"
      aria-modal="true"
      aria-label="Suscribite al newsletter"
      onClick={dismiss}
    >
      <div className="newsletter-popup__card" onClick={e => e.stopPropagation()}>
        <button
          className="newsletter-popup__close"
          onClick={dismiss}
          aria-label="Cerrar"
        >
          ×
        </button>

        <div className="newsletter-popup__icon">💌</div>

        <h2 className="newsletter-popup__title">
          Suscribite al newsletter
        </h2>
        <p className="newsletter-popup__subtitle">
          Enterarte primero de novedades, ofertas<br />y colecciones exclusivas.
        </p>

        {success ? (
          <div className="newsletter-popup__success">
            ¡Listo! Revisá tu email. 🎉
          </div>
        ) : (
          <form className="newsletter-popup__form" onSubmit={handleSubmit}>
            <input
              type="email"
              className="newsletter-popup__input"
              placeholder="Tu email"
              value={email}
              onChange={e => setEmail(e.target.value)}
              required
              disabled={loading}
            />
            {error && <p className="newsletter-popup__error">{error}</p>}
            <button
              type="submit"
              className="newsletter-popup__submit"
              disabled={loading}
            >
              {loading ? 'Enviando...' : 'Suscribirme'}
            </button>
          </form>
        )}

        <p className="newsletter-popup__disclaimer">
          Sin spam. Podés darte de baja cuando quieras.
        </p>
      </div>
    </div>
  );
}
