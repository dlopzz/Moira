'use client';

import { useEffect, useState } from 'react';
import { useSiteInfo } from '@/lib/site-info-context';
import { wasRecentlyDismissed, markDismissed } from '@/lib/dismissible-cooldown';

const STORAGE_KEY = 'cookie_notice_accepted_at';
const COOLDOWN_MS = 365 * 24 * 60 * 60 * 1000;

export default function CookieNotice() {
  const siteInfo = useSiteInfo();
  // Se lee una sola vez al montar: localStorage no cambia por fuera de accept().
  const [recentlyAccepted] = useState(() => wasRecentlyDismissed(STORAGE_KEY, COOLDOWN_MS));
  const [closed, setClosed] = useState(false);
  const [active, setActive] = useState(false);

  const visible = !!siteInfo?.cookie_notice_enabled && !recentlyAccepted && !closed;

  useEffect(() => {
    if (!visible) return;
    let inner = 0;
    const outer = requestAnimationFrame(() => {
      inner = requestAnimationFrame(() => setActive(true));
    });
    return () => {
      cancelAnimationFrame(outer);
      cancelAnimationFrame(inner);
    };
  }, [visible]);

  function accept() {
    markDismissed(STORAGE_KEY);
    setActive(false);
    setTimeout(() => setClosed(true), 300);
  }

  if (!visible) return null;

  return (
    <div
      id="cookie-popup-1"
      className={`popup-drawer cookie-popup show-drawer${active ? ' active' : ''}`}
    >
      <div className="drawer-inner float_right">
        <p className="cookie-notice__text">{siteInfo?.cookie_notice_text || 'Usamos cookies para mejorar tu experiencia.'}</p>
        <button className="cookie-notice__btn" onClick={accept}>
          Aceptar
        </button>
      </div>
    </div>
  );
}
