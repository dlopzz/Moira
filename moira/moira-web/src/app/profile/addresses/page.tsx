'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { api, ApiError, Address } from '@/lib/api';

export default function AddressesPage() {
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState('');

  async function load() {
    const res = await api.getAddresses();
    setAddresses(res.data);
    setLoading(false);
  }

  useEffect(() => { load(); }, []);

  async function handleDelete(id: number) {
    if (!confirm('¿Eliminar esta dirección?')) return;
    try {
      await api.deleteAddress(id);
      setAddresses((prev) => prev.filter((a) => a.id !== id));
    } catch (err) {
      if (err instanceof ApiError) setMessage(err.message);
    }
  }

  async function handleSetDefault(id: number) {
    try {
      await api.setDefaultAddress(id);
      load();
    } catch (err) {
      if (err instanceof ApiError) setMessage(err.message);
    }
  }

  if (loading) return <p className="text-sm text-gray-500">Cargando...</p>;

  return (
    <div className="bg-white rounded shadow p-6">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-bold">Mis direcciones</h2>
        <Link href="/profile/addresses/new" className="bg-blue-600 text-white px-3 py-1.5 rounded text-sm font-medium hover:bg-blue-700">
          + Agregar
        </Link>
      </div>

      {message && <p className="text-red-600 text-sm mb-4">{message}</p>}

      {addresses.length === 0 ? (
        <p className="text-gray-500 text-sm">No tenés direcciones guardadas.</p>
      ) : (
        <ul className="space-y-3">
          {addresses.map((a) => (
            <li key={a.id} className="border rounded p-4">
              <div className="flex items-start justify-between">
                <div>
                  {a.label && <p className="font-medium text-sm">{a.label}</p>}
                  <p className="text-sm">{a.street}{a.address_line_2 ? `, ${a.address_line_2}` : ''}</p>
                  <p className="text-sm">{a.city}, {a.state} {a.zip_code} — {a.country}</p>
                  {a.telephone && <p className="text-sm text-gray-500">{a.telephone}</p>}
                  {a.is_default && (
                    <span className="inline-block mt-1 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">
                      Predeterminada
                    </span>
                  )}
                </div>
                <div className="flex gap-2 text-sm ml-4 shrink-0">
                  <Link href={`/profile/addresses/${a.id}/edit`} className="text-blue-600 hover:underline">Editar</Link>
                  {!a.is_default && (
                    <button onClick={() => handleSetDefault(a.id)} className="text-gray-600 hover:underline">
                      Predeterminar
                    </button>
                  )}
                  <button onClick={() => handleDelete(a.id)} className="text-red-600 hover:underline">Eliminar</button>
                </div>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
