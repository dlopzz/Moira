'use client';

import { useEffect, useRef, useState } from 'react';
import { useRouter } from 'next/navigation';
import Script from 'next/script';
import { api, type Cart, type Address, type PaymentConfig, ApiError, formatPrice } from '@/lib/api';
import { getToken } from '@/lib/auth';
import Header from '@/components/Header';
import Breadcrumb from '@/components/Breadcrumb';
import CheckoutSteps from '@/components/CheckoutSteps';

declare global {
  interface Window {
    Decidir: new (endpoint: string, live?: boolean) => {
      setPublishableKey: (key: string) => void;
      createToken: (
        form: HTMLFormElement,
        callback: (status: number, response: DecidirTokenResponse) => void,
      ) => void;
    };
  }
}

type DecidirTokenResponse = {
  id: string;
  bin: string;
  payment_method_id: number;
  last_four_digits: string;
  expiration_month: string;
  expiration_year: string;
};

const DOC_TYPES = [
  { value: 'dni', label: 'DNI' },
  { value: 'le', label: 'LE' },
  { value: 'lc', label: 'LC' },
  { value: 'ci', label: 'CI' },
  { value: 'pasaporte', label: 'Pasaporte' },
];

const INSTALLMENTS = [1, 3, 6, 12, 18, 24];

function SimulatorPanel({
  total,
  processing,
  error,
  onPay,
}: {
  total: number;
  processing: boolean;
  error: string;
  onPay: (result: 'success' | 'fail') => void;
}) {
  return (
    <div className="bg-white rounded-xl border-2 border-dashed border-yellow-300 p-5">
      <div className="flex items-center gap-2 mb-3">
        <span className="text-xs font-semibold bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full">
          Modo simulación
        </span>
        <span className="text-xs text-gray-400">PayWay no configurado</span>
      </div>
      <p className="text-sm text-gray-500 mb-4">
        Usá estos botones para simular un pago aprobado o rechazado durante el desarrollo.
      </p>

      {error && (
        <p className="text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2 mb-4">
          {error}
        </p>
      )}

      <div className="flex gap-3">
        <button
          onClick={() => onPay('success')}
          disabled={processing}
          className="flex-1 bg-green-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-green-700 disabled:opacity-50 transition-colors"
        >
          {processing ? 'Procesando...' : `✓ Aprobar pago $${formatPrice(total)}`}
        </button>
        <button
          onClick={() => onPay('fail')}
          disabled={processing}
          className="flex-1 bg-red-500 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-red-600 disabled:opacity-50 transition-colors"
        >
          ✕ Rechazar pago
        </button>
      </div>
    </div>
  );
}

export default function CheckoutPaymentPage() {
  const router = useRouter();
  const formRef = useRef<HTMLFormElement>(null);

  const [cart, setCart] = useState<Cart | null>(null);
  const [address, setAddress] = useState<Address | null>(null);
  const [paymentConfig, setPaymentConfig] = useState<PaymentConfig | null>(null);
  const [loading, setLoading] = useState(true);
  const [sdkReady, setSdkReady] = useState(false);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState('');
  const [installments, setInstallments] = useState(1);
  const [docType, setDocType] = useState('dni');

  useEffect(() => {
    if (!getToken()) { router.push('/auth/login'); return; }
    Promise.all([api.getCheckout(), api.getPaymentConfig()])
      .then(([checkoutRes, configRes]) => {
        setCart(checkoutRes.cart);
        setAddress(checkoutRes.checkout_address);
        setPaymentConfig(configRes.data);
        if (!checkoutRes.checkout_address || !checkoutRes.cart.shipping?.code) {
          router.push('/checkout/shipping');
        }
      })
      .catch(() => setError('No se pudo cargar la información de pago. Recargá la página.'))
      .finally(() => setLoading(false));
  }, [router]);

  function initSdk() {
    if (!paymentConfig || !window.Decidir) return;
    const sdk = new window.Decidir(paymentConfig.sdk_endpoint);
    if (paymentConfig.public_key) {
      sdk.setPublishableKey(paymentConfig.public_key);
    }
    (window as Window & { __decidir?: typeof sdk }).__decidir = sdk;
    setSdkReady(true);
  }

  async function handlePay(e: React.FormEvent) {
    e.preventDefault();
    setError('');

    const sdk = (window as Window & { __decidir?: ReturnType<typeof window.Decidir> }).__decidir;
    if (!sdk) {
      setError('El SDK de pago no está listo. Recargá la página.');
      return;
    }
    if (!formRef.current) return;

    setProcessing(true);

    sdk.createToken(formRef.current, async (status, response) => {
      if (status !== 200 && status !== 201) {
        const tokenError = (response as unknown as { error?: { type?: string }[] })?.error;
        if (status === 422 && tokenError?.length) {
          setError('Datos de tarjeta inválidos. Verificá número, fecha de vencimiento y CVV.');
        } else if (status === 401) {
          setError('Error de configuración del procesador de pagos. Contactá al soporte.');
        } else {
          setError('No se pudo conectar con el procesador de pagos. Intentá nuevamente.');
        }
        setProcessing(false);
        return;
      }

      try {
        const holderName = (formRef.current!.querySelector('[data-decidir="card_holder_name"]') as HTMLInputElement)?.value ?? '';
        const holderDoc  = (formRef.current!.querySelector('[data-decidir="card_holder_doc_number"]') as HTMLInputElement)?.value ?? '';

        const res = await api.processPayment({
          token: response.id,
          bin: response.bin,
          payment_method_id: response.payment_method_id,
          installments,
          card_holder_name: holderName,
          card_holder_doc_type: docType,
          card_holder_doc_number: holderDoc,
        });
        router.push(`/checkout/success?order=${res.data.number}`);
      } catch (err) {
        if (err instanceof ApiError) setError(err.message);
        setProcessing(false);
      }
    });
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <div className="max-w-3xl mx-auto px-4 py-8 text-gray-400">Cargando...</div>
      </div>
    );
  }

  if (!cart && error) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <div className="max-w-3xl mx-auto px-4 py-8">
          <p className="text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg px-4 py-3">{error}</p>
        </div>
      </div>
    );
  }

  const sdkNotConfigured = !paymentConfig?.js_sdk_url || !paymentConfig?.public_key;

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />

      {/* JS SDK — URL y public key vienen de la BD */}
      {paymentConfig?.js_sdk_url && paymentConfig?.public_key && (
        <Script
          src={paymentConfig.js_sdk_url}
          strategy="afterInteractive"
          onLoad={initSdk}
        />
      )}

      <main className="max-w-3xl mx-auto px-4 py-8">
        <Breadcrumb crumbs={[{ name: 'Carrito', href: '/cart' }, { name: 'Checkout' }]} />
        <CheckoutSteps current={2} />

        <div className="grid md:grid-cols-[1fr_300px] gap-6">
          {/* Left */}
          <div className="space-y-4">
            {address && (
              <div className="bg-white rounded-xl border border-gray-200 p-4">
                <div className="flex items-center justify-between mb-2">
                  <h3 className="font-semibold text-gray-900 text-sm">Dirección de envío</h3>
                  <button onClick={() => router.push('/checkout/shipping')} className="text-xs text-blue-600 hover:underline">
                    Cambiar
                  </button>
                </div>
                <p className="text-sm font-medium text-gray-700">{address.label}</p>
                <p className="text-sm text-gray-500">{address.street}{address.address_line_2 ? `, ${address.address_line_2}` : ''}</p>
                <p className="text-sm text-gray-500">{address.city}, {address.state} ({address.zip_code})</p>
              </div>
            )}

            {sdkNotConfigured ? (
              <SimulatorPanel
                total={cart?.summary.total ?? 0}
                processing={processing}
                error={error}
                onPay={async (result) => {
                  setError('');
                  setProcessing(true);
                  try {
                    const res = await api.simulatePayment(result);
                    router.push(`/checkout/success?order=${res.data.number}`);
                  } catch (err) {
                    if (err instanceof ApiError) setError(err.message);
                    setProcessing(false);
                  }
                }}
              />
            ) : (
              <div className="bg-white rounded-xl border border-gray-200 p-5">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="font-semibold text-gray-900 text-sm">Datos de la tarjeta</h3>
                  <span className="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                    {paymentConfig?.is_sandbox ? 'Sandbox' : 'Producción'}
                  </span>
                </div>

                <form ref={formRef} onSubmit={handlePay} className="space-y-4">
                  <div>
                    <label className="block text-xs font-medium text-gray-700 mb-1">Número de tarjeta</label>
                    <input
                      type="text"
                      data-decidir="card_number"
                      placeholder="#### #### #### ####"
                      maxLength={19}
                      className="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-medium text-gray-700 mb-1">Nombre del titular</label>
                    <input
                      type="text"
                      data-decidir="card_holder_name"
                      placeholder="Como figura en la tarjeta"
                      className="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      required
                    />
                  </div>

                  <div className="grid grid-cols-3 gap-3">
                    <div>
                      <label className="block text-xs font-medium text-gray-700 mb-1">Mes</label>
                      <input
                        type="text"
                        data-decidir="card_expiration_month"
                        placeholder="MM"
                        maxLength={2}
                        className="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-700 mb-1">Año</label>
                      <input
                        type="text"
                        data-decidir="card_expiration_year"
                        placeholder="AA"
                        maxLength={2}
                        className="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-700 mb-1">CVV</label>
                      <input
                        type="text"
                        data-decidir="security_code"
                        placeholder="123"
                        maxLength={4}
                        className="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-medium text-gray-700 mb-1">Tipo de documento</label>
                      <select
                        data-decidir="card_holder_doc_type"
                        value={docType}
                        onChange={(e) => setDocType(e.target.value)}
                        className="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        {DOC_TYPES.map((d) => (
                          <option key={d.value} value={d.value}>{d.label}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-700 mb-1">Número de documento</label>
                      <input
                        type="text"
                        data-decidir="card_holder_doc_number"
                        placeholder="12345678"
                        className="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-medium text-gray-700 mb-1">Cuotas</label>
                    <select
                      value={installments}
                      onChange={(e) => setInstallments(Number(e.target.value))}
                      className="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      {INSTALLMENTS.map((n) => (
                        <option key={n} value={n}>
                          {n === 1 ? '1 cuota (sin interés)' : `${n} cuotas`}
                        </option>
                      ))}
                    </select>
                  </div>

                  {error && (
                    <p className="text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
                      {error}
                    </p>
                  )}

                  <button
                    type="submit"
                    disabled={processing || !sdkReady}
                    className="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 disabled:opacity-50 transition-colors text-sm mt-2"
                  >
                    {processing ? 'Procesando pago...' : `Pagar $${cart?.summary.formatPrice(total) ?? '0.00'}`}
                  </button>

                  {!sdkReady && (
                    <p className="text-xs text-gray-400 text-center">Cargando SDK de pago seguro...</p>
                  )}

                  <p className="text-xs text-gray-400 text-center flex items-center justify-center gap-1">
                    <span>🔒</span> Pago seguro procesado por PayWay
                  </p>
                </form>
              </div>
            )}

            <button onClick={() => router.push('/checkout/shipping')} className="text-sm text-gray-500 hover:text-gray-700">
              ← Volver a envío
            </button>
          </div>

          {/* Right: order summary */}
          {cart && (
            <div className="bg-white rounded-xl border border-gray-200 p-4 h-fit">
              <h3 className="font-semibold text-gray-900 text-sm mb-4">
                Resumen ({cart.summary.items_count} artículo{cart.summary.items_count !== 1 ? 's' : ''})
              </h3>
              <div className="divide-y divide-gray-100">
                {cart.items.map((item) => (
                  <div key={item.id} className="py-2 flex justify-between text-sm gap-2">
                    <span className="text-gray-700 truncate">
                      {item.name}
                      {item.variant_label && <span className="text-gray-400 block text-xs">{item.variant_label}</span>}
                      <span className="text-gray-400"> ×{item.quantity}</span>
                    </span>
                    <span className="text-gray-900 font-medium flex-none">${item.subformatPrice(total)}</span>
                  </div>
                ))}
              </div>
              <div className="border-t border-gray-100 mt-3 pt-3 space-y-1.5 text-sm">
                <div className="flex justify-between text-gray-500">
                  <span>Subtotal</span><span>${cart.summary.subformatPrice(total)}</span>
                </div>
                <div className="flex justify-between text-gray-500">
                  <span>Envío{cart.shipping?.label ? ` (${cart.shipping.label})` : ''}</span>
                  <span className={cart.summary.shipping_cost === 0 ? 'text-green-600' : ''}>
                    {cart.summary.shipping_cost === 0 ? 'Gratis' : `$${formatPrice(cart.summary.shipping_cost)}`}
                  </span>
                </div>
                {cart.summary.discount > 0 && (
                  <div className="flex justify-between text-green-600">
                    <span>Descuento{cart.coupon_code ? ` (${cart.coupon_code})` : ''}</span>
                    <span>−${formatPrice(cart.summary.discount)}</span>
                  </div>
                )}
                <div className="flex justify-between font-bold text-gray-900 text-base pt-1 border-t border-gray-100">
                  <span>Total</span><span>${cart.summary.formatPrice(total)}</span>
                </div>
              </div>
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
