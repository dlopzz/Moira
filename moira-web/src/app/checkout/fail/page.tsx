'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import Header from '@/components/Header';

export default function CheckoutFailPage() {
  const router = useRouter();

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <main className="max-w-lg mx-auto px-4 py-16 text-center">
        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-10">
          <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>

          <h1 className="text-2xl font-bold text-gray-900 mb-2">El pago fue rechazado</h1>

          <p className="text-gray-500 text-sm mb-8">
            No pudimos procesar tu pago. Tu carrito sigue guardado — podés intentarlo de nuevo cuando quieras.
          </p>

          <div className="space-y-3">
            <button
              onClick={() => router.push('/checkout/payment')}
              className="block w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors text-sm"
            >
              Volver a intentar el pago
            </button>
            <Link
              href="/cart"
              className="block w-full border border-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-50 transition-colors text-sm"
            >
              Ver mi carrito
            </Link>
            <Link
              href="/"
              className="block w-full text-sm text-gray-400 hover:text-gray-600 py-2"
            >
              Seguir navegando por el sitio
            </Link>
          </div>
        </div>
      </main>
    </div>
  );
}
