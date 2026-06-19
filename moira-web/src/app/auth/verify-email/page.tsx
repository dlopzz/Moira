'use client';

import { useState } from 'react';
import Link from 'next/link';
import { api, ApiError } from '@/lib/api';

export default function VerifyEmailPage() {
  const [sent, setSent] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  async function handleResend() {
    setLoading(true);
    setError('');
    try {
      await api.resendVerification();
      setSent(true);
    } catch (err) {
      if (err instanceof ApiError) setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="bg-white p-8 rounded shadow w-full max-w-sm text-center">
        <div className="text-4xl mb-4">📧</div>
        <h1 className="text-xl font-bold text-gray-900 mb-2">Verificá tu email</h1>
        <p className="text-sm text-gray-500 mb-6">
          Te enviamos un link de verificación. Revisá tu bandeja de entrada y hacé click en el link para activar tu cuenta.
        </p>

        {sent ? (
          <p className="text-sm text-green-600 bg-green-50 border border-green-100 rounded-lg px-4 py-3">
            Email reenviado. Revisá tu bandeja de entrada.
          </p>
        ) : (
          <>
            {error && (
              <p className="text-sm text-red-600 mb-4">{error}</p>
            )}
            <button
              onClick={handleResend}
              disabled={loading}
              className="w-full bg-blue-600 text-white py-2 rounded text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
            >
              {loading ? 'Enviando...' : 'Reenviar email de verificación'}
            </button>
          </>
        )}

        <p className="mt-4 text-sm text-gray-400">
          <Link href="/auth/login" className="text-blue-600 hover:underline">
            Volver al login
          </Link>
        </p>
      </div>
    </div>
  );
}
