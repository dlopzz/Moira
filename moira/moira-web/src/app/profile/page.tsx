'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { api, ApiError, Customer } from '@/lib/api';
import { clearToken } from '@/lib/auth';

export default function ProfilePage() {
  const router = useRouter();
  const [customer, setCustomer] = useState<Customer | null>(null);

  useEffect(() => {
    api.getProfile().then((res) => setCustomer(res.data)).catch(() => {});
  }, []);

  async function handleLogout() {
    try {
      await api.logout();
    } catch (e) {
      if (e instanceof ApiError && e.status !== 401) throw e;
    }
    clearToken();
    router.push('/');
  }

  if (!customer) return <p>Cargando...</p>;

  return (
    <>
      <p>
        Hola <strong>{customer.name}</strong> (¿no sos <strong>{customer.name}</strong>?{' '}
        <button
          onClick={handleLogout}
          style={{ background: 'none', border: 'none', padding: 0, cursor: 'pointer', color: 'inherit', textDecoration: 'underline', font: 'inherit' }}
        >
          Cerrar sesión
        </button>
        )
      </p>
      <p>
        Desde el panel de control de tu cuenta podés ver tus{' '}
        <Link href="/profile/orders">pedidos recientes</Link>, gestionar tus{' '}
        <Link href="/profile/addresses">direcciones de envío y facturación</Link> y{' '}
        <Link href="/profile/edit-account">editar tu contraseña y los detalles de tu cuenta</Link>.
      </p>
    </>
  );
}
