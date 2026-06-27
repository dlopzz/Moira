'use client';

import { useWishlist, type WishlistItem } from '@/lib/wishlist-context';

function HeartIcon({ filled }: { filled: boolean }) {
  return filled ? (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="var(--global-palette3)" stroke="none">
      <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
    </svg>
  ) : (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--global-palette4)" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
      <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
    </svg>
  );
}

export default function WishlistButton({
  product,
  inline = false,
}: {
  product: WishlistItem;
  inline?: boolean;
}) {
  const { isInWishlist, toggle } = useWishlist();
  const inWishlist = isInWishlist(product.id);

  async function handleClick(e: React.MouseEvent) {
    e.preventDefault();
    e.stopPropagation();
    try {
      await toggle(product);
    } catch {
      // silently ignore
    }
  }

  if (inline) {
    return (
      <button
        onClick={handleClick}
        className="woosw-btn woosw-btn-has-icon woosw-btn-icon-text"
        aria-label={inWishlist ? 'Quitar de favoritos' : 'Agregar a favoritos'}
      >
        <span className="woosw-btn-icon">
          <HeartIcon filled={inWishlist} />
        </span>
        <span className="woosw-btn-text">Favoritos</span>
      </button>
    );
  }

  return (
    <button
      onClick={handleClick}
      aria-label={inWishlist ? 'Quitar de favoritos' : 'Agregar a favoritos'}
      style={{
        position: 'absolute',
        top: '8px',
        right: '8px',
        zIndex: 10,
        width: '34px',
        height: '34px',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'var(--global-palette9)',
        border: inWishlist ? '1px solid var(--global-palette3)' : '1px solid transparent',
        borderRadius: '50%',
        boxShadow: 'rgba(0,0,0,0.16) 0px 1px 3px',
        transition: 'border-color 0.2s ease',
        cursor: 'pointer',
        padding: 0,
      }}
    >
      <HeartIcon filled={inWishlist} />
    </button>
  );
}
