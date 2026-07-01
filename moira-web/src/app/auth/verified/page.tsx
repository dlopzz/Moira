'use client';

import { Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import Link from 'next/link';

function VerifiedContent() {
  const params = useSearchParams();
  const success  = params.get('success');
  const already  = params.get('already');
  const error    = params.get('error');

  if (error === 'expired') {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="bg-white p-8 rounded shadow w-full max-w-sm text-center">
          <div className="text-4xl mb-4">⏰</div>
          <h1 className="text-xl font-bold text-gray-900 mb-2">Link expirado</h1>
          <p className="text-sm text-gray-500 mb-6">El link de verificación expiró. Podés solicitar uno nuevo.</p>
          <Link href="/auth/verify-email" className="w-full inline-block bg-blue-600 text-white py-2 rounded text-sm font-medium hover:bg-blue-700">
            Reenviar email
          </Link>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="bg-white p-8 rounded shadow w-full max-w-sm text-center">
          <div className="text-4xl mb-4">❌</div>
          <h1 className="text-xl font-bold text-gray-900 mb-2">Link inválido</h1>
          <p className="text-sm text-gray-500 mb-6">El link de verificación no es válido.</p>
          <Link href="/auth/verify-email" className="w-full inline-block bg-blue-600 text-white py-2 rounded text-sm font-medium hover:bg-blue-700">
            Reenviar email
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="bg-white p-8 rounded shadow w-full max-w-sm text-center">
        <div className="text-4xl mb-4">✅</div>
        <h1 className="text-xl font-bold text-gray-900 mb-2">
          {already ? '¡Ya estás verificado!' : '¡Email verificado!'}
        </h1>
        <p className="text-sm text-gray-500 mb-6">Tu cuenta está activa. Ya podés iniciar sesión.</p>
        <Link href="/auth/login" className="w-full inline-block bg-blue-600 text-white py-2 rounded text-sm font-medium hover:bg-blue-700">
          Iniciar sesión
        </Link>
      </div>
    </div>
  );
}

export default function VerifiedPage() {
  return (
    <Suspense>
      <VerifiedContent />
    </Suspense>
  );
}
