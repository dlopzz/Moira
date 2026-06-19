'use client';

import { usePathname, useRouter } from 'next/navigation';
import { getToken } from '@/lib/auth';
import { useWishlist } from '@/lib/wishlist-context';

export default function WishlistButton({
  productId,
  inline = false,
}: {
  productId: number;
  inline?: boolean;
}) {
  const { isInWishlist, toggle } = useWishlist();
  const router = useRouter();
  const pathname = usePathname();
  const inWishlist = isInWishlist(productId);

  async function handleClick(e: React.MouseEvent) {
    e.preventDefault();
    e.stopPropagation();
    if (!getToken()) {
      router.push(`/auth/login?redirect=${encodeURIComponent(pathname)}`);
      return;
    }
    await toggle(productId);
  }

  const cls = inline
    ? 'p-1.5 rounded-full hover:bg-gray-100 transition-colors'
    : 'absolute top-2 right-2 p-1.5 bg-white/80 backdrop-blur-sm rounded-full shadow-sm hover:bg-white transition-colors z-10';

  return (
    <button
      onClick={handleClick}
      className={cls}
      aria-label={inWishlist ? 'Quitar de favoritos' : 'Agregar a favoritos'}
    >
      {inWishlist ? (
        <svg xmlns="http://www.w3.org/2000/svg" className="w-4 h-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
        </svg>
      ) : (
        <svg xmlns="http://www.w3.org/2000/svg" className="w-4 h-4 text-gray-400 hover:text-yellow-400 transition-colors" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
        </svg>
      )}
    </button>
  );
}
