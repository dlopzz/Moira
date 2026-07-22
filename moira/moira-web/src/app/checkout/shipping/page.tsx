'use client';

import { useCallback, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import Image from 'next/image';
import {
  api, type Address, type AddressPayload, type Cart, type ShippingRate,
  type GuestShippingAddress, ApiError, formatPrice, imageThumbUrl,
} from '@/lib/api';
import { getToken, saveToken } from '@/lib/auth';
import Header from '@/components/Header';
import Breadcrumb from '@/components/Breadcrumb';

// ─── ARGENTINA PROVINCES ─────────────────────
const PROVINCES = [
  'Buenos Aires', 'Catamarca', 'Chaco', 'Chubut', 'Córdoba', 'Corrientes',
  'Entre Ríos', 'Formosa', 'Jujuy', 'La Pampa', 'La Rioja', 'Mendoza',
  'Misiones', 'Neuquén', 'Río Negro', 'Salta', 'San Juan', 'San Luis',
  'Santa Cruz', 'Santa Fe', 'Santiago del Estero', 'Tierra del Fuego',
  'Tucumán', 'Ciudad Autónoma de Buenos Aires',
];

const EMPTY_GUEST: GuestShippingAddress = {
  email: '', firstname: '', lastname: '', telephone: '',
  street: '', city: '', state: '', zip_code: '', country: 'AR',
};

const EMPTY_ADDR: AddressPayload = {
  label: '', street: '', address_line_2: '', city: '',
  state: '', zip_code: '', country: 'AR', telephone: '',
};

// ─── FIELD COMPONENT ─────────────────────────
function Field({
  label, required, error, children,
}: { label: string; required?: boolean; error?: string; children: React.ReactNode }) {
  return (
    <p className="co-field form-row">
      <label className="co-label">
        {label} {required && <abbr title="requerido">*</abbr>}
      </label>
      {children}
      {error && <span className="co-error">{error}</span>}
    </p>
  );
}

// ─── ORDER SIDEBAR ────────────────────────────
function OrderSidebar({
  cart, selectedRate,
}: { cart: Cart | null; selectedRate: ShippingRate | null }) {
  if (!cart) return null;

  const shippingCost = selectedRate ? selectedRate.price : cart.summary.shipping_cost;
  const total = cart.summary.subtotal - cart.summary.discount + shippingCost;

  return (
    <div className="co-sidebar">
      <h3 id="order_review_heading">Tu pedido</h3>
      <table className="shop_table woocommerce-checkout-review-order-table">
        <thead>
          <tr>
            <th className="product-name">Producto</th>
            <th className="product-total">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          {cart.items.map((item) => (
            <tr key={item.id} className="cart_item">
              <td className="product-name">
                <span className="checkout-review-product-image">
                  {item.image && imageThumbUrl(item.image) && (
                    <Image
                      src={imageThumbUrl(item.image)!}
                      alt={item.name}
                      width={48}
                      height={64}
                      style={{ objectFit: 'cover' }}
                      unoptimized
                    />
                  )}
                </span>
                <span className="checkout-product-info">
                  {item.product_slug ? (
                    <Link href={`/products/${item.product_slug}`}>{item.name}</Link>
                  ) : item.name}
                  {item.variant_label && <span className="co-variant"> ({item.variant_label})</span>}
                  <strong className="product-quantity"> &times; {item.quantity}</strong>
                </span>
              </td>
              <td className="product-total">
                <span className="woocommerce-Price-amount">
                  <bdi>${formatPrice(item.subtotal)}</bdi>
                </span>
              </td>
            </tr>
          ))}
        </tbody>
        <tfoot>
          <tr className="cart-subtotal">
            <th>Subtotal</th>
            <td>
              <span className="woocommerce-Price-amount">
                <bdi>${formatPrice(cart.summary.subtotal)}</bdi>
              </span>
            </td>
          </tr>
          {cart.summary.discount > 0 && (
            <tr className="cart-discount">
              <th>Descuento{cart.coupon_code ? ` (${cart.coupon_code})` : ''}</th>
              <td>
                <span className="woocommerce-Price-amount co-discount">
                  <bdi>-${formatPrice(cart.summary.discount)}</bdi>
                </span>
              </td>
            </tr>
          )}
          <tr className="woocommerce-shipping-totals shipping">
            <th>Envío</th>
            <td>
              {selectedRate ? (
                <>
                  <span className="woocommerce-Price-amount">
                    <bdi>{selectedRate.price === 0 ? 'Gratis' : `$${formatPrice(selectedRate.price)}`}</bdi>
                  </span>
                  <small className="co-shipping-label"> — {selectedRate.label}</small>
                </>
              ) : (
                <span className="co-muted">A calcular</span>
              )}
            </td>
          </tr>
          <tr className="order-total">
            <th>Total</th>
            <td>
              <strong>
                <span className="woocommerce-Price-amount">
                  <bdi>${formatPrice(total)}</bdi>
                </span>
              </strong>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  );
}

// ─── COUPON BAR ───────────────────────────────
function CouponBar({ cart, onCartUpdate }: { cart: Cart | null; onCartUpdate: (c: Cart) => void }) {
  const [open, setOpen] = useState(false);
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const alreadyApplied = cart?.coupon_code;

  async function handleApply(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const res = await api.applyCoupon(code.trim());
      onCartUpdate(res.data);
      setCode('');
      setOpen(false);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Error al aplicar el cupón.');
    } finally {
      setLoading(false);
    }
  }

  async function handleRemove() {
    setLoading(true);
    try {
      const res = await api.removeCoupon();
      onCartUpdate(res.data);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="woocommerce-form-coupon-toggle">
      {alreadyApplied ? (
        <p className="co-coupon-applied">
          Cupón <strong>{alreadyApplied}</strong> aplicado.{' '}
          <button className="co-coupon-remove" onClick={handleRemove} disabled={loading}>
            Quitar
          </button>
        </p>
      ) : (
        <>
          <p>
            ¿Tenés un cupón de descuento?{' '}
            <a href="#" className="showcoupon" onClick={(e) => { e.preventDefault(); setOpen(o => !o); }}>
              Hacé clic aquí para ingresar el código.
            </a>
          </p>
          {open && (
            <form className="checkout_coupon woocommerce-form-coupon" onSubmit={handleApply}>
              <p className="form-row">
                <input
                  type="text"
                  className="input-text"
                  placeholder="Código de cupón"
                  value={code}
                  onChange={e => setCode(e.target.value)}
                  required
                />
              </p>
              <p className="form-row">
                <button type="submit" className="button" disabled={loading || !code.trim()}>
                  {loading ? 'Aplicando...' : 'Aplicar cupón'}
                </button>
              </p>
              {error && <p className="co-error" style={{ margin: '0 0 1em' }}>{error}</p>}
            </form>
          )}
        </>
      )}
    </div>
  );
}

// ─── GUEST CHECKOUT ───────────────────────────
function GuestCheckout({ cart, onCartUpdate, onRateSelect }: {
  cart: Cart | null;
  onCartUpdate: (c: Cart) => void;
  onRateSelect: (r: ShippingRate | null) => void;
}) {
  const router = useRouter();
  const [loading, setLoading]   = useState(true);
  const [form, setForm]         = useState<GuestShippingAddress>(EMPTY_GUEST);
  const [notes, setNotes]       = useState('');
  const [errors, setErrors]     = useState<Record<string, string>>({});
  const [saving, setSaving]     = useState(false);
  const [zipLoading, setZipLoading] = useState(false);

  // Login panel (manual — sin detección de existencia de email)
  const [showLogin, setShowLogin]             = useState(false);
  const [loginEmail, setLoginEmail]           = useState('');
  const [loginPassword, setLoginPassword]     = useState('');
  const [loginError, setLoginError]           = useState('');
  const [loginLoading, setLoginLoading]       = useState(false);

  // Shipping method
  const [addrSaved, setAddrSaved]       = useState(false);
  const [rates, setRates]               = useState<ShippingRate[]>([]);
  const [selectedRate, setSelectedRate] = useState<ShippingRate | null>(null);
  const [loadingRates, setLoadingRates] = useState(false);
  const [ratesError, setRatesError]     = useState('');
  const [confirming, setConfirming]     = useState(false);

  function selectRate(rate: ShippingRate) {
    setSelectedRate(rate);
    onRateSelect(rate);
  }

  useEffect(() => {
    api.getGuestCheckout()
      .then((res) => {
        if (res.shipping_address) {
          setForm(res.shipping_address);
          setNotes(res.order_notes ?? '');
          setAddrSaved(true);
          loadRates();
          if (res.shipping_method) { setSelectedRate(res.shipping_method); onRateSelect(res.shipping_method); }
        }
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  async function loadRates() {
    setLoadingRates(true);
    setRatesError('');
    try {
      const res = await api.getGuestShippingRates();
      setRates(res.data);
    } catch (err) {
      setRatesError(err instanceof ApiError ? err.message : 'Error al obtener tarifas.');
    } finally {
      setLoadingRates(false);
    }
  }

  async function lookupZip(zip: string) {
    if (zip.length < 4) return;
    setZipLoading(true);
    try {
      const res = await fetch(`https://apis.datos.gob.ar/georef/api/localidades?cp=${zip}&campos=nombre,provincia.nombre&max=1`);
      const data = await res.json();
      const loc = data?.localidades?.[0];
      if (loc) setForm(f => ({ ...f, zip_code: zip, city: loc.nombre, state: loc.provincia?.nombre ?? f.state }));
    } catch { /* ignore */ } finally {
      setZipLoading(false);
    }
  }

  async function handleLogin() {
    setLoginError('');
    setLoginLoading(true);
    try {
      const res = await api.login({ email: loginEmail, password: loginPassword });
      saveToken(res.token);
      window.location.reload();
    } catch (err) {
      setLoginError(err instanceof ApiError ? err.message : 'Error al iniciar sesión.');
    } finally {
      setLoginLoading(false);
    }
  }

  async function handleAddressSubmit(e: React.FormEvent) {
    e.preventDefault();
    setErrors({});
    setSaving(true);
    try {
      await api.saveGuestAddress({ ...form, order_notes: notes });
      setAddrSaved(true);
      await loadRates();
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        const flat: Record<string, string> = {};
        for (const [k, v] of Object.entries(err.errors)) flat[k] = Array.isArray(v) ? v[0] : v;
        setErrors(flat);
      }
    } finally {
      setSaving(false);
    }
  }

  async function handleContinue() {
    if (!selectedRate) return;
    setConfirming(true);
    try {
      await api.selectGuestShipping(selectedRate);
      router.push('/checkout/payment');
    } catch {
      setConfirming(false);
    }
  }

  // El usuario abre el login manualmente; por defecto se ve el form de invitado.
  const showLoginPanel = showLogin;
  const showAddressFields = !showLogin;

  if (loading) return <p className="co-loading">Cargando...</p>;

  return (
    <>
      <form className="woocommerce-checkout checkout" onSubmit={handleAddressSubmit} noValidate>
        {/* ── Dirección de envío ── */}
        <div id="customer_details">
          <div className="woocommerce-billing-fields">
            <h3>Detalles de envío</h3>
            <div className="woocommerce-billing-fields__field-wrapper">

              {/* ── ¿Ya tenés cuenta? Login manual (sin detección de email) ── */}
              {!showLogin && (
                <p className="co-guest-login-hint" style={{ marginBottom: '1em' }}>
                  ¿Ya tenés cuenta?{' '}
                  <button
                    type="button" className="co-guest-link"
                    onClick={() => { setShowLogin(true); setLoginError(''); }}
                  >
                    Iniciá sesión
                  </button>
                </p>
              )}

              {/* ── Panel de login (self-contained) ── */}
              {showLoginPanel && (
                <div className="co-login-panel">
                  <p className="co-login-panel-msg">Iniciá sesión con tu cuenta.</p>
                  <p className="co-field form-row">
                    <label className="co-label">Correo electrónico <abbr title="requerido">*</abbr></label>
                    <input
                      type="email" className="input-text" value={loginEmail}
                      autoComplete="email"
                      onChange={e => setLoginEmail(e.target.value)}
                    />
                  </p>
                  <p className="co-field form-row">
                    <label className="co-label">Contraseña <abbr title="requerido">*</abbr></label>
                    <input
                      type="password" className="input-text" value={loginPassword}
                      autoComplete="current-password"
                      onChange={e => setLoginPassword(e.target.value)}
                      onKeyDown={e => { if (e.key === 'Enter') { e.preventDefault(); handleLogin(); } }}
                    />
                  </p>
                  {loginError && <p className="co-error" style={{ marginBottom: '0.75em' }}>{loginError}</p>}
                  <div className="co-login-actions">
                    <button
                      type="button" className="button alt"
                      disabled={loginLoading || !loginEmail || !loginPassword}
                      onClick={handleLogin}
                    >
                      {loginLoading ? 'Iniciando sesión...' : 'Iniciar sesión'}
                    </button>
                    <button
                      type="button" className="co-guest-link"
                      onClick={() => setShowLogin(false)}
                    >
                      Seguir como invitado
                    </button>
                  </div>
                </div>
              )}

              {/* ── Formulario de invitado ── */}
              {showAddressFields && (
                <>
                  <Field label="Correo electrónico" required error={errors.email}>
                    <input
                      type="email" className="input-text" required value={form.email}
                      autoComplete="email"
                      onChange={e => setForm(f => ({ ...f, email: e.target.value }))}
                    />
                  </Field>

                  <div className="co-row-2">
                    <Field label="Nombre" required error={errors.firstname}>
                      <input type="text" className="input-text" required value={form.firstname}
                        onChange={e => setForm(f => ({ ...f, firstname: e.target.value }))} />
                    </Field>
                    <Field label="Apellidos" required error={errors.lastname}>
                      <input type="text" className="input-text" required value={form.lastname}
                        onChange={e => setForm(f => ({ ...f, lastname: e.target.value }))} />
                    </Field>
                  </div>

                  <Field label="Teléfono" required error={errors.telephone}>
                    <input type="tel" className="input-text" required value={form.telephone}
                      onChange={e => setForm(f => ({ ...f, telephone: e.target.value }))} />
                  </Field>

                  <Field label="Dirección de la calle" required error={errors.street}>
                    <input type="text" className="input-text" placeholder="Número de la casa y nombre de la calle"
                      required value={form.street}
                      onChange={e => setForm(f => ({ ...f, street: e.target.value }))} />
                  </Field>

                  <Field label="Código postal" required error={errors.zip_code}>
                    <input type="text" className="input-text" required value={form.zip_code}
                      onChange={e => setForm(f => ({ ...f, zip_code: e.target.value }))}
                      onBlur={e => lookupZip(e.target.value)} />
                    {zipLoading && <span className="co-muted" style={{ fontSize: 12 }}> buscando...</span>}
                  </Field>

                  <div className="co-row-2">
                    <Field label="Localidad / Ciudad" required error={errors.city}>
                      <input type="text" className="input-text" required value={form.city}
                        onChange={e => setForm(f => ({ ...f, city: e.target.value }))} />
                    </Field>
                    <Field label="Provincia" required error={errors.state}>
                      <select className="input-text addr-select" required value={form.state}
                        onChange={e => setForm(f => ({ ...f, state: e.target.value }))}>
                        <option value="">Seleccioná</option>
                        {PROVINCES.map(p => <option key={p} value={p}>{p}</option>)}
                      </select>
                    </Field>
                  </div>

                  <p className="co-billing-note">
                    ✓ La dirección de facturación es la misma que la de envío.
                  </p>
                </>
              )}
            </div>
          </div>
        </div>

        {/* ── Método de envío ── */}
        {addrSaved && (
          <div className="woocommerce-shipping-fields co-shipping-section">
            <h3>Método de envío</h3>
            {ratesError && <p className="co-error">{ratesError}</p>}
            {loadingRates ? (
              <p className="co-loading">Calculando tarifas de envío...</p>
            ) : rates.length === 0 ? (
              <p className="co-muted">No hay métodos de envío disponibles para este código postal.</p>
            ) : (
              <ul id="shipping_method" className="woocommerce-shipping-methods">
                {rates.map(rate => (
                  <li key={rate.code}>
                    <input
                      type="radio" id={`shipping_method_${rate.code}`}
                      name="shipping_method" value={rate.code}
                      className="shipping_method"
                      checked={selectedRate?.code === rate.code}
                      onChange={() => selectRate(rate)}
                    />
                    <label htmlFor={`shipping_method_${rate.code}`}>
                      {rate.label}
                      {rate.estimated_days && <span className="co-muted"> — {rate.estimated_days}</span>}
                      <span className="woocommerce-Price-amount" style={{ marginLeft: 8 }}>
                        <bdi>{rate.price === 0 ? 'Gratis' : `$${formatPrice(rate.price)}`}</bdi>
                      </span>
                    </label>
                  </li>
                ))}
              </ul>
            )}
          </div>
        )}

        {/* ── Información adicional ── */}
        {showAddressFields && (
          <div className="woocommerce-additional-fields">
            <h3>Información adicional</h3>
            <div className="woocommerce-additional-fields__field-wrapper">
              <Field label="Notas del pedido (opcional)">
                <textarea
                  id="order_comments" className="input-text"
                  placeholder="Notas sobre tu pedido, por ejemplo, notas especiales para la entrega."
                  rows={4}
                  value={notes}
                  onChange={e => setNotes(e.target.value)}
                />
              </Field>
            </div>
          </div>
        )}

        {/* ── Acciones ── */}
        {showAddressFields && (
          <div className="co-actions">
            <button type="button" className="co-back-link" onClick={() => router.push('/cart')}>
              ← Volver al carrito
            </button>
            <button
              type={addrSaved ? 'button' : 'submit'}
              className="button alt"
              disabled={saving || loadingRates || (addrSaved && !selectedRate) || confirming}
              onClick={addrSaved ? handleContinue : undefined}
            >
              {confirming ? 'Guardando...' :
               saving || loadingRates ? 'Cargando...' :
               addrSaved ? 'Continuar al pago →' :
               'Ver métodos de envío →'}
            </button>
          </div>
        )}
      </form>
    </>
  );
}

// ─── AUTH CHECKOUT ────────────────────────────
function AuthCheckout({ cart, onCartUpdate, onRateSelect }: {
  cart: Cart | null;
  onCartUpdate: (c: Cart) => void;
  onRateSelect: (r: ShippingRate | null) => void;
}) {
  const router = useRouter();
  const [loading, setLoading]                       = useState(true);
  const [addresses, setAddresses]                   = useState<Address[]>([]);
  const [selected, setSelected]                     = useState<number | null>(null);
  const [saving, setSaving]                         = useState(false);
  const [showNewForm, setShowNewForm]               = useState(false);
  const [newForm, setNewForm]                       = useState<AddressPayload>(EMPTY_ADDR);
  const [newErrors, setNewErrors]                   = useState<Record<string, string>>({});
  const [formSaving, setFormSaving]                 = useState(false);
  const [zipLoading, setZipLoading]                 = useState(false);
  const [notes, setNotes]                           = useState('');
  const [rates, setRates]                           = useState<ShippingRate[]>([]);
  const [selectedRate, setSelectedRate]             = useState<ShippingRate | null>(null);
  const [loadingRates, setLoadingRates]             = useState(false);
  const [ratesError, setRatesError]                 = useState('');
  const [addrConfirmed, setAddrConfirmed]           = useState(false);
  const [confirming, setConfirming]                 = useState(false);
  // Billing address
  const [billingSame, setBillingSame]               = useState(true);
  const [selectedBilling, setSelectedBilling]       = useState<number | null>(null);

  useEffect(() => {
    Promise.all([api.getAddresses(), api.getCheckout()])
      .then(([addrRes, checkoutRes]) => {
        setAddresses(addrRes.data);
        onCartUpdate(checkoutRes.cart);
        // Pre-select shipping address
        const shippingId = checkoutRes.checkout_address?.id
          ?? addrRes.data.find(a => a.is_default_shipping)?.id
          ?? addrRes.data[0]?.id
          ?? null;
        setSelected(shippingId);
        // Pre-select billing state
        const billingId = checkoutRes.billing_address?.id ?? null;
        const sameAsShipping = checkoutRes.billing_same_as_shipping ?? true;
        setBillingSame(sameAsShipping);
        setSelectedBilling(billingId && !sameAsShipping ? billingId : null);
        // If address was already confirmed (shipping method set), show rates section
        if (checkoutRes.checkout_address && checkoutRes.cart.shipping?.code) {
          setAddrConfirmed(true);
          loadRates();
        }
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  function selectRate(rate: ShippingRate) {
    setSelectedRate(rate);
    onRateSelect(rate);
  }

  async function loadRates() {
    setLoadingRates(true);
    setRatesError('');
    try {
      const res = await api.getShippingRates();
      setRates(res.data);
    } catch (err) {
      setRatesError(err instanceof ApiError ? err.message : 'Error al obtener tarifas.');
    } finally {
      setLoadingRates(false);
    }
  }

  async function handleAddrContinue(e: React.FormEvent) {
    e.preventDefault();
    if (!selected) return;
    setSaving(true);
    try {
      const billingId = billingSame ? null : (selectedBilling ?? selected);
      await api.setCheckoutAddress(selected, billingId);
      setAddrConfirmed(true);
      await loadRates();
    } finally {
      setSaving(false);
    }
  }

  async function handleContinue() {
    if (!selectedRate) return;
    setConfirming(true);
    try {
      if (notes.trim()) await api.saveCheckoutNotes(notes);
      await api.selectShipping(selectedRate);
      router.push('/checkout/payment');
    } catch {
      setConfirming(false);
    }
  }

  async function handleNewAddrSubmit(e: React.SyntheticEvent) {
    e.preventDefault();
    setNewErrors({});
    setFormSaving(true);
    try {
      const res = await api.createAddress(newForm);
      const newAddr = res.data;
      setAddresses(prev => [...prev, newAddr]);
      setSelected(newAddr.id);
      setNewForm(EMPTY_ADDR);
      setShowNewForm(false);
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

  async function lookupZip(zip: string) {
    if (zip.length < 4) return;
    setZipLoading(true);
    try {
      const res = await fetch(`https://apis.datos.gob.ar/georef/api/localidades?cp=${zip}&campos=nombre,provincia.nombre&max=1`);
      const data = await res.json();
      const loc = data?.localidades?.[0];
      if (loc) setNewForm(f => ({ ...f, zip_code: zip, city: loc.nombre, state: loc.provincia?.nombre ?? f.state }));
    } catch { /* ignore */ } finally {
      setZipLoading(false);
    }
  }

  if (loading) return <p className="co-loading">Cargando...</p>;

  return (
    <form className="woocommerce-checkout checkout" onSubmit={handleAddrContinue} noValidate>
      {/* ── Direcciones guardadas ── */}
      <div id="customer_details">
        <div className="woocommerce-billing-fields">
          <h3>Dirección de envío</h3>
          <div className="woocommerce-billing-fields__field-wrapper">
            {addresses.map(addr => (
              <label key={addr.id} className={`co-addr-card ${selected === addr.id ? 'co-addr-card--selected' : ''}`}>
                <input type="radio" name="address" value={addr.id}
                  checked={selected === addr.id}
                  onChange={() => { setSelected(addr.id); setAddrConfirmed(false); }} />
                <div className="co-addr-card-body">
                  <strong>{addr.label}</strong>
                  <span>{addr.street}{addr.address_line_2 ? `, ${addr.address_line_2}` : ''}</span>
                  <span>{addr.city}, {addr.state} ({addr.zip_code})</span>
                  <span>{addr.telephone}</span>
                  <div className="co-addr-badges">
                    {addr.is_default_shipping && <span className="co-badge co-badge--shipping">Envío predeterminado</span>}
                    {addr.is_default_billing  && <span className="co-badge co-badge--billing">Facturación predeterminada</span>}
                  </div>
                </div>
              </label>
            ))}

            {addresses.length === 0 && !showNewForm && (
              <p className="co-muted">No tenés direcciones guardadas.</p>
            )}

            {showNewForm ? (
              <div className="co-new-addr-form">
                <div className="co-row-2">
                  <Field label="Etiqueta" required error={newErrors.label}>
                    <input type="text" className="input-text" required value={newForm.label}
                      onChange={e => setNewForm(f => ({ ...f, label: e.target.value }))} />
                  </Field>
                  <Field label="Teléfono" required error={newErrors.telephone}>
                    <input type="tel" className="input-text" required value={newForm.telephone}
                      onChange={e => setNewForm(f => ({ ...f, telephone: e.target.value }))} />
                  </Field>
                </div>
                <Field label="Calle y número" required error={newErrors.street}>
                  <input type="text" className="input-text" required value={newForm.street}
                    onChange={e => setNewForm(f => ({ ...f, street: e.target.value }))} />
                </Field>
                <Field label="Código postal" required error={newErrors.zip_code}>
                  <input type="text" className="input-text" required value={newForm.zip_code}
                    onChange={e => setNewForm(f => ({ ...f, zip_code: e.target.value }))}
                    onBlur={e => lookupZip(e.target.value)} />
                  {zipLoading && <span className="co-muted" style={{ fontSize: 12 }}> buscando...</span>}
                </Field>
                <div className="co-row-2">
                  <Field label="Ciudad" required error={newErrors.city}>
                    <input type="text" className="input-text" required value={newForm.city}
                      onChange={e => setNewForm(f => ({ ...f, city: e.target.value }))} />
                  </Field>
                  <Field label="Provincia" required error={newErrors.state}>
                    <select className="input-text addr-select" required value={newForm.state}
                      onChange={e => setNewForm(f => ({ ...f, state: e.target.value }))}>
                      <option value="">Seleccioná</option>
                      {PROVINCES.map(p => <option key={p} value={p}>{p}</option>)}
                    </select>
                  </Field>
                </div>
                <div className="co-new-addr-actions">
                  <button type="button" className="co-back-link" onClick={() => setShowNewForm(false)}>
                    Cancelar
                  </button>
                  <button type="button" className="button" disabled={formSaving} onClick={handleNewAddrSubmit}>
                    {formSaving ? 'Guardando...' : 'Guardar dirección'}
                  </button>
                </div>
              </div>
            ) : (
              <p>
                <button type="button" className="co-add-addr-btn" onClick={() => setShowNewForm(true)}>
                  + Agregar nueva dirección
                </button>
              </p>
            )}
          </div>
        </div>
      </div>

      {/* ── Dirección de facturación ── solo si hay una dirección de envío seleccionada */}
      {selected && <div className="woocommerce-billing-fields co-billing-section">
        <h3>Dirección de facturación</h3>
        <label className="co-checkbox-label">
          <input
            type="checkbox"
            checked={billingSame}
            onChange={e => {
              setBillingSame(e.target.checked);
              if (e.target.checked) setSelectedBilling(null);
              else {
                // Pre-select default billing or the same as shipping
                const defBilling = addresses.find(a => a.is_default_billing) ?? addresses.find(a => a.id === selected);
                setSelectedBilling(defBilling?.id ?? null);
              }
              setAddrConfirmed(false);
            }}
          />
          Mi dirección de facturación es la misma que la de envío
        </label>

        {!billingSame && (
          <div className="co-billing-picker">
            {addresses.map(addr => (
              <label key={addr.id} className={`co-addr-card co-addr-card--sm ${selectedBilling === addr.id ? 'co-addr-card--selected' : ''}`}>
                <input type="radio" name="billing_address" value={addr.id}
                  checked={selectedBilling === addr.id}
                  onChange={() => { setSelectedBilling(addr.id); setAddrConfirmed(false); }} />
                <div className="co-addr-card-body">
                  <strong>{addr.label}</strong>
                  <span>{addr.street}</span>
                  <span>{addr.city}, {addr.state}</span>
                  {addr.is_default_billing && <span className="co-badge co-badge--billing">Predeterminada</span>}
                </div>
              </label>
            ))}
          </div>
        )}
      </div>}

      {/* ── Método de envío ── */}
      {addrConfirmed && (
        <div className="woocommerce-shipping-fields co-shipping-section">
          <h3>Método de envío</h3>
          {ratesError && <p className="co-error">{ratesError}</p>}
          {loadingRates ? (
            <p className="co-loading">Calculando tarifas de envío...</p>
          ) : rates.length === 0 ? (
            <p className="co-muted">No hay métodos disponibles para esta dirección.</p>
          ) : (
            <ul id="shipping_method" className="woocommerce-shipping-methods">
              {rates.map(rate => (
                <li key={rate.code}>
                  <input
                    type="radio" id={`shipping_method_auth_${rate.code}`}
                    name="shipping_method" value={rate.code}
                    className="shipping_method"
                    checked={selectedRate?.code === rate.code}
                    onChange={() => selectRate(rate)}
                  />
                  <label htmlFor={`shipping_method_auth_${rate.code}`}>
                    {rate.label}
                    {rate.estimated_days && <span className="co-muted"> — {rate.estimated_days}</span>}
                    <span className="woocommerce-Price-amount" style={{ marginLeft: 8 }}>
                      <bdi>{rate.price === 0 ? 'Gratis' : `$${formatPrice(rate.price)}`}</bdi>
                    </span>
                  </label>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}

      {/* ── Información adicional ── */}
      <div className="woocommerce-additional-fields">
        <h3>Información adicional</h3>
        <div className="woocommerce-additional-fields__field-wrapper">
          <Field label="Notas del pedido (opcional)">
            <textarea
              id="order_comments" className="input-text"
              placeholder="Notas sobre tu pedido, por ejemplo, notas especiales para la entrega."
              rows={4}
              value={notes}
              onChange={e => setNotes(e.target.value)}
            />
          </Field>
        </div>
      </div>

      {/* ── Acciones ── */}
      <div className="co-actions">
        <button type="button" className="co-back-link" onClick={() => router.push('/cart')}>
          ← Volver al carrito
        </button>
        {addrConfirmed ? (
          <button type="button" className="button alt"
            disabled={!selectedRate || confirming}
            onClick={handleContinue}>
            {confirming ? 'Guardando...' : 'Continuar al pago →'}
          </button>
        ) : (
          <button type="submit" className="button alt" disabled={!selected || saving || loadingRates}>
            {saving || loadingRates ? 'Cargando...' : 'Ver métodos de envío →'}
          </button>
        )}
      </div>
    </form>
  );
}

// ─── PAGE SHELL ───────────────────────────────
export default function CheckoutShippingPage() {
  const [isAuth, setIsAuth]             = useState<boolean | null>(null);
  const [cart, setCart]                 = useState<Cart | null>(null);
  const [selectedRate, setSelectedRate] = useState<ShippingRate | null>(null);

  useEffect(() => {
    const auth = !!getToken();
    setIsAuth(auth);
    // Auth users get cart from AuthCheckout via onCartUpdate; guests load directly
    if (!auth) {
      api.getCart().then(res => setCart(res.data)).catch(() => {});
    }
  }, []);

  // Listen for rate selection changes from children
  // (simplified: just re-fetch cart when shipping method changes)
  const handleRateSelect = useCallback((rate: ShippingRate | null) => {
    setSelectedRate(rate);
  }, []);

  return (
    <>
      <Header />

      <section role="banner" className="entry-hero product-archive-hero-section entry-hero-layout-standard">
        <div className="entry-hero-container-inner">
          <div className="hero-section-overlay" />
          <div className="hero-container site-container">
            <header className="entry-header">
              <Breadcrumb crumbs={[{ name: 'Inicio', href: '/' }, { name: 'Carrito', href: '/cart' }, { name: 'Checkout' }]} />
              <h1 className="page-title">Checkout</h1>
            </header>
          </div>
        </div>
      </section>

      <div className="woocommerce woocommerce-checkout woocommerce-page">
        <div className="site-container co-page">
          <div className="woocommerce-notices-wrapper" />

          {/* Coupon banner */}
          {cart !== null && (
            <CouponBar cart={cart} onCartUpdate={c => setCart(c)} />
          )}

          {isAuth === null ? (
            <p className="co-loading">Cargando...</p>
          ) : (
            <div className="col2-set" id="customer_details_wrap">
              <div className="co-main-col">
                {isAuth ? (
                  <AuthCheckout cart={cart} onCartUpdate={c => setCart(c)} onRateSelect={handleRateSelect} />
                ) : (
                  <GuestCheckout cart={cart} onCartUpdate={c => setCart(c)} onRateSelect={handleRateSelect} />
                )}
              </div>
              <div className="col-2" id="order_review">
                <OrderSidebar cart={cart} selectedRate={selectedRate} />
              </div>
            </div>
          )}
        </div>
      </div>
    </>
  );
}
