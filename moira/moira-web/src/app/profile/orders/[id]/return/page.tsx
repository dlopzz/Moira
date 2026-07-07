'use client';

import { useEffect, useState, use } from 'react';
import { api, ApiError, type ReturnableItem } from '@/lib/api';

type Errors = Partial<Record<'reason' | 'telephone' | 'description' | 'items' | '_global', string>>;

export default function OrderReturnPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params);
  const orderId = Number(id);

  const [items, setItems] = useState<ReturnableItem[]>([]);
  const [reasons, setReasons] = useState<string[]>([]);
  const [quantities, setQuantities] = useState<Record<number, number>>({});
  const [reason, setReason] = useState('');
  const [description, setDescription] = useState('');
  const [telephone, setTelephone] = useState('');
  const [errors, setErrors] = useState<Errors>({});
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);
  const [notEligible, setNotEligible] = useState<string | null>(null);
  const [initializing, setInitializing] = useState(true);

  useEffect(() => {
    Promise.all([api.getReturnableItems(orderId), api.getOrder(orderId)])
      .then(([returnable, order]) => {
        setItems(returnable.data);
        setReasons(returnable.reasons);
        setTelephone(order.data.shipping_address?.telephone ?? '');
      })
      .catch((err) => {
        if (err instanceof ApiError) setNotEligible(err.message);
      })
      .finally(() => setInitializing(false));
  }, [orderId]);

  function setQuantity(orderItemId: number, value: number, max: number) {
    setQuantities((q) => ({ ...q, [orderItemId]: Math.max(0, Math.min(value, max)) }));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setErrors({});

    const payloadItems = Object.entries(quantities)
      .filter(([, qty]) => qty > 0)
      .map(([orderItemId, qty]) => ({ order_item_id: Number(orderItemId), quantity: qty }));

    if (payloadItems.length === 0) {
      setErrors({ items: 'Seleccioná al menos un producto y una cantidad a devolver.' });
      return;
    }

    setLoading(true);
    try {
      await api.submitOrderReturn(orderId, { reason, description: description || undefined, telephone, items: payloadItems });
      setSent(true);
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        const mapped: Errors = {};
        for (const [k, v] of Object.entries(err.errors)) mapped[k as keyof Errors] = v[0];
        setErrors(mapped);
      } else if (err instanceof ApiError) {
        setErrors({ _global: err.message });
      }
    } finally {
      setLoading(false);
    }
  }

  if (initializing) {
    return <p className="text-sm text-gray-400 animate-pulse">Cargando...</p>;
  }

  if (notEligible) {
    return (
      <div className="profile-content-box">
        <h2 className="profile-section-title">Solicitar devolución</h2>
        <div className="rounded-lg bg-gray-50 border border-gray-200 px-6 py-8 text-center">
          <p className="text-gray-700 font-medium">{notEligible}</p>
        </div>
      </div>
    );
  }

  if (sent) {
    return (
      <div className="profile-content-box">
        <h2 className="profile-section-title">Solicitar devolución</h2>
        <div className="rounded-lg bg-green-50 border border-green-200 px-6 py-8 text-center">
          <p className="text-green-700 font-semibold text-lg mb-1">¡Solicitud enviada!</p>
          <p className="text-green-600 text-sm">Moira se va a poner en contacto con vos a la brevedad.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="profile-content-box">
      <h2 className="profile-section-title">Solicitar devolución — Pedido #{orderId}</h2>

      <form onSubmit={handleSubmit} className="space-y-5" noValidate>
        {errors._global && (
          <div className="text-sm text-red-600 bg-red-50 border border-red-200 rounded px-4 py-2">{errors._global}</div>
        )}

        <div className="co-field form-row">
          <label className="co-label">Productos a devolver</label>
          <div className="space-y-2">
            {items.map((item) => (
              <div key={item.order_item_id} className="flex items-center justify-between gap-4 border border-gray-200 rounded-lg px-4 py-3">
                <div>
                  <p className="text-sm font-medium text-gray-900">
                    {item.product_name}
                    {item.variant_label && <span className="text-gray-400"> ({item.variant_label})</span>}
                  </p>
                  <p className="text-xs text-gray-400">Disponible para devolver: {item.remaining_quantity}</p>
                </div>
                <input
                  type="number"
                  min={0}
                  max={item.remaining_quantity}
                  value={quantities[item.order_item_id] ?? 0}
                  onChange={(e) => setQuantity(item.order_item_id, Number(e.target.value), item.remaining_quantity)}
                  className="input-text w-20 text-center"
                />
              </div>
            ))}
          </div>
          {errors.items && <span className="co-field-error">{errors.items}</span>}
        </div>

        <div className="co-field form-row">
          <label className="co-label">Motivo</label>
          <select className="input-text" value={reason} onChange={(e) => setReason(e.target.value)} required>
            <option value="" disabled>Seleccioná un motivo</option>
            {reasons.map((r) => (
              <option key={r} value={r}>{r}</option>
            ))}
          </select>
          {errors.reason && <span className="co-field-error">{errors.reason}</span>}
        </div>

        <div className="co-field form-row">
          <label className="co-label">Teléfono</label>
          <input
            type="tel"
            className="input-text"
            value={telephone}
            onChange={(e) => setTelephone(e.target.value)}
            required
          />
          {errors.telephone && <span className="co-field-error">{errors.telephone}</span>}
        </div>

        <div className="co-field form-row">
          <label className="co-label">Descripción (opcional)</label>
          <textarea
            className="input-text"
            rows={12}
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            style={{ resize: 'vertical' }}
          />
          {errors.description && <span className="co-field-error">{errors.description}</span>}
        </div>

        <button type="submit" disabled={loading} className="button alt">
          {loading ? 'Enviando...' : 'Enviar solicitud'}
        </button>
      </form>
    </div>
  );
}
