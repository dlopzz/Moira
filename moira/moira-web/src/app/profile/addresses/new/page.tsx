'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { api, ApiError, AddressPayload } from '@/lib/api';
import { AddressForm } from '@/components/AddressForm';

const EMPTY: AddressPayload = {
  label: '',
  street: '',
  address_line_2: '',
  city: '',
  state: '',
  zip_code: '',
  country: 'AR',
  telephone: '',
  is_default: false,
};

export default function NewAddressPage() {
  const router = useRouter();
  const [form, setForm] = useState<AddressPayload>(EMPTY);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setErrors({});
    setLoading(true);
    try {
      await api.createAddress(form);
      router.push('/profile/addresses');
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        const flat: Record<string, string> = {};
        for (const [k, v] of Object.entries(err.errors)) flat[k] = v[0];
        setErrors(flat);
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="bg-white rounded shadow p-6">
      <h2 className="text-lg font-bold mb-6">Nueva dirección</h2>
      <AddressForm form={form} errors={errors} loading={loading} onChange={setForm} onSubmit={handleSubmit} />
    </div>
  );
}
