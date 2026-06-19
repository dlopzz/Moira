'use client';

import { useState, useEffect, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { api, ApiError } from '@/lib/api';
import { FormField } from '@/components/FormField';

function ResetPasswordForm() {
  const router = useRouter();
  const params = useSearchParams();
  const [form, setForm] = useState({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    setForm((f) => ({
      ...f,
      token: params.get('token') ?? '',
      email: params.get('email') ?? '',
    }));
  }, [params]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setErrors({});
    setMessage('');
    setLoading(true);
    try {
      const res = await api.resetPassword(form);
      setMessage(res.message);
      setTimeout(() => router.push('/auth/login'), 2000);
    } catch (err) {
      if (err instanceof ApiError) {
        setMessage(err.message);
        if (err.errors) {
          const flat: Record<string, string> = {};
          for (const [k, v] of Object.entries(err.errors)) flat[k] = v[0];
          setErrors(flat);
        }
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="bg-white p-8 rounded shadow w-full max-w-sm">
        <h1 className="text-xl font-bold mb-6">Nueva contraseña</h1>
        {message && <p className="text-green-700 bg-green-50 border border-green-200 rounded p-3 text-sm mb-4">{message}</p>}
        <form onSubmit={handleSubmit} className="space-y-4">
          <FormField
            label="Email"
            type="email"
            value={form.email}
            onChange={(e) => setForm({ ...form, email: e.target.value })}
            error={errors.email}
            required
          />
          <FormField
            label="Nueva contraseña"
            type="password"
            value={form.password}
            onChange={(e) => setForm({ ...form, password: e.target.value })}
            error={errors.password}
            required
          />
          <FormField
            label="Confirmar contraseña"
            type="password"
            value={form.password_confirmation}
            onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })}
            required
          />
          <button
            type="submit"
            disabled={loading}
            className="w-full bg-blue-600 text-white py-2 rounded text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
          >
            {loading ? 'Guardando...' : 'Guardar contraseña'}
          </button>
        </form>
      </div>
    </div>
  );
}

export default function ResetPasswordPage() {
  return (
    <Suspense>
      <ResetPasswordForm />
    </Suspense>
  );
}
