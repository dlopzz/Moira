'use client';

import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { api } from '@/lib/api';
import { getToken } from '@/lib/auth';

export type WishlistItem = {
  id: number;
  name: string;
  slug: string;
  price: number;
  sale_price: number | null;
  image: string | null;
};

type WishlistContextType = {
  items: WishlistItem[];
  ids: Set<number>;
  isInWishlist: (id: number) => boolean;
  toggle: (item: WishlistItem) => Promise<void>;
  isOpen: boolean;
  closePopup: () => void;
  lastAdded: WishlistItem | null;
};

const WishlistContext = createContext<WishlistContextType | null>(null);

const LS_KEY = 'moira_wishlist';

function loadLocal(): WishlistItem[] {
  if (typeof window === 'undefined') return [];
  try {
    return JSON.parse(localStorage.getItem(LS_KEY) ?? '[]');
  } catch {
    return [];
  }
}

function saveLocal(items: WishlistItem[]): void {
  localStorage.setItem(LS_KEY, JSON.stringify(items));
}

export function WishlistProvider({ children }: { children: React.ReactNode }) {
  const [items, setItems] = useState<WishlistItem[]>([]);
  const [isOpen, setIsOpen] = useState(false);
  const [lastAdded, setLastAdded] = useState<WishlistItem | null>(null);
  const noticeTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    const local = loadLocal();

    if (!getToken()) {
      setItems(local);
      return;
    }

    api.getWishlistIds()
      .then((res) => {
        const apiIds = new Set(res.product_ids);
        const synced = local.filter((i) => apiIds.has(i.id));
        setItems(synced);
        saveLocal(synced);
      })
      .catch(() => setItems(local));
  }, []);

  const ids = useMemo(() => new Set(items.map((i) => i.id)), [items]);

  const isInWishlist = useCallback((id: number) => ids.has(id), [ids]);

  const showNotice = useCallback((item: WishlistItem) => {
    setLastAdded(item);
    setIsOpen(true);
    if (noticeTimer.current) clearTimeout(noticeTimer.current);
    noticeTimer.current = setTimeout(() => setLastAdded(null), 2500);
  }, []);

  const toggle = useCallback(async (item: WishlistItem) => {
    const inWishlist = ids.has(item.id);

    if (!getToken()) {
      setItems((prev) => {
        const next = inWishlist
          ? prev.filter((i) => i.id !== item.id)
          : [...prev, item];
        saveLocal(next);
        return next;
      });
      if (!inWishlist) showNotice(item);
      return;
    }

    try {
      const res = await api.toggleWishlist(item.id);
      setItems((prev) => {
        const next = res.in_wishlist
          ? prev.some((i) => i.id === item.id) ? prev : [...prev, item]
          : prev.filter((i) => i.id !== item.id);
        saveLocal(next);
        return next;
      });
      if (res.in_wishlist) showNotice(item);
    } catch {
      throw new Error('Error al actualizar la lista de deseos');
    }
  }, [ids, showNotice]);

  const closePopup = useCallback(() => setIsOpen(false), []);

  return (
    <WishlistContext.Provider value={{ items, ids, isInWishlist, toggle, isOpen, closePopup, lastAdded }}>
      {children}
    </WishlistContext.Provider>
  );
}

export function useWishlist(): WishlistContextType {
  const ctx = useContext(WishlistContext);
  if (!ctx) throw new Error('useWishlist must be used within WishlistProvider');
  return ctx;
}
