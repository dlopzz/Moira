'use client';

import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';

const DEFAULT_REFETCH_COOLDOWN_MS = 60_000;

/**
 * Shared shape for a "fetch once, refetch on tab-focus with a cooldown,
 * never wipe good data on a failed background refetch" context. Extracted
 * after site-info-context.tsx and categories-context.tsx converged on this
 * exact same ~45-line implementation, differing only in the fetched type.
 *
 * `debugName` sets .displayName on the Provider/Context so React DevTools
 * and error/warning messages can tell multiple instances apart — without
 * it, every instance's Provider/hook shows up as the same generic
 * "Provider"/"useValue" name, since destructuring a returned tuple doesn't
 * rename the underlying function.
 */
export function createFetchOnceContext<T>(
  fetcher: () => Promise<{ data: T }>,
  options: { cooldownMs?: number; debugName?: string } = {}
) {
  const { cooldownMs = DEFAULT_REFETCH_COOLDOWN_MS, debugName } = options;
  const Context = createContext<T | null | undefined>(undefined);
  if (debugName) Context.displayName = debugName;

  function Provider({ children }: { children: React.ReactNode }) {
    const [value, setValue] = useState<T | null | undefined>(undefined);
    // 0 = todavía no se hizo ningún fetch (cubre tanto el guard de montaje una
    // sola vez en StrictMode como el cooldown del refetch por visibilitychange).
    const lastFetchedAt = useRef(0);

    const fetchValue = useCallback(() => {
      const isFirstFetch = lastFetchedAt.current === 0;
      lastFetchedAt.current = Date.now();

      fetcher()
        .then((r) => setValue(r.data))
        .catch(() => {
          // Solo pisar con null si nunca hubo un fetch exitoso: un refetch en
          // background que falla (ej. blip de red al volver el foco) no debe
          // tirar datos buenos ya cacheados.
          if (isFirstFetch) setValue(null);
        });
    }, []);

    useEffect(() => {
      if (lastFetchedAt.current === 0) {
        fetchValue();
      }

      // La raíz del layout no se remonta en navegaciones client-side, así que
      // sin esto el valor quedaría desactualizado toda la sesión del navegador
      // si cambia en el backend mientras alguien navega el sitio.
      function onVisible() {
        if (document.visibilityState !== 'visible') return;
        if (Date.now() - lastFetchedAt.current < cooldownMs) return;
        fetchValue();
      }

      document.addEventListener('visibilitychange', onVisible);
      return () => document.removeEventListener('visibilitychange', onVisible);
    }, [fetchValue]);

    return <Context.Provider value={value}>{children}</Context.Provider>;
  }
  if (debugName) Provider.displayName = `${debugName}Provider`;

  /** undefined = todavía cargando, null = falló, T = cargado. */
  function useValue(): T | null | undefined {
    return useContext(Context);
  }

  return [Provider, useValue] as const;
}
