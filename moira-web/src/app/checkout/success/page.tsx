'use client';

import { useSearchParams } from 'next/navigation';
import Link from 'next/link';
import Header from '@/components/Header';

export default function CheckoutSuccessPage() {
  const params = useSearchParams();
  const orderNumber = params.get('order');

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <main className="max-w-lg mx-auto px-4 py-16 text-center">
        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-10">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
          </div>

          <h1 className="text-2xl font-bold text-gray-900 mb-2">¡Pedido confirmado!</h1>

          {orderNumber && (
            <p className="text-gray-500 text-sm mb-6">
              Número de orden: <span className="font-mono font-semibold text-gray-900">#{orderNumber}</span>
            </p>
          )}

          <p className="text-gray-500 text-sm mb-8">
            Gracias por tu compra. Te notificaremos cuando tu pedido esté en camino.
          </p>

          <div className="space-y-3">
            <Link
              href="/profile"
              className="block w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors text-sm"
            >
              Ver mis pedidos
            </Link>
            <Link
              href="/"
              className="block w-full border border-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-50 transition-colors text-sm"
            >
              Seguir comprando
            </Link>
          </div>
        </div>
      </main>
    </div>
  );
}
