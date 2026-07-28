'use client';

import { useEffect, useState } from 'react';

export default function QtyEditor({
  quantity, onChange, variant = 'page',
}: {
  quantity: number;
  onChange: (q: number) => void;
  variant?: 'page' | 'mini';
}) {
  const [val, setVal] = useState(String(quantity));

  // Resincronizar el input cuando cambia la cantidad (ej. tras −/+).
  useEffect(() => { setVal(String(quantity)); }, [quantity]);

  const parsed = parseInt(val, 10);
  const dirty = Number.isFinite(parsed) && parsed >= 1 && parsed !== quantity;

  function commit() {
    if (dirty) onChange(parsed);
  }

  return (
    <div className={`qty-editor qty-editor--${variant}`}>
      <div className="quantity spinners-added">
        <button
          type="button"
          className="quantity-btn minus"
          onClick={() => onChange(quantity - 1)}
          aria-label="Reducir cantidad"
        >−</button>
        <input
          type="number"
          className="input-text qty text"
          value={val}
          min={1}
          onChange={(e) => setVal(e.target.value)}
          onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); commit(); } }}
          aria-label="Cantidad"
        />
        <button
          type="button"
          className="quantity-btn plus"
          onClick={() => onChange(quantity + 1)}
          aria-label="Aumentar cantidad"
        >+</button>
      </div>
      <button
        type="button"
        className="qty-update-btn"
        onClick={commit}
        disabled={!dirty}
      >Actualizar</button>
    </div>
  );
}
