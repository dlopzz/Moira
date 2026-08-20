'use client';

import { Suspense, useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { saveToken } from '@/lib/auth';
import { api } from '@/lib/api';

function CallbackContent() {
  const router = useRouter();
  const params = useSearchParams();

  useEffect(() => {
    const code = params.get('code');
    const error = params.get('error');

    if (error === 'account_disabled') {
      router.push('/auth/login?error=account_disabled');
      return;
    }

    if (error) {
      router.push('/auth/login?error=google_failed');
      return;
    }

    if (!code) {
      router.push('/auth/login?error=google_failed');
      return;
    }

    // El backend deja un código de un solo uso, no el token: así el token no
    // queda en el historial del navegador, en los access logs ni en el Referer.
    let cancelled = false;

    api
      .exchangeSocialCode(code)
      .then(({ token }) => {
        if (cancelled) return;
        saveToken(token);
        // replace, no push: saca el código de la URL y del historial.
        router.replace('/profile');
      })
      .catch(() => {
        if (cancelled) return;
        router.replace('/auth/login?error=google_failed');
      });

    return () => {
      cancelled = true;
    };
  }, [params, router]);

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <p className="text-sm text-gray-400 animate-pulse">Iniciando sesión...</p>
    </div>
  );
}

export default function AuthCallbackPage() {
  return (
    <Suspense>
      <CallbackContent />
    </Suspense>
  );
}
