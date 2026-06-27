'use client';

import Link from 'next/link';
import { useWishlist } from '@/lib/wishlist-context';
import { imageUrl, formatPrice } from '@/lib/api';

export default function WishlistPopup() {
  const { items, isOpen, closePopup, toggle, lastAdded } = useWishlist();

  function handleBackdrop(e: React.MouseEvent<HTMLDivElement>) {
    if (e.target === e.currentTarget) closePopup();
  }

  return (
    <div
      id="woosw_wishlist"
      className={`woosw-popup woosw-popup-center${isOpen ? ' woosw-show' : ''}`}
    >
      <div className="woosw-popup-inner" onClick={handleBackdrop}>
        <div className="woosw-popup-content">

          <div className="woosw-popup-content-top">
            Lista de deseos&nbsp;
            <span className="woosw-count-wrapper">{items.length}</span>
            <span className="woosw-popup-close" onClick={closePopup} role="button" aria-label="Cerrar" />
          </div>

          <div className="woosw-popup-content-mid">
            {items.length === 0 ? (
              <div className="woosw-popup-content-mid-message">Tu lista de deseos está vacía.</div>
            ) : (
              <div className="woosw-items">
                {items.map((item) => (
                  <div key={item.id} className="woosw-item">
                    <div className="woosw-item-inner">
                      <div className="woosw-item--image">
                        <Link href={`/products/${item.slug}`} onClick={closePopup}>
                          {item.image
                            ? <img src={imageUrl(item.image)!} alt={item.name} />
                            : <div className="woosw-item--no-image">📦</div>
                          }
                        </Link>
                      </div>
                      <div className="woosw-item--info">
                        <div className="woosw-item--name">
                          <Link href={`/products/${item.slug}`} onClick={closePopup}>
                            {item.name}
                          </Link>
                        </div>
                        <div className="woosw-item--price">
                          ${formatPrice(item.sale_price ?? item.price)}
                        </div>
                      </div>
                      <div className="woosw-item--remove">
                        <span
                          onClick={() => toggle(item)}
                          role="button"
                          aria-label={`Quitar ${item.name}`}
                        />
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="woosw-popup-content-bot">
            <div className="woosw-popup-content-bot-inner">
              <Link href="/profile/wishlist" onClick={closePopup}>Ver lista de deseos</Link>
            </div>
            <div className={`woosw-notice${lastAdded ? ' woosw-notice-show' : ''}`}>
              ¡Añadido a la lista de deseos!
            </div>
          </div>

        </div>
      </div>
    </div>
  );
}
