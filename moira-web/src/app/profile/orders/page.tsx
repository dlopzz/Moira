'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { api, type Order, type PaginationMeta, formatPrice } from '@/lib/api';
import { getToken } from '@/lib/auth';

const STATUS_LABEL: Record<string, string> = {
  pending: 'Pendiente',
  processing: 'En proceso',
  completed: 'Completado',
  cancelled: 'Cancelado',
};

const STATUS_CLASS: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-700',
  processing: 'bg-blue-100 text-blue-700',
  completed: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
};

export default function OrdersPage() {
  const router = useRouter();
  const [orders, setOrders] = useState<Order[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [expanded, setExpanded] = useState<number | null>(null);
  const [orderDetail, setOrderDetail] = useState<Record<number, Order>>({});

  useEffect(() => {
    if (!getToken()) {
      router.push('/auth/login?redirect=/profile/orders');
      return;
    }
    setLoading(true);
    api
      .getOrders(page)
      .then((res) => {
        setOrders(res.data);
        setMeta(res.meta);
      })
      .finally(() => setLoading(false));
  }, [page, router]);

  async function toggleExpand(order: Order) {
    if (expanded === order.id) {
      setExpanded(null);
      return;
    }
    setExpanded(order.id);
    if (!orderDetail[order.id]) {
      const res = await api.getOrder(order.id);
      setOrderDetail((prev) => ({ ...prev, [order.id]: res.data }));
    }
  }

  if (loading) {
    return <p className="text-sm text-gray-400 animate-pulse">Cargando pedidos...</p>;
  }

  if (orders.length === 0) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-400 font-medium">Todavía no tenés pedidos</p>
        <a href="/" className="mt-3 inline-block text-blue-600 hover:underline text-sm">
          Empezar a comprar
        </a>
      </div>
    );
  }

  return (
    <div>
      <h1 className="text-xl font-bold text-gray-900 mb-6">Mis pedidos</h1>
      <div className="space-y-3">
        {orders.map((order) => {
          const detail = orderDetail[order.id];
          const isOpen = expanded === order.id;
          const statusClass = STATUS_CLASS[order.status] ?? 'bg-gray-100 text-gray-600';
          const statusLabel = STATUS_LABEL[order.status] ?? order.status;

          return (
            <div key={order.id} className="bg-white border border-gray-200 rounded-xl overflow-hidden">
              <button
                onClick={() => toggleExpand(order)}
                className="w-full text-left px-5 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
              >
                <div className="flex items-center gap-4">
                  <div>
                    <p className="text-sm font-semibold text-gray-900">Pedido #{order.number}</p>
                    <p className="text-xs text-gray-400 mt-0.5">
                      {new Date(order.created_at).toLocaleDateString('es-AR', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric',
                      })}
                    </p>
                  </div>
                  <span className={`text-xs font-medium px-2 py-0.5 rounded-full ${statusClass}`}>
                    {statusLabel}
                  </span>
                </div>
                <div className="flex items-center gap-4">
                  <p className="text-sm font-bold text-gray-900">${formatPrice(order.total)}</p>
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className={`w-4 h-4 text-gray-400 transition-transform ${isOpen ? 'rotate-180' : ''}`}
                    fill="none"
                    viewBox="0 0 24 24"
                    strokeWidth={2}
                    stroke="currentColor"
                  >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                  </svg>
                </div>
              </button>

              {isOpen && (
                <div className="border-t border-gray-100 px-5 py-4">
                  {!detail ? (
                    <p className="text-xs text-gray-400 animate-pulse">Cargando detalle...</p>
                  ) : (
                    <>
                      {/* Items */}
                      <div className="space-y-2 mb-4">
                        {detail.items?.map((item) => (
                          <div key={item.id} className="flex justify-between text-sm">
                            <span className="text-gray-700">
                              {item.name}
                              <span className="text-gray-400 ml-1">× {item.quantity}</span>
                            </span>
                            <span className="text-gray-900 font-medium">${formatPrice(item.subtotal)}</span>
                          </div>
                        ))}
                      </div>

                      {/* Summary */}
                      <div className="border-t border-gray-100 pt-3 space-y-1.5 text-sm">
                        <div className="flex justify-between text-gray-500">
                          <span>Subtotal</span>
                          <span>${formatPrice(detail.subtotal)}</span>
                        </div>
                        {detail.discount > 0 && (
                          <div className="flex justify-between text-green-600">
                            <span>Descuento</span>
                            <span>−${formatPrice(detail.discount)}</span>
                          </div>
                        )}
                        <div className="flex justify-between font-bold text-gray-900">
                          <span>Total</span>
                          <span>${formatPrice(detail.total)}</span>
                        </div>
                      </div>

                      {/* Shipping address */}
                      {detail.shipping_address && (
                        <div className="mt-3 pt-3 border-t border-gray-100">
                          <p className="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Envío a</p>
                          <p className="text-sm text-gray-700">
                            {detail.shipping_address.street}
                            {detail.shipping_address.address_line_2 && `, ${detail.shipping_address.address_line_2}`}
                          </p>
                          <p className="text-sm text-gray-500">
                            {detail.shipping_address.city}, {detail.shipping_address.state} {detail.shipping_address.zip_code}
                          </p>
                        </div>
                      )}
                    </>
                  )}
                </div>
              )}
            </div>
          );
        })}
      </div>

      {meta && meta.last_page > 1 && (
        <div className="flex justify-center items-center gap-2 mt-6">
          <button
            onClick={() => setPage((p) => p - 1)}
            disabled={page === 1}
            className="px-3 py-1.5 text-sm border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50"
          >
            Anterior
          </button>
          <span className="text-sm text-gray-600">
            {meta.current_page} / {meta.last_page}
          </span>
          <button
            onClick={() => setPage((p) => p + 1)}
            disabled={page === meta.last_page}
            className="px-3 py-1.5 text-sm border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50"
          >
            Siguiente
          </button>
        </div>
      )}
    </div>
  );
}
