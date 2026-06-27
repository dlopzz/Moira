'use client';

import { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import Image from 'next/image';
import Link from 'next/link';
import { api, ApiError, imageUrl } from '@/lib/api';

type ReviewInfo = {
  token: string;
  product: { id: number; name: string; slug: string; image: string | null };
  customer_name: string;
};

function StarSelector({ value, onChange }: { value: number; onChange: (v: number) => void }) {
  const [hover, setHover] = useState(0);
  return (
    <div className="flex gap-1">
      {[1, 2, 3, 4, 5].map((star) => (
        <button
          key={star}
          type="button"
          onClick={() => onChange(star)}
          onMouseEnter={() => setHover(star)}
          onMouseLeave={() => setHover(0)}
          className="text-3xl transition-colors"
          aria-label={`${star} estrella${star !== 1 ? 's' : ''}`}
        >
          <span className={(hover || value) >= star ? 'text-yellow-400' : 'text-gray-200'}>
            ★
          </span>
        </button>
      ))}
    </div>
  );
}

export default function ReviewFormPage() {
  const { token } = useParams<{ token: string }>();
  const [info, setInfo] = useState<ReviewInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);
  const [rating, setRating] = useState(0);
  const [title, setTitle] = useState('');
  const [body, setBody] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [success, setSuccess] = useState(false);
  const [errors, setErrors] = useState<Record<string, string[]>>({});

  useEffect(() => {
    api
      .getReview(token)
      .then((res) => setInfo(res.data))
      .catch(() => setNotFound(true))
      .finally(() => setLoading(false));
  }, [token]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setErrors({});
    if (rating === 0) {
      setErrors({ rating: ['Seleccioná una puntuación'] });
      return;
    }
    setSubmitting(true);
    try {
      await api.submitReview(token, { rating, title: title.trim() || undefined, body });
      setSuccess(true);
    } catch (err) {
      if (err instanceof ApiError && err.errors) setErrors(err.errors);
    } finally {
      setSubmitting(false);
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <p className="text-gray-400 animate-pulse">Cargando...</p>
      </div>
    );
  }

  if (notFound) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <p className="text-gray-500 font-medium">Este link ya fue usado o no es válido.</p>
          <Link href="/" className="mt-3 inline-block text-blue-600 hover:underline text-sm">
            Ir al inicio
          </Link>
        </div>
      </div>
    );
  }

  if (success) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-200 p-10 text-center max-w-md">
          <div className="text-5xl mb-4">🎉</div>
          <h1 className="text-xl font-bold text-gray-900 mb-2">¡Gracias por tu reseña!</h1>
          <p className="text-gray-500 text-sm mb-6">
            Tu opinión sobre <strong>{info?.product.name}</strong> fue enviada y será revisada antes de publicarse.
          </p>
          <Link href="/" className="inline-block bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
            Seguir comprando
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-lg">
        {/* Header */}
        <div className="text-center mb-6">
          <Link href="/" className="font-bold text-2xl tracking-tight text-gray-900">Moira</Link>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
          {/* Product header */}
          <div className="flex items-center gap-4 px-6 py-5 border-b border-gray-100">
            <div className="relative w-16 h-16 flex-none rounded-lg overflow-hidden bg-gray-100">
              {info?.product.image ? (
                <Image src={imageUrl(info.product.image)!} alt={info.product.name} fill className="object-cover" sizes="64px" />
              ) : (
                <div className="absolute inset-0 flex items-center justify-center text-gray-300 text-2xl">📦</div>
              )}
            </div>
            <div>
              <p className="text-xs text-gray-400 mb-0.5">Reseña para</p>
              <h2 className="font-semibold text-gray-900 leading-tight">{info?.product.name}</h2>
              <p className="text-xs text-gray-400 mt-0.5">Hola, {info?.customer_name}</p>
            </div>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="px-6 py-6 space-y-5">
            {/* Rating */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Puntuación <span className="text-red-500">*</span>
              </label>
              <StarSelector value={rating} onChange={setRating} />
              {errors.rating && (
                <p className="text-xs text-red-500 mt-1">{errors.rating[0]}</p>
              )}
            </div>

            {/* Title */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Título <span className="text-gray-400 font-normal">(opcional)</span>
              </label>
              <input
                type="text"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="Ej: Excelente producto"
                maxLength={255}
                className="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            {/* Body */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Tu comentario <span className="text-red-500">*</span>
              </label>
              <textarea
                value={body}
                onChange={(e) => setBody(e.target.value)}
                placeholder="Contá tu experiencia con el producto..."
                rows={4}
                maxLength={2000}
                required
                minLength={10}
                className="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
              />
              {errors.body && (
                <p className="text-xs text-red-500 mt-1">{errors.body[0]}</p>
              )}
              <p className="text-xs text-gray-400 mt-1 text-right">{body.length}/2000</p>
            </div>

            <button
              type="submit"
              disabled={submitting}
              className="w-full bg-blue-600 text-white font-semibold py-3 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-60"
            >
              {submitting ? 'Enviando...' : 'Enviar reseña'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
