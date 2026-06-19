'use client';

import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { api } from '@/lib/api';
import { getToken } from '@/lib/auth';

type WishlistContextType = {
  ids: Set<number>;
  isInWishlist: (id: number) => boolean;
  toggle: (id: number) => Promise<void>;
};

const WishlistContext = createContext<WishlistContextType | null>(null);

export function WishlistProvider({ children }: { children: React.ReactNode }) {
  const [ids, setIds] = useState<Set<number>>(new Set());

  useEffect(() => {
    if (!getToken()) return;
    api.getWishlistIds()
      .then((res) => setIds(new Set(res.product_ids)))
      .catch(() => {});
  }, []);

  const isInWishlist = useCallback((id: number) => ids.has(id), [ids]);

  const toggle = useCallback(async (id: number) => {
    const res = await api.toggleWishlist(id);
    setIds((prev) => {
      const next = new Set(prev);
      if (res.in_wishlist) next.add(id);
      else next.delete(id);
      return next;
    });
  }, []);

  return (
    <WishlistContext.Provider value={{ ids, isInWishlist, toggle }}>
      {children}
    </WishlistContext.Provider>
  );
}

export function useWishlist(): WishlistContextType {
  const ctx = useContext(WishlistContext);
  if (!ctx) throw new Error('useWishlist must be used within WishlistProvider');
  return ctx;
}
