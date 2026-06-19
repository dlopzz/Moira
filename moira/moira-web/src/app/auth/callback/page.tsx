'use client';

import { useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { saveToken } from '@/lib/auth';

export default function AuthCallbackPage() {
  const router = useRouter();
  const params = useSearchParams();

  useEffect(() => {
    const token = params.get('token');
    const error = params.get('error');

    if (error === 'account_disabled') {
      router.push('/auth/login?error=account_disabled');
      return;
    }

    if (error) {
      router.push('/auth/login?error=google_failed');
      return;
    }

    if (token) {
      saveToken(token);
      router.push('/profile');
    }
  }, [params, router]);

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <p className="text-sm text-gray-400 animate-pulse">Iniciando sesión...</p>
    </div>
  );
}
