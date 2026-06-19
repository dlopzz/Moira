'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { api, type Product } from '@/lib/api';
import { getToken } from '@/lib/auth';
import { useWishlist } from '@/lib/wishlist-context';
import ProductCard from '@/components/ProductCard';

export default function WishlistPage() {
  const router = useRouter();
  const { ids } = useWishlist();
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!getToken()) {
      router.push('/auth/login?redirect=/profile/wishlist');
      return;
    }
    api
      .getWishlist()
      .then((res) => setProducts(res.data))
      .finally(() => setLoading(false));
  }, [router]);

  // Keep list in sync with wishlist context (removes items toggled off)
  const visible = products.filter((p) => ids.has(p.id));

  if (loading) {
    return <p className="text-sm text-gray-400 animate-pulse">Cargando wishlist...</p>;
  }

  return (
    <div>
      <h1 className="text-xl font-bold text-gray-900 mb-6">Mi wishlist</h1>
      {visible.length === 0 ? (
        <div className="text-center py-12">
          <p className="text-gray-400 font-medium">Tu wishlist está vacía</p>
          <a href="/" className="mt-3 inline-block text-blue-600 hover:underline text-sm">
            Explorar productos
          </a>
        </div>
      ) : (
        <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
          {visible.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>
      )}
    </div>
  );
}
