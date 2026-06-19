'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { api, type MyReview } from '@/lib/api';
import { getToken } from '@/lib/auth';

const STARS = [1, 2, 3, 4, 5];

function StarRating({ rating }: { rating: number }) {
  return (
    <span className="flex gap-0.5">
      {STARS.map((s) => (
        <svg key={s} className={`w-4 h-4 ${s <= rating ? 'text-yellow-400' : 'text-gray-200'}`} fill="currentColor" viewBox="0 0 20 20">
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
      ))}
    </span>
  );
}

export default function ReviewsPage() {
  const router = useRouter();
  const [reviews, setReviews] = useState<MyReview[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!getToken()) { router.push('/auth/login?redirect=/profile/reviews'); return; }
    api.getMyReviews()
      .then((res) => setReviews(res.data))
      .finally(() => setLoading(false));
  }, [router]);

  if (loading) {
    return <p className="text-sm text-gray-400 animate-pulse">Cargando reseñas...</p>;
  }

  if (reviews.length === 0) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-400 font-medium">Todavía no tenés reseñas</p>
        <p className="text-xs text-gray-400 mt-1">Cuando comprés un producto te vamos a mandar un email para que puedas calificarlo.</p>
      </div>
    );
  }

  const pending = reviews.filter((r) => !r.submitted_at);
  const submitted = reviews.filter((r) => r.submitted_at);

  return (
    <div>
      <h1 className="text-xl font-bold text-gray-900 mb-6">Mis reseñas</h1>

      {pending.length > 0 && (
        <div className="mb-8">
          <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Pendientes de envío</h2>
          <div className="space-y-3">
            {pending.map((review) => (
              <div key={review.id} className="bg-white border border-yellow-200 rounded-xl p-4 flex items-center gap-4">
                {review.product?.image && (
                  <img src={review.product.image} alt={review.product.name} className="w-14 h-14 object-cover rounded-lg flex-none" />
                )}
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900 truncate">
                    {review.product?.name ?? 'Producto eliminado'}
                  </p>
                  <p className="text-xs text-gray-400 mt-0.5">Pendiente de calificación</p>
                </div>
                <Link
                  href={`/reviews/${review.token}`}
                  className="flex-none text-sm font-medium text-blue-600 hover:underline"
                >
                  Calificar
                </Link>
              </div>
            ))}
          </div>
        </div>
      )}

      {submitted.length > 0 && (
        <div>
          <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Enviadas</h2>
          <div className="space-y-3">
            {submitted.map((review) => (
              <div key={review.id} className="bg-white border border-gray-200 rounded-xl p-4">
                <div className="flex items-start gap-4">
                  {review.product?.image && (
                    <img src={review.product.image} alt={review.product?.name} className="w-14 h-14 object-cover rounded-lg flex-none" />
                  )}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-2 mb-1">
                      {review.product ? (
                        <Link href={`/products/${review.product.slug}`} className="text-sm font-medium text-gray-900 hover:underline truncate">
                          {review.product.name}
                        </Link>
                      ) : (
                        <span className="text-sm font-medium text-gray-500 truncate">Producto eliminado</span>
                      )}
                      <span className={`flex-none text-xs px-2 py-0.5 rounded-full ${review.is_approved ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}`}>
                        {review.is_approved ? 'Aprobada' : 'En revisión'}
                      </span>
                    </div>
                    {review.rating && <StarRating rating={review.rating} />}
                    {review.title && <p className="text-sm font-medium text-gray-800 mt-1">{review.title}</p>}
                    {review.body && <p className="text-sm text-gray-600 mt-0.5 line-clamp-2">{review.body}</p>}
                    <p className="text-xs text-gray-400 mt-1">
                      {new Date(review.submitted_at!).toLocaleDateString('es-AR', { day: '2-digit', month: 'long', year: 'numeric' })}
                    </p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
