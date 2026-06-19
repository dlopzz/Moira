'use client';

import { useState, useEffect } from 'react';
import { api, ApiError, Customer } from '@/lib/api';
import { FormField } from '@/components/FormField';

export default function ProfilePage() {
  const [customer, setCustomer] = useState<Customer | null>(null);
  const [form, setForm] = useState({ first_name: '', last_name: '', email: '', date_of_birth: '' });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api.getProfile().then((res) => {
      setCustomer(res.data);
      setForm({
        first_name: res.data.first_name,
        last_name: res.data.last_name,
        email: res.data.email,
        date_of_birth: res.data.dob ?? '',
      });
    });
  }, []);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setErrors({});
    setMessage('');
    setLoading(true);
    try {
      const res = await api.updateProfile(form);
      setCustomer(res.data);
      setMessage('Perfil actualizado correctamente.');
    } catch (err) {
      if (err instanceof ApiError) {
        if (err.errors) {
          const flat: Record<string, string> = {};
          for (const [k, v] of Object.entries(err.errors)) flat[k] = v[0];
          setErrors(flat);
        } else {
          setMessage(err.message);
        }
      }
    } finally {
      setLoading(false);
    }
  }

  if (!customer) return <p className="text-sm text-gray-500">Cargando...</p>;

  return (
    <div className="bg-white rounded shadow p-6">
      <h2 className="text-lg font-bold mb-6">Mis datos</h2>
      {message && <p className="text-green-700 bg-green-50 border border-green-200 rounded p-3 text-sm mb-4">{message}</p>}
      <form onSubmit={handleSubmit} className="space-y-4 max-w-md">
        <FormField
          label="Nombre"
          value={form.first_name}
          onChange={(e) => setForm({ ...form, first_name: e.target.value })}
          error={errors.first_name}
          required
        />
        <FormField
          label="Apellido"
          value={form.last_name}
          onChange={(e) => setForm({ ...form, last_name: e.target.value })}
          error={errors.last_name}
          required
        />
        <FormField
          label="Email"
          type="email"
          value={form.email}
          onChange={(e) => setForm({ ...form, email: e.target.value })}
          error={errors.email}
          required
        />
        <FormField
          label="Fecha de nacimiento"
          type="date"
          value={form.date_of_birth}
          onChange={(e) => setForm({ ...form, date_of_birth: e.target.value })}
          error={errors.date_of_birth}
        />
        <button
          type="submit"
          disabled={loading}
          className="bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
        >
          {loading ? 'Guardando...' : 'Guardar cambios'}
        </button>
      </form>
    </div>
  );
}
