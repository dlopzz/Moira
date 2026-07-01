'use client';

import { useEffect, useRef, useState } from 'react';
import { useRouter } from 'next/navigation';
import Script from 'next/script';
import {
  api, type Cart, type Address, type GuestShippingAddress,
  type PaymentConfig, type PaymentTokenData, ApiError, formatPrice,
} from '@/lib/api';

// Maps BIN prefix → PayWay payment_method_id.
// Used as fallback when the JS SDK doesn't return the field.
function detectPaymentMethodIdFromBin(bin: string): number | undefined {
  const b = bin.replace(/\s/g, '');
  const n = parseInt(b.substring(0, 4), 10);
  if (b[0] === '4') return 1;               // Visa crédito/débito
  if (b.startsWith('34') || b.startsWith('37')) return 65; // Amex
  if (b.startsWith('36')) return 8;          // Diners
  if (b.startsWith('589562')) return 24;     // Naranja
  if (n >= 2221 && n <= 2720) return 15;    // Mastercard (rango nuevo)
  if (b[0] === '5') return 15;              // Mastercard
  return undefined;
}

function saveGuestPrefill(addr: GuestShippingAddress) {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem('guest_checkout_prefill', JSON.stringify({
      email:     addr.email,
      firstName: addr.firstname,
      lastName:  addr.lastname,
    }));
  } catch { /* ignore */ }
}
import { getToken } from '@/lib/auth';
import Header from '@/components/Header';
import Breadcrumb from '@/components/Breadcrumb';

declare global {
  interface Window {
    Decidir: new (endpoint: string, live?: boolean) => {
      setPublishableKey: (key: string) => void;
      device_unique_identifier?: string;
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
  // v1 SDK: flat field. v2 SDK: nested under payment_method
  payment_method_id?: number;
  payment_method?: { id: number; name: string; payment_type?: string };
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
  onBack,
}: {
  total: number;
  processing: boolean;
  error: string;
  onPay: (result: 'success' | 'fail') => void;
  onBack: () => void;
}) {
  return (
    <div className="co-simulator-panel">
      <p className="co-simulator-label">Modo simulación — PayWay no configurado</p>
      <p className="co-muted" style={{ fontSize: 13, marginBottom: '1em' }}>
        Usá estos botones para simular un pago aprobado o rechazado.
      </p>
      {error && <p className="co-error" style={{ marginBottom: '0.75em' }}>{error}</p>}
      <div className="co-simulator-actions">
        <button
          type="button" className="button alt"
          onClick={() => onPay('success')}
          disabled={processing}
        >
          {processing ? 'Procesando...' : `✓ Aprobar pago $${formatPrice(total)}`}
        </button>
        <button
          type="button" className="button"
          onClick={() => onPay('fail')}
          disabled={processing}
          style={{ background: 'var(--global-palette6)', color: '#fff' }}
        >
          ✕ Rechazar pago
        </button>
      </div>
      <div className="co-actions" style={{ marginTop: '1.5em' }}>
        <button type="button" className="co-back-link" onClick={onBack}>
          ← Volver a envío
        </button>
      </div>
    </div>
  );
}

export default function CheckoutPaymentPage() {
  const router = useRouter();
  const formRef = useRef<HTMLFormElement>(null);
  const isAuth = !!getToken();

  const [cart, setCart]                   = useState<Cart | null>(null);
  const [authAddress, setAuthAddress]     = useState<Address | null>(null);
  const [guestAddress, setGuestAddress]   = useState<GuestShippingAddress | null>(null);
  const [paymentConfig, setPaymentConfig] = useState<PaymentConfig | null>(null);
  const [loading, setLoading]             = useState(true);
  const [sdkReady, setSdkReady]           = useState(false);
  const [processing, setProcessing]       = useState(false);
  const [error, setError]                 = useState('');
  const [installments, setInstallments]   = useState(1);
  const [docType, setDocType]             = useState('dni');

  useEffect(() => {
    if (isAuth) {
      Promise.all([api.getCheckout(), api.getPaymentConfig()])
        .then(([checkoutRes, configRes]) => {
          setCart(checkoutRes.cart);
          setAuthAddress(checkoutRes.checkout_address);
          setPaymentConfig(configRes.data);
          if (!checkoutRes.checkout_address || !checkoutRes.cart.shipping?.code) {
            router.push('/checkout/shipping');
          }
        })
        .catch(() => setError('No se pudo cargar la información de pago. Recargá la página.'))
        .finally(() => setLoading(false));
    } else {
      Promise.all([api.getGuestCheckout(), api.getCart(), api.getPaymentConfig()])
        .then(([guestRes, cartRes, configRes]) => {
          setCart(cartRes.data);
          setGuestAddress(guestRes.shipping_address);
          setPaymentConfig(configRes.data);
          if (!guestRes.shipping_address || !guestRes.shipping_method) {
            router.push('/checkout/shipping');
          }
        })
        .catch(() => setError('No se pudo cargar la información de pago. Recargá la página.'))
        .finally(() => setLoading(false));
    }
  }, [isAuth, router]);

  function initSdk() {
    if (!paymentConfig || !window.Decidir) return;
    const sdk = new window.Decidir(paymentConfig.sdk_endpoint);
    if (paymentConfig.public_key) sdk.setPublishableKey(paymentConfig.public_key);
    // Disable ThreatMetrix fraud detection so token is created without device fingerprint,
    // matching server-side tokenization behavior (avoids PayWay Scala None error on charge).
    (sdk as typeof sdk & { dontUseFraudPrevention?: boolean }).dontUseFraudPrevention = true;
    (window as Window & { __decidir?: typeof sdk }).__decidir = sdk;
    setSdkReady(true);
  }

  async function handleSimulate(result: 'success' | 'fail') {
    setError('');
    setProcessing(true);
    try {
      const res = isAuth
        ? await api.simulatePayment(result)
        : await api.simulateGuestPayment(result);
      if (!isAuth && guestAddress) saveGuestPrefill(guestAddress);
      router.push(`/checkout/success?order=${res.data.number}`);
    } catch (err) {
      if (err instanceof ApiError) setError(err.message);
      setProcessing(false);
    }
  }

  async function handlePay(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    const sdk = (window as Window & { __decidir?: InstanceType<typeof window.Decidir> }).__decidir;
    if (!sdk) { setError('El SDK de pago no está listo. Recargá la página.'); return; }
    if (!formRef.current) return;
    setProcessing(true);
    sdk.createToken(formRef.current, async (status, response) => {
      console.debug('[PayWay] createToken status:', status, 'response:', JSON.stringify(response));
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
        // v1 SDK: flat `payment_method_id`. v2 SDK: nested under `payment_method.id`.
        // Final fallback: detect from BIN prefix.
        const paymentMethodId =
          response.payment_method_id ??
          response.payment_method?.id ??
          detectPaymentMethodIdFromBin(response.bin ?? '');
        if (!paymentMethodId) {
          setError('No se pudo identificar el tipo de tarjeta. Verificá el número ingresado.');
          setProcessing(false);
          return;
        }
        const deviceUid = sdk.device_unique_identifier ?? undefined;
        const payload: PaymentTokenData = {
          token: response.id,
          bin: response.bin,
          payment_method_id: paymentMethodId,
          installments,
          card_holder_name: holderName,
          card_holder_doc_type: docType,
          card_holder_doc_number: holderDoc,
          device_unique_identifier: deviceUid,
        };
        const res = isAuth
          ? await api.processPayment(payload)
          : await api.processGuestPayment(payload);
        if (!isAuth && guestAddress) saveGuestPrefill(guestAddress);
        router.push(`/checkout/success?order=${res.data.number}`);
      } catch (err) {
        if (err instanceof ApiError) {
          // Validation errors (missing fields) have `errors` object → show inline.
          // PayWay rejections/errors have only `message` → send to fail page.
          if (!err.errors) {
            router.push('/checkout/fail');
            return;
          }
          setError(err.message);
        }
        setProcessing(false);
      }
    });
  }

  if (loading) {
    return (
      <>
        <Header />
        <div className="woocommerce woocommerce-page">
          <div className="site-container co-page">
            <p className="co-loading">Cargando...</p>
          </div>
        </div>
      </>
    );
  }

  if (!cart && error) {
    return (
      <>
        <Header />
        <div className="woocommerce woocommerce-page">
          <div className="site-container co-page">
            <p className="co-error">{error}</p>
          </div>
        </div>
      </>
    );
  }

  const sdkNotConfigured = !paymentConfig?.js_sdk_url || !paymentConfig?.public_key;

  // Normalised address for display
  const addrLabel  = authAddress?.label ?? (guestAddress ? `${guestAddress.firstname} ${guestAddress.lastname}` : null);
  const addrStreet = authAddress?.street ?? guestAddress?.street ?? null;
  const addrCity   = authAddress ? `${authAddress.city}, ${authAddress.state} (${authAddress.zip_code})` : guestAddress ? `${guestAddress.city}, ${guestAddress.state} (${guestAddress.zip_code})` : null;

  return (
    <>
      <Header />

      <section role="banner" className="entry-hero product-archive-hero-section entry-hero-layout-standard">
        <div className="entry-hero-container-inner">
          <div className="hero-section-overlay" />
          <div className="hero-container site-container">
            <header className="entry-header">
              <Breadcrumb crumbs={[{ name: 'Inicio', href: '/' }, { name: 'Carrito', href: '/cart' }, { name: 'Checkout' }]} />
              <h1 className="page-title">Checkout — Pago</h1>
            </header>
          </div>
        </div>
      </section>

      <div className="woocommerce woocommerce-checkout woocommerce-page">
        <div className="site-container co-page">

          {paymentConfig?.js_sdk_url && paymentConfig?.public_key && (
            <Script src={paymentConfig.js_sdk_url} strategy="afterInteractive" onLoad={initSdk} />
          )}

          <div className="col2-set" id="customer_details_wrap">
            <div className="co-main-col">

              {/* Dirección confirmada */}
              {addrLabel && (
                <div className="co-confirmed-address">
                  <div className="co-confirmed-address-header">
                    <span>Dirección de envío</span>
                    <button type="button" className="co-guest-link" onClick={() => router.push('/checkout/shipping')}>
                      Cambiar
                    </button>
                  </div>
                  <p>{addrLabel}</p>
                  {addrStreet && <p>{addrStreet}</p>}
                  {addrCity && <p>{addrCity}</p>}
                </div>
              )}

              {/* Panel de pago */}
              {sdkNotConfigured ? (
                <SimulatorPanel
                  total={cart?.summary.total ?? 0}
                  processing={processing}
                  error={error}
                  onPay={handleSimulate}
                  onBack={() => router.push('/checkout/shipping')}
                />
              ) : (
                <div className="co-payment-form">
                  <h3>Datos de la tarjeta</h3>
                  <form ref={formRef} onSubmit={handlePay}>

                    <p className="co-field form-row">
                      <label className="co-label">Número de tarjeta <abbr title="requerido">*</abbr></label>
                      <input type="text" className="input-text" data-decidir="card_number"
                        placeholder="#### #### #### ####" maxLength={19} required />
                    </p>

                    <p className="co-field form-row">
                      <label className="co-label">Nombre del titular <abbr title="requerido">*</abbr></label>
                      <input type="text" className="input-text" data-decidir="card_holder_name"
                        placeholder="Como figura en la tarjeta" required />
                    </p>

                    <div className="co-row-3">
                      <p className="co-field form-row">
                        <label className="co-label">Mes <abbr title="requerido">*</abbr></label>
                        <input type="text" className="input-text" data-decidir="card_expiration_month"
                          placeholder="MM" maxLength={2} required />
                      </p>
                      <p className="co-field form-row">
                        <label className="co-label">Año <abbr title="requerido">*</abbr></label>
                        <input type="text" className="input-text" data-decidir="card_expiration_year"
                          placeholder="AA" maxLength={2} required />
                      </p>
                      <p className="co-field form-row">
                        <label className="co-label">CVV <abbr title="requerido">*</abbr></label>
                        <input type="text" className="input-text" data-decidir="security_code"
                          placeholder="123" maxLength={4} required />
                      </p>
                    </div>

                    <div className="co-row-2">
                      <p className="co-field form-row">
                        <label className="co-label">Tipo de documento <abbr title="requerido">*</abbr></label>
                        <select className="input-text addr-select" data-decidir="card_holder_doc_type"
                          value={docType} onChange={e => setDocType(e.target.value)}>
                          {DOC_TYPES.map(d => <option key={d.value} value={d.value}>{d.label}</option>)}
                        </select>
                      </p>
                      <p className="co-field form-row">
                        <label className="co-label">Número de documento <abbr title="requerido">*</abbr></label>
                        <input type="text" className="input-text" data-decidir="card_holder_doc_number"
                          placeholder="12345678" required />
                      </p>
                    </div>

                    <p className="co-field form-row">
                      <label className="co-label">Cuotas</label>
                      <select className="input-text addr-select" value={installments}
                        onChange={e => setInstallments(Number(e.target.value))}>
                        {INSTALLMENTS.map(n => (
                          <option key={n} value={n}>{n === 1 ? '1 cuota (sin interés)' : `${n} cuotas`}</option>
                        ))}
                      </select>
                    </p>

                    {error && <p className="co-error" style={{ marginBottom: '0.75em' }}>{error}</p>}

                    <div className="co-actions">
                      <button type="button" className="co-back-link" onClick={() => router.push('/checkout/shipping')}>
                        ← Volver a envío
                      </button>
                      <button type="submit" className="button alt" disabled={processing || !sdkReady}>
                        {processing ? 'Procesando pago...' : `Pagar $${formatPrice(cart?.summary.total ?? 0)}`}
                      </button>
                    </div>
                  </form>
                </div>
              )}

            </div>

            {/* Sidebar resumen */}
            {cart && (
              <div className="col-2" id="order_review">
                <div className="co-sidebar">
                  <h3>Tu pedido</h3>
                  <table className="shop_table woocommerce-checkout-review-order-table">
                    <thead>
                      <tr>
                        <th className="product-name">Producto</th>
                        <th className="product-total">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      {cart.items.map(item => (
                        <tr key={item.id} className="cart_item">
                          <td className="product-name">
                            {item.name}
                            {item.variant_label && <span className="co-variant"> ({item.variant_label})</span>}
                            <strong className="product-quantity"> &times; {item.quantity}</strong>
                          </td>
                          <td className="product-total">
                            <span className="woocommerce-Price-amount"><bdi>${formatPrice(item.subtotal)}</bdi></span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                    <tfoot>
                      <tr className="cart-subtotal">
                        <th>Subtotal</th>
                        <td><span className="woocommerce-Price-amount"><bdi>${formatPrice(cart.summary.subtotal)}</bdi></span></td>
                      </tr>
                      {cart.summary.discount > 0 && (
                        <tr className="cart-discount">
                          <th>Descuento{cart.coupon_code ? ` (${cart.coupon_code})` : ''}</th>
                          <td><span className="woocommerce-Price-amount"><bdi>−${formatPrice(cart.summary.discount)}</bdi></span></td>
                        </tr>
                      )}
                      <tr className="shipping">
                        <th>Envío</th>
                        <td>
                          {cart.summary.shipping_cost === 0
                            ? 'Gratis'
                            : <span className="woocommerce-Price-amount"><bdi>${formatPrice(cart.summary.shipping_cost)}</bdi></span>}
                        </td>
                      </tr>
                      <tr className="order-total">
                        <th>Total</th>
                        <td><strong><span className="woocommerce-Price-amount"><bdi>${formatPrice(cart.summary.total)}</bdi></span></strong></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
