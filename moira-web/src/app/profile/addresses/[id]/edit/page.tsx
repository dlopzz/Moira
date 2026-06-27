'use client';

import { useState, useEffect, use } from 'react';
import { useRouter } from 'next/navigation';
import { api, ApiError, AddressPayload, Address } from '@/lib/api';
import { AddressForm } from '@/components/AddressForm';

export default function EditAddressPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params);
  const router = useRouter();
  const [form, setForm] = useState<AddressPayload | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api.getAddresses().then((res) => {
      const address = res.data.find((a: Address) => a.id === Number(id));
      if (address) {
        const { id: _, ...rest } = address;
        setForm(rest);
      }
    });
  }, [id]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!form) return;
    setErrors({});
    setLoading(true);
    try {
      await api.updateAddress(Number(id), form);
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

  if (!form) return <p className="text-sm text-gray-500">Cargando...</p>;

  return (
    <div className="profile-content-box">
      <h2 className="profile-section-title">Editar dirección</h2>
      <AddressForm form={form} errors={errors} loading={loading} onChange={setForm} onSubmit={handleSubmit} />
    </div>
  );
}
