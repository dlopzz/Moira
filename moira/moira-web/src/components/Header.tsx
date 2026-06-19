'use client';

import Link from 'next/link';
import Image from 'next/image';
import { useEffect, useRef, useState } from 'react';
import { useRouter } from 'next/navigation';
import { api, type Category } from '@/lib/api';
import { useCart } from '@/lib/cart-context';
import { getToken } from '@/lib/auth';

export default function Header() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [catOpen, setCatOpen] = useState<number | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const { cart, cartOpen, toggleCart, closeCart, updateItem, removeItem } = useCart();
  const cartRef = useRef<HTMLDivElement>(null);
  const router = useRouter();

  useEffect(() => {
    api.getCategories().then((res) => setCategories(res.data)).catch(() => {});
  }, []);

  // Close mini cart on outside click
  useEffect(() => {
    function handler(e: MouseEvent) {
      if (cartRef.current && !cartRef.current.contains(e.target as Node)) {
        closeCart();
      }
    }
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [closeCart]);

  const itemCount = cart?.summary.items_count ?? 0;

  return (
    <header className="bg-white border-b border-gray-200 sticky top-0 z-50">
      <div className="max-w-6xl mx-auto px-4 flex items-center justify-between h-14">
        <Link href="/" className="font-bold text-xl tracking-tight text-gray-900">
          Moira
        </Link>

        {/* Category nav */}
        <nav className="hidden md:flex items-center gap-1">
          {categories.map((cat) => (
            <div
              key={cat.id}
              className="relative"
              onMouseEnter={() => setCatOpen(cat.id)}
              onMouseLeave={() => setCatOpen(null)}
            >
              <Link
                href={`/categories/${cat.slug}`}
                className="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-md hover:bg-gray-50 transition-colors"
              >
                {cat.name}
              </Link>
              {cat.children && cat.children.length > 0 && catOpen === cat.id && (
                <div className="absolute top-full left-0 bg-white border border-gray-200 rounded-md shadow-lg min-w-40 py-1">
                  {cat.children.map((child) => (
                    <Link
                      key={child.id}
                      href={`/categories/${child.slug}`}
                      className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                      {child.name}
                    </Link>
                  ))}
                </div>
              )}
            </div>
          ))}
        </nav>

        {/* Search */}
        <form
          onSubmit={(e) => {
            e.preventDefault();
            const q = searchQuery.trim();
            if (q) router.push(`/search?q=${encodeURIComponent(q)}`);
          }}
          className="hidden md:flex items-center"
        >
          <div className="relative">
            <input
              type="search"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Buscar productos..."
              className="w-56 pl-8 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <svg xmlns="http://www.w3.org/2000/svg" className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
          </div>
        </form>

        <div className="flex items-center gap-3">
          {/* Mini cart */}
          <div className="relative" ref={cartRef}>
            <button
              onClick={toggleCart}
              className="relative p-2 text-gray-600 hover:text-gray-900 transition-colors"
              aria-label="Carrito"
            >
              <svg xmlns="http://www.w3.org/2000/svg" className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
              </svg>
              {itemCount > 0 && (
                <span className="absolute -top-1 -right-1 bg-blue-600 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-medium">
                  {itemCount > 99 ? '99+' : itemCount}
                </span>
              )}
            </button>

            {cartOpen && (
              <div className="absolute right-0 top-full mt-1 w-88 bg-white border border-gray-200 rounded-xl shadow-xl z-50" style={{ width: '22rem' }}>
                {!getToken() ? (
                  /* Not logged in */
                  <div className="p-6 text-center">
                    <p className="text-sm text-gray-500 mb-3">Iniciá sesión para ver tu carrito</p>
                    <Link
                      href="/auth/login"
                      className="inline-block bg-blue-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-700"
                      onClick={closeCart}
                    >
                      Iniciar sesión
                    </Link>
                  </div>
                ) : !cart || cart.items.length === 0 ? (
                  /* Empty cart */
                  <div className="p-8 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" className="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                    </svg>
                    <p className="text-sm text-gray-400 font-medium">Tu carrito está vacío</p>
                    <p className="text-xs text-gray-300 mt-1">Agregá productos para continuar</p>
                  </div>
                ) : (
                  /* Cart with items */
                  <>
                    <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                      <span className="text-sm font-semibold text-gray-900">
                        Carrito ({itemCount} artículo{itemCount !== 1 ? 's' : ''})
                      </span>
                      <button onClick={closeCart} className="text-gray-300 hover:text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>

                    <div className="max-h-72 overflow-y-auto divide-y divide-gray-50">
                      {cart.items.map((item) => (
                        <div key={item.id} className="flex items-center gap-3 px-4 py-3">
                          {/* Image */}
                          <div className="relative w-12 h-12 flex-none rounded-md overflow-hidden bg-gray-100">
                            {item.image ? (
                              <Image src={item.image} alt={item.name} fill className="object-cover" sizes="48px" />
                            ) : (
                              <div className="absolute inset-0 flex items-center justify-center text-gray-300 text-lg">📦</div>
                            )}
                          </div>

                          {/* Info */}
                          <div className="flex-1 min-w-0">
                            <p className="text-xs font-medium text-gray-900 truncate">{item.name}</p>
                            {item.variant_label && (
                              <p className="text-xs text-gray-500 truncate">{item.variant_label}</p>
                            )}
                            <p className="text-xs text-gray-400">${item.unit_price.toFixed(2)} c/u</p>

                            {/* Qty controls */}
                            <div className="flex items-center gap-2 mt-1.5">
                              <div className="flex items-center border border-gray-200 rounded-md overflow-hidden">
                                <button
                                  onClick={() => updateItem(item.id, item.quantity - 1)}
                                  className="w-6 h-6 flex items-center justify-center text-gray-500 hover:bg-gray-50 text-sm font-medium"
                                >
                                  −
                                </button>
                                <span className="w-6 h-6 flex items-center justify-center text-xs font-semibold text-gray-800 border-x border-gray-200">
                                  {item.quantity}
                                </span>
                                <button
                                  onClick={() => updateItem(item.id, item.quantity + 1)}
                                  className="w-6 h-6 flex items-center justify-center text-gray-500 hover:bg-gray-50 text-sm font-medium"
                                >
                                  +
                                </button>
                              </div>
                              <button
                                onClick={() => removeItem(item.id)}
                                className="text-gray-300 hover:text-red-400 transition-colors"
                                aria-label="Eliminar"
                              >
                                <svg xmlns="http://www.w3.org/2000/svg" className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                  <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                              </button>
                            </div>
                          </div>

                          {/* Subtotal */}
                          <p className="text-sm font-bold text-gray-900 flex-none">
                            ${item.subtotal.toFixed(2)}
                          </p>
                        </div>
                      ))}
                    </div>

                    {/* Footer */}
                    <div className="border-t border-gray-100 px-4 py-3 space-y-2.5">
                      {cart.summary.discount > 0 && (
                        <div className="flex justify-between text-xs text-green-600">
                          <span>Descuento {cart.coupon_code && `(${cart.coupon_code})`}</span>
                          <span>−${cart.summary.discount.toFixed(2)}</span>
                        </div>
                      )}
                      <div className="flex justify-between text-sm font-bold text-gray-900">
                        <span>Total</span>
                        <span>${cart.summary.total.toFixed(2)}</span>
                      </div>
                      <div className="grid grid-cols-2 gap-2 pt-1">
                        <Link
                          href="/cart"
                          className="text-center border border-gray-200 text-gray-700 text-xs py-2.5 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                          onClick={closeCart}
                        >
                          Ver carrito
                        </Link>
                        <Link
                          href="/checkout/shipping"
                          className="text-center bg-blue-600 text-white text-xs py-2.5 rounded-lg hover:bg-blue-700 transition-colors font-medium"
                          onClick={closeCart}
                        >
                          Ir a pagar
                        </Link>
                      </div>
                    </div>
                  </>
                )}
              </div>
            )}
          </div>

          <Link href="/profile" className="text-sm font-medium text-gray-700 hover:text-gray-900">
            Mi cuenta
          </Link>
        </div>
      </div>
    </header>
  );
}
