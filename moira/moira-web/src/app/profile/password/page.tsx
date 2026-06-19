'use client';

import { useState } from 'react';
import { api, ApiError } from '@/lib/api';
import { FormField } from '@/components/FormField';

export default function PasswordPage() {
  const [form, setForm] = useState({ current_password: '', password: '', password_confirmation: '' });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [message, setMessage] = useState('');
  const [isError, setIsError] = useState(false);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setErrors({});
    setMessage('');
    setLoading(true);
    try {
      const res = await api.updatePassword(form);
      setIsError(false);
      setMessage(res.message);
      setForm({ current_password: '', password: '', password_confirmation: '' });
    } catch (err) {
      if (err instanceof ApiError) {
        setIsError(true);
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
    <div className="bg-white rounded shadow p-6">
      <h2 className="text-lg font-bold mb-6">Cambiar contraseña</h2>
      {message && (
        <p className={`${isError ? 'text-red-600 bg-red-50 border-red-200' : 'text-green-700 bg-green-50 border-green-200'} border rounded p-3 text-sm mb-4`}>
          {message}
        </p>
      )}
      <form onSubmit={handleSubmit} className="space-y-4 max-w-md">
        <FormField
          label="Contraseña actual"
          type="password"
          value={form.current_password}
          onChange={(e) => setForm({ ...form, current_password: e.target.value })}
          error={errors.current_password}
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
          label="Confirmar nueva contraseña"
          type="password"
          value={form.password_confirmation}
          onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })}
          required
        />
        <button
          type="submit"
          disabled={loading}
          className="bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
        >
          {loading ? 'Guardando...' : 'Cambiar contraseña'}
        </button>
      </form>
    </div>
  );
}
