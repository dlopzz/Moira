'use client';

import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';
import { api, type Cart } from './api';
import { getToken } from './auth';

type CartContextType = {
  cart: Cart | null;
  cartOpen: boolean;
  openCart: () => void;
  closeCart: () => void;
  toggleCart: () => void;
  refreshCart: () => Promise<void>;
  addItem: (productId: number, quantity?: number, variantId?: number) => Promise<void>;
  updateItem: (itemId: number, quantity: number) => Promise<void>;
  removeItem: (itemId: number) => Promise<void>;
};

const CartContext = createContext<CartContextType | null>(null);

export function CartProvider({ children }: { children: React.ReactNode }) {
  const [cart, setCart] = useState<Cart | null>(null);
  const [cartOpen, setCartOpen] = useState(false);
  const initialized = useRef(false);
  // Timers de debounce por ítem: N clicks rápidos de +/- = 1 solo request.
  const qtyTimers = useRef<Map<number, ReturnType<typeof setTimeout>>>(new Map());

  const refreshCart = useCallback(async () => {
    try {
      const res = await api.getCart();
      setCart(res.data);
    } catch {
      // network error — silent
    }
  }, []);

  useEffect(() => {
    if (initialized.current) return;
    initialized.current = true;
    refreshCart();
  }, [refreshCart]);

  const addItem = useCallback(async (productId: number, quantity = 1, variantId?: number) => {
    const res = await api.addToCart(productId, quantity, variantId);
    setCart(res.data);
    setCartOpen(true);
  }, []);

  const updateItem = useCallback(async (itemId: number, quantity: number) => {
    // Optimistic: la UI reacciona al instante (así el usuario no re-clickea).
    setCart(prev => {
      if (!prev) return prev;
      const items = quantity < 1
        ? prev.items.filter(i => i.id !== itemId)
        : prev.items.map(i => i.id === itemId
            ? { ...i, quantity, subtotal: i.unit_price * quantity }
            : i);
      return { ...prev, items };
    });

    // Debounce del request por ítem: solo se envía la última cantidad.
    const existing = qtyTimers.current.get(itemId);
    if (existing) clearTimeout(existing);
    qtyTimers.current.set(itemId, setTimeout(async () => {
      qtyTimers.current.delete(itemId);
      try {
        const res = quantity < 1
          ? await api.removeCartItem(itemId)
          : await api.updateCartItem(itemId, quantity);
        setCart(res.data);
      } catch {
        // 429 / error de red: resincronizar con el server (rollback).
        refreshCart();
      }
    }, 400));
  }, [refreshCart]);

  const removeItem = useCallback(async (itemId: number) => {
    const res = await api.removeCartItem(itemId);
    setCart(res.data);
  }, []);

  return (
    <CartContext.Provider value={{
      cart,
      cartOpen,
      openCart:   () => setCartOpen(true),
      closeCart:  () => setCartOpen(false),
      toggleCart: () => setCartOpen((o) => !o),
      refreshCart,
      addItem,
      updateItem,
      removeItem,
    }}>
      {children}
    </CartContext.Provider>
  );
}

export function useCart(): CartContextType {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error('useCart must be used inside <CartProvider>');
  return ctx;
}
