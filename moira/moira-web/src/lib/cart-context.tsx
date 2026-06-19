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

  const refreshCart = useCallback(async () => {
    if (!getToken()) return;
    try {
      const res = await api.getCart();
      setCart(res.data);
    } catch {
      // not logged in or network error — silent
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
    if (quantity < 1) {
      const res = await api.removeCartItem(itemId);
      setCart(res.data);
    } else {
      const res = await api.updateCartItem(itemId, quantity);
      setCart(res.data);
    }
  }, []);

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
