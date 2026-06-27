'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { api, imageUrl, type Product } from '@/lib/api';
import { getToken } from '@/lib/auth';
import { useWishlist } from '@/lib/wishlist-context';
import { useCart } from '@/lib/cart-context';

type WishlistProduct = Product & { date_added?: string };

function priceHtml(price: number, salePrice: number | null): string {
  const fmt = (n: number) =>
    n.toLocaleString('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 });
  if (salePrice !== null && salePrice < price) {
    return `<del>${fmt(price)}</del> <ins>${fmt(salePrice)}</ins>`;
  }
  return fmt(price);
}

export default function WishlistPage() {
  const { toggle, ids } = useWishlist();
  const { addItem } = useCart();
  const [products, setProducts] = useState<WishlistProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [addingId, setAddingId] = useState<number | null>(null);

  useEffect(() => {
    if (!getToken()) { setLoading(false); return; }
    api.getWishlist()
      .then((res) => setProducts(res.data))
      .finally(() => setLoading(false));
  }, []);

  const visible = products.filter((p) => ids.has(p.id));

  async function handleRemove(product: WishlistProduct) {
    await toggle({
      id: product.id,
      name: product.name,
      slug: product.slug,
      price: product.price,
      sale_price: product.sale_price,
      image: product.images[0] ?? null,
    });
  }

  async function handleAddToCart(product: WishlistProduct) {
    setAddingId(product.id);
    try {
      await addItem(product.id, 1);
    } finally {
      setAddingId(null);
    }
  }

  if (loading) {
    return <p style={{ fontSize: 14, color: 'var(--global-palette4)' }}>Cargando wishlist...</p>;
  }

  if (visible.length === 0) {
    return (
      <div className="woosw-list">
        <div className="woosw-popup-content-mid-message">
          ¡No hay productos en la Wishlist!
        </div>
      </div>
    );
  }

  const wishlistUrl = typeof window !== 'undefined'
    ? `${window.location.origin}/profile/wishlist`
    : '/profile/wishlist';

  return (
    <div className="woosw-list">
      <table className="woosw-items">
        <tbody>
          {visible.map((product) => {
            const image = product.images[0] ?? null;
            const inStock = product.stock > 0;

            return (
              <tr
                key={product.id}
                className={`woosw-item woosw-item-${product.id}`}
                data-id={product.id}
              >
                <td className="woosw-item--remove">
                  <span
                    onClick={() => handleRemove(product)}
                    title="Eliminar de la wishlist"
                    aria-label="Eliminar"
                  />
                </td>

                <td className="woosw-item--image">
                  <Link href={`/products/${product.slug}`}>
                    {image ? (
                      <img
                        src={imageUrl(image) ?? ''}
                        alt={product.name}
                      />
                    ) : (
                      <div className="woosw-no-image" />
                    )}
                  </Link>
                </td>

                <td className="woosw-item--info">
                  <div className="woosw-item--name">
                    <Link href={`/products/${product.slug}`}>{product.name}</Link>
                  </div>
                  <div
                    className="woosw-item--price"
                    dangerouslySetInnerHTML={{ __html: priceHtml(product.price, product.sale_price) }}
                  />
                  {product.date_added && (
                    <div className="woosw-item--time">{product.date_added}</div>
                  )}
                </td>

                <td className="woosw-item--actions">
                  <div className="woosw-item--stock">
                    {product.product_type !== 'configurable' && (
                      <div className={`stock entry-product-stock ${inStock ? 'in-stock' : 'out-of-stock'}`}>
                        {product.stock} disponibles
                      </div>
                    )}
                  </div>
                  <div className="woosw-item--atc">
                    {product.product_type === 'configurable' ? (
                      <Link href={`/products/${product.slug}`} className="button">
                        Seleccionar opciones
                      </Link>
                    ) : (
                      <button
                        className="button add_to_cart_button ajax_add_to_cart"
                        disabled={!inStock || addingId === product.id}
                        onClick={() => handleAddToCart(product)}
                      >
                        {addingId === product.id ? '...' : 'Añadir al carrito'}
                      </button>
                    )}
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>

      <div className="woosw-actions">
        <div className="woosw-copy">
          <span className="woosw-copy-label">Wishlist link:</span>
          <span className="woosw-copy-url">
            <input type="url" defaultValue={wishlistUrl} readOnly />
          </span>
          <span className="woosw-copy-btn">
            <button
              type="button"
              className="button"
              onClick={() => navigator.clipboard.writeText(wishlistUrl)}
            >
              Copiar
            </button>
          </span>
        </div>
      </div>
    </div>
  );
}
