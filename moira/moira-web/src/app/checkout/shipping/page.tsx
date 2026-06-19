'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { api, type Address, type AddressPayload, type ShippingRate, ApiError } from '@/lib/api';
import { getToken } from '@/lib/auth';
import Header from '@/components/Header';
import Breadcrumb from '@/components/Breadcrumb';
import CheckoutSteps from '@/components/CheckoutSteps';
import { AddressForm } from '@/components/AddressForm';

const EMPTY: AddressPayload = {
  label: '', street: '', address_line_2: '', city: '',
  state: '', zip_code: '', country: 'AR', telephone: '', is_default: false,
};

type Stage = 'address' | 'shipping-method';

export default function CheckoutShippingPage() {
  const router = useRouter();

  // Address stage
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [selected, setSelected] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [newForm, setNewForm] = useState<AddressPayload>(EMPTY);
  const [newErrors, setNewErrors] = useState<Record<string, string>>({});
  const [formSaving, setFormSaving] = useState(false);

  // Shipping method stage
  const [stage, setStage] = useState<Stage>('address');
  const [rates, setRates] = useState<ShippingRate[]>([]);
  const [selectedRate, setSelectedRate] = useState<ShippingRate | null>(null);
  const [loadingRates, setLoadingRates] = useState(false);
  const [ratesError, setRatesError] = useState('');
  const [confirmingSave, setConfirmingSave] = useState(false);

  useEffect(() => {
    if (!getToken()) { router.push('/auth/login'); return; }
    Promise.all([api.getAddresses(), api.getCheckout()])
      .then(([addrRes, checkoutRes]) => {
        setAddresses(addrRes.data);
        if (checkoutRes.checkout_address) {
          setSelected(checkoutRes.checkout_address.id);
        } else {
          const def = addrRes.data.find((a) => a.is_default);
          if (def) setSelected(def.id);
          else if (addrRes.data.length > 0) setSelected(addrRes.data[0].id);
        }
        // If address is already set and cart has a shipping method, go to method stage
        if (checkoutRes.checkout_address && checkoutRes.cart.shipping?.code) {
          loadRates(false);
        }
      })
      .finally(() => setLoading(false));
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [router]);

  async function loadRates(showStage = true) {
    setLoadingRates(true);
    setRatesError('');
    try {
      const res = await api.getShippingRates();
      setRates(res.data);
      if (showStage) setStage('shipping-method');
    } catch (err) {
      if (err instanceof ApiError) setRatesError(err.message);
      else setRatesError('Error al cargar tarifas de envío.');
    } finally {
      setLoadingRates(false);
    }
  }

  async function handleAddressContinue() {
    if (!selected) return;
    setSaving(true);
    try {
      await api.setCheckoutAddress(selected);
      await loadRates(true);
    } finally {
      setSaving(false);
    }
  }

  async function handleShippingContinue() {
    if (!selectedRate) return;
    setConfirmingSave(true);
    try {
      await api.selectShipping(selectedRate);
      router.push('/checkout/payment');
    } catch {
      setConfirmingSave(false);
    }
  }

  async function handleNewAddressSubmit(e: React.FormEvent) {
    e.preventDefault();
    setNewErrors({});
    setFormSaving(true);
    try {
      const res = await api.createAddress(newForm);
      const newAddr = res.data;
      setAddresses((prev) => [...prev, newAddr]);
      setSelected(newAddr.id);
      setNewForm(EMPTY);
      setShowForm(false);
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        const flat: Record<string, string> = {};
        for (const [k, v] of Object.entries(err.errors)) flat[k] = v[0];
        setNewErrors(flat);
      }
    } finally {
      setFormSaving(false);
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <div className="max-w-3xl mx-auto px-4 py-8 text-gray-400">Cargando...</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <main className="max-w-3xl mx-auto px-4 py-8">
        <Breadcrumb crumbs={[{ name: 'Carrito', href: '/cart' }, { name: 'Checkout' }]} />
        <CheckoutSteps current={1} />

        {/* ── Dirección ────────────────────────────────── */}
        <div className="mb-8">
          <div className="flex items-center justify-between mb-4">
            <h1 className="text-xl font-bold text-gray-900">1. Dirección de envío</h1>
            {stage === 'shipping-method' && (
              <button
                onClick={() => setStage('address')}
                className="text-sm text-blue-600 hover:underline"
              >
                Cambiar
              </button>
            )}
          </div>

          {stage === 'address' ? (
            <>
              <div className="space-y-3 mb-6">
                {addresses.map((addr) => (
                  <label
                    key={addr.id}
                    className={`flex items-start gap-4 p-4 bg-white rounded-xl border-2 cursor-pointer transition-colors ${
                      selected === addr.id ? 'border-blue-500' : 'border-gray-200 hover:border-gray-300'
                    }`}
                  >
                    <input
                      type="radio"
                      name="address"
                      value={addr.id}
                      checked={selected === addr.id}
                      onChange={() => setSelected(addr.id)}
                      className="mt-1 accent-blue-600"
                    />
                    <div className="flex-1">
                      <div className="flex items-center gap-2">
                        <span className="font-medium text-gray-900 text-sm">{addr.label}</span>
                        {addr.is_default && (
                          <span className="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">Principal</span>
                        )}
                      </div>
                      <p className="text-sm text-gray-600 mt-0.5">
                        {addr.street}{addr.address_line_2 ? `, ${addr.address_line_2}` : ''}
                      </p>
                      <p className="text-sm text-gray-500">{addr.city}, {addr.state} ({addr.zip_code})</p>
                      <p className="text-sm text-gray-500">{addr.telephone}</p>
                    </div>
                  </label>
                ))}
                {addresses.length === 0 && !showForm && (
                  <p className="text-sm text-gray-400 text-center py-4">No tenés direcciones guardadas.</p>
                )}
              </div>

              {showForm ? (
                <div className="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                  <div className="flex items-center justify-between mb-4">
                    <h2 className="font-semibold text-gray-900 text-sm">Nueva dirección</h2>
                    <button onClick={() => setShowForm(false)} className="text-sm text-gray-400 hover:text-gray-600">
                      Cancelar
                    </button>
                  </div>
                  <AddressForm
                    form={newForm}
                    errors={newErrors}
                    loading={formSaving}
                    onChange={setNewForm}
                    onSubmit={handleNewAddressSubmit}
                  />
                </div>
              ) : (
                <button
                  onClick={() => setShowForm(true)}
                  className="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-700 font-medium mb-6"
                >
                  <span className="text-lg leading-none">+</span> Agregar nueva dirección
                </button>
              )}

              <div className="flex items-center justify-between pt-4 border-t border-gray-200">
                <button onClick={() => router.push('/cart')} className="text-sm text-gray-500 hover:text-gray-700">
                  ← Volver al carrito
                </button>
                <button
                  onClick={handleAddressContinue}
                  disabled={!selected || saving || loadingRates}
                  className="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-40 transition-colors text-sm"
                >
                  {saving || loadingRates ? 'Cargando...' : 'Ver opciones de envío →'}
                </button>
              </div>
            </>
          ) : (
            // Summary of selected address
            (() => {
              const addr = addresses.find((a) => a.id === selected);
              return addr ? (
                <div className="bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm text-gray-700">
                  <p className="font-medium">{addr.label}</p>
                  <p>{addr.street}{addr.address_line_2 ? `, ${addr.address_line_2}` : ''}</p>
                  <p>{addr.city}, {addr.state} ({addr.zip_code})</p>
                </div>
              ) : null;
            })()
          )}
        </div>

        {/* ── Método de envío ───────────────────────────── */}
        {stage === 'shipping-method' && (
          <div>
            <h2 className="text-xl font-bold text-gray-900 mb-4">2. Método de envío</h2>

            {ratesError && (
              <p className="text-sm text-red-600 bg-red-50 rounded-lg px-4 py-3 mb-4">{ratesError}</p>
            )}

            {loadingRates ? (
              <div className="text-gray-400 text-sm py-4">Calculando tarifas...</div>
            ) : rates.length === 0 ? (
              <div className="text-gray-400 text-sm py-4">No hay métodos de envío disponibles.</div>
            ) : (
              <div className="space-y-3 mb-6">
                {rates.map((rate) => (
                  <label
                    key={rate.code}
                    className={`flex items-center gap-4 p-4 bg-white rounded-xl border-2 cursor-pointer transition-colors ${
                      selectedRate?.code === rate.code
                        ? 'border-blue-500'
                        : 'border-gray-200 hover:border-gray-300'
                    }`}
                  >
                    <input
                      type="radio"
                      name="shipping-rate"
                      value={rate.code}
                      checked={selectedRate?.code === rate.code}
                      onChange={() => setSelectedRate(rate)}
                      className="accent-blue-600"
                    />
                    <div className="flex-1">
                      <p className="font-medium text-gray-900 text-sm">{rate.label}</p>
                      <p className="text-xs text-gray-500 mt-0.5">{rate.estimated_days}</p>
                    </div>
                    <span className="font-bold text-gray-900 text-sm">
                      {rate.price === 0 ? 'Gratis' : `$${rate.price.toFixed(2)}`}
                    </span>
                  </label>
                ))}
              </div>
            )}

            <div className="flex items-center justify-between pt-4 border-t border-gray-200">
              <button
                onClick={() => setStage('address')}
                className="text-sm text-gray-500 hover:text-gray-700"
              >
                ← Cambiar dirección
              </button>
              <button
                onClick={handleShippingContinue}
                disabled={!selectedRate || confirmingSave}
                className="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-40 transition-colors text-sm"
              >
                {confirmingSave ? 'Guardando...' : 'Continuar al pago →'}
              </button>
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
