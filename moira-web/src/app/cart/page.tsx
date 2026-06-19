'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { useRouter } from 'next/navigation';
import { api, type Cart, ApiError } from '@/lib/api';
import { getToken } from '@/lib/auth';
import Header from '@/components/Header';
import Breadcrumb from '@/components/Breadcrumb';

export default function CartPage() {
  const router = useRouter();
  const [cart, setCart] = useState<Cart | null>(null);
  const [loading, setLoading] = useState(true);
  const [couponInput, setCouponInput] = useState('');
  const [couponError, setCouponError] = useState('');
  const [couponLoading, setCouponLoading] = useState(false);

  useEffect(() => {
    if (!getToken()) {
      router.push('/auth/login');
      return;
    }
    api.getCart()
      .then((res) => setCart(res.data))
      .finally(() => setLoading(false));
  }, [router]);

  async function updateQty(itemId: number, qty: number) {
    if (qty < 1) return;
    const res = await api.updateCartItem(itemId, qty);
    setCart(res.data);
  }

  async function removeItem(itemId: number) {
    const res = await api.removeCartItem(itemId);
    setCart(res.data);
  }

  async function applyCoupon() {
    setCouponError('');
    setCouponLoading(true);
    try {
      const res = await api.applyCoupon(couponInput.trim());
      setCart(res.data);
      setCouponInput('');
    } catch (err) {
      if (err instanceof ApiError) setCouponError(err.message);
    } finally {
      setCouponLoading(false);
    }
  }

  async function removeCoupon() {
    const res = await api.removeCoupon();
    setCart(res.data);
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <div className="max-w-6xl mx-auto px-4 py-8 text-gray-400">Cargando carrito...</div>
      </div>
    );
  }

  const isEmpty = !cart || cart.items.length === 0;

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <main className="max-w-6xl mx-auto px-4 py-8">
        <Breadcrumb crumbs={[{ name: 'Carrito' }]} />
        <h1 className="text-2xl font-bold text-gray-900 mb-6">Mi carrito</h1>

        {isEmpty ? (
          <div className="text-center py-16">
            <p className="text-gray-400 text-lg mb-4">Tu carrito está vacío</p>
            <Link href="/" className="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 text-sm">
              Seguir comprando
            </Link>
          </div>
        ) : (
          <div className="grid lg:grid-cols-3 gap-6">
            {/* Items */}
            <div className="lg:col-span-2 space-y-3">
              {cart.items.map((item) => (
                <div key={item.id} className="bg-white rounded-xl border border-gray-200 p-4 flex gap-4">
                  <div className="relative w-20 h-20 flex-none rounded-lg overflow-hidden bg-gray-100">
                    {item.image ? (
                      <Image src={item.image} alt={item.name} fill className="object-cover" sizes="80px" />
                    ) : (
                      <div className="absolute inset-0 flex items-center justify-center text-gray-300 text-2xl">📦</div>
                    )}
                  </div>

                  <div className="flex-1 min-w-0">
                    <Link
                      href={`/products/${item.product_id}`}
                      className="font-medium text-gray-900 hover:text-blue-600 line-clamp-2 text-sm"
                    >
                      {item.name}
                    </Link>
                    {item.variant_label && (
                      <p className="text-xs text-gray-500 mt-0.5">{item.variant_label}</p>
                    )}
                    {item.sku && <p className="text-xs text-gray-400 mt-0.5">SKU: {item.sku}</p>}

                    <div className="flex items-center justify-between mt-3">
                      <div className="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                        <button
                          onClick={() => updateQty(item.id, item.quantity - 1)}
                          className="px-3 py-1.5 text-gray-600 hover:bg-gray-50 text-sm font-medium"
                        >
                          −
                        </button>
                        <span className="px-3 py-1.5 text-sm font-medium text-gray-900 border-x border-gray-200">
                          {item.quantity}
                        </span>
                        <button
                          onClick={() => updateQty(item.id, item.quantity + 1)}
                          className="px-3 py-1.5 text-gray-600 hover:bg-gray-50 text-sm font-medium"
                        >
                          +
                        </button>
                      </div>

                      <div className="flex items-center gap-4">
                        <div className="text-right">
                          <p className="text-sm font-bold text-gray-900">${item.subtotal.toFixed(2)}</p>
                          {item.quantity > 1 && (
                            <p className="text-xs text-gray-400">${item.unit_price.toFixed(2)} c/u</p>
                          )}
                        </div>
                        <button
                          onClick={() => removeItem(item.id)}
                          className="text-gray-300 hover:text-red-500 transition-colors"
                          aria-label="Eliminar"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              ))}

              <Link href="/" className="inline-block text-sm text-blue-600 hover:underline mt-2">
                ← Seguir comprando
              </Link>
            </div>

            {/* Summary */}
            <div className="space-y-4">
              {/* Coupon */}
              <div className="bg-white rounded-xl border border-gray-200 p-4">
                <h3 className="font-semibold text-gray-900 mb-3 text-sm">Cupón de descuento</h3>

                {cart.coupon_code ? (
                  <div className="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                    <div>
                      <p className="text-sm font-medium text-green-700">{cart.coupon_code}</p>
                      <p className="text-xs text-green-600">−${cart.summary.discount.toFixed(2)}</p>
                    </div>
                    <button
                      onClick={removeCoupon}
                      className="text-green-400 hover:text-red-500 transition-colors text-xs underline"
                    >
                      Quitar
                    </button>
                  </div>
                ) : (
                  <div className="flex gap-2">
                    <input
                      type="text"
                      value={couponInput}
                      onChange={(e) => { setCouponInput(e.target.value); setCouponError(''); }}
                      onKeyDown={(e) => e.key === 'Enter' && applyCoupon()}
                      placeholder="Código de cupón"
                      className="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <button
                      onClick={applyCoupon}
                      disabled={!couponInput.trim() || couponLoading}
                      className="bg-gray-900 text-white text-sm px-3 py-2 rounded-lg hover:bg-gray-800 disabled:opacity-40 transition-colors"
                    >
                      Aplicar
                    </button>
                  </div>
                )}

                {couponError && (
                  <p className="text-xs text-red-600 mt-2">{couponError}</p>
                )}
              </div>

              {/* Order summary */}
              <div className="bg-white rounded-xl border border-gray-200 p-4">
                <h3 className="font-semibold text-gray-900 mb-4">Resumen del pedido</h3>

                <div className="space-y-2 text-sm">
                  <div className="flex justify-between text-gray-600">
                    <span>Subtotal ({cart.summary.items_count} artículo{cart.summary.items_count !== 1 ? 's' : ''})</span>
                    <span>${cart.summary.subtotal.toFixed(2)}</span>
                  </div>

                  {cart.summary.shipping_cost > 0 && (
                    <div className="flex justify-between text-gray-600">
                      <span>Envío</span>
                      <span>${cart.summary.shipping_cost.toFixed(2)}</span>
                    </div>
                  )}

                  {cart.summary.discount > 0 && (
                    <div className="flex justify-between text-green-600">
                      <span>Descuento</span>
                      <span>−${cart.summary.discount.toFixed(2)}</span>
                    </div>
                  )}

                  <div className="border-t border-gray-100 pt-2 mt-2 flex justify-between font-bold text-gray-900 text-base">
                    <span>Total</span>
                    <span>${cart.summary.total.toFixed(2)}</span>
                  </div>
                </div>

                <Link
                  href="/checkout/shipping"
                  className="mt-4 block w-full text-center bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors text-sm"
                >
                  Proceder al pago
                </Link>
              </div>
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
