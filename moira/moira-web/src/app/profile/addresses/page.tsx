'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { api, ApiError, type Address } from '@/lib/api';

function AddressLines({ a }: { a: Address }) {
  const lines: string[] = [];
  if (a.street) lines.push(a.street);
  if (a.address_line_2) lines.push(a.address_line_2);
  const cityState = [a.city, a.state].filter(Boolean).join(', ');
  if (cityState) lines.push(cityState);
  if (a.zip_code) lines.push(a.zip_code);
  if (a.telephone) lines.push(a.telephone);
  return (
    <>
      {lines.map((line, i) => (
        <span key={i}>{line}{i < lines.length - 1 && <br />}</span>
      ))}
    </>
  );
}

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

  async function handleSetDefault(id: number, type: 'billing' | 'shipping') {
    try {
      await api.setDefaultAddress(id, type);
      load();
    } catch (err) {
      if (err instanceof ApiError) setMessage(err.message);
    }
  }

  if (loading) return <p className="woocommerce-addresses-desc">Cargando...</p>;

  const billing = addresses.find((a) => a.is_default_billing);
  const shipping = addresses.find((a) => a.is_default_shipping);

  return (
    <div>
      {/* ── Avanam-style default addresses ── */}
      <p className="woocommerce-addresses-desc">
        Las siguientes direcciones se utilizarán de forma predeterminada en la página de pago.
      </p>

      <div className="woocommerce-Addresses col2-set addresses">
        <div className="u-column1 col-1 woocommerce-Address">
          <header className="woocommerce-Address-title title">
            <h2>Dirección de facturación</h2>
            <Link
              href={billing ? `/profile/addresses/${billing.id}/edit` : '/profile/addresses/new'}
              className="edit"
            >
              {billing ? 'Editar' : 'Agregar'}
            </Link>
          </header>
          {billing ? (
            <address><AddressLines a={billing} /></address>
          ) : (
            <p className="no-address">No has configurado esta dirección.</p>
          )}
        </div>

        <div className="u-column2 col-2 woocommerce-Address">
          <header className="woocommerce-Address-title title">
            <h2>Dirección de envío</h2>
            <Link
              href={shipping ? `/profile/addresses/${shipping.id}/edit` : '/profile/addresses/new'}
              className="edit"
            >
              {shipping ? 'Editar' : 'Agregar'}
            </Link>
          </header>
          {shipping ? (
            <address><AddressLines a={shipping} /></address>
          ) : (
            <p className="no-address">No has configurado esta dirección.</p>
          )}
        </div>
      </div>

      {/* ── Full address book ── */}
      <div className="addresses-book">
        <div className="addresses-book-header">
          <h2>Mis direcciones</h2>
          <Link href="/profile/addresses/new" className="button">
            + Agregar
          </Link>
        </div>

        {message && (
          <p style={{ color: 'var(--color-alert)', fontSize: 14, marginBottom: '1em' }}>{message}</p>
        )}

        {addresses.length === 0 ? (
          <p className="woocommerce-addresses-desc">No tenés direcciones guardadas.</p>
        ) : (
          <ul className="addresses-list">
            {addresses.map((a) => (
              <li key={a.id} className="address-item">
                <div className="address-item-info">
                  <strong>{a.label}</strong>
                  <address><AddressLines a={a} /></address>
                  <div className="address-badges">
                    {a.is_default_billing && (
                      <span className="address-badge address-badge-billing">Facturación</span>
                    )}
                    {a.is_default_shipping && (
                      <span className="address-badge address-badge-shipping">Envío</span>
                    )}
                  </div>
                </div>

                <div className="address-item-actions">
                  <Link href={`/profile/addresses/${a.id}/edit`}>Editar</Link>
                  {!a.is_default_billing && (
                    <button onClick={() => handleSetDefault(a.id, 'billing')}>
                      Default facturación
                    </button>
                  )}
                  {!a.is_default_shipping && (
                    <button onClick={() => handleSetDefault(a.id, 'shipping')}>
                      Default envío
                    </button>
                  )}
                  <button className="danger" onClick={() => handleDelete(a.id)}>
                    Eliminar
                  </button>
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}
