import Link from 'next/link';
import Image from 'next/image';
import type { Product } from '@/lib/api';
import WishlistButton from '@/components/WishlistButton';

export default function ProductCard({ product }: { product: Product }) {
  const image = product.images[0];
  const hasDiscount = product.sale_price !== null && product.sale_price < product.price;

  return (
    <Link
      href={`/products/${product.slug}`}
      className="group bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow"
    >
      <div className="relative aspect-square bg-gray-100">
        {image ? (
          <Image
            src={image}
            alt={product.name}
            fill
            className="object-cover group-hover:scale-105 transition-transform duration-300"
            sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 25vw"
          />
        ) : (
          <div className="absolute inset-0 flex items-center justify-center text-gray-300 text-4xl">
            📦
          </div>
        )}
        <WishlistButton productId={product.id} />
      </div>
      <div className="p-3">
        <p className="text-xs text-gray-400 mb-1 truncate">
          {product.categories[0]?.name ?? ''}
        </p>
        <h3 className="font-medium text-gray-900 text-sm leading-snug line-clamp-2 mb-2">
          {product.name}
        </h3>
        <div className="flex items-baseline gap-2">
          {hasDiscount ? (
            <>
              <span className="text-base font-bold text-red-600">
                ${product.sale_price!.toFixed(2)}
              </span>
              <span className="text-xs text-gray-400 line-through">
                ${product.price.toFixed(2)}
              </span>
            </>
          ) : (
            <span className="text-base font-bold text-gray-900">
              ${product.price.toFixed(2)}
            </span>
          )}
        </div>
      </div>
    </Link>
  );
}
