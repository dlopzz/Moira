'use client';

import { useState } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import type { Product } from '@/lib/api';
import { imageUrl, imageMediumUrl, formatPrice } from '@/lib/api';
import type { WishlistItem } from '@/lib/wishlist-context';
import { useCart } from '@/lib/cart-context';
import WishlistButton from '@/components/WishlistButton';

export default function ProductCard({
  product,
  view = 'grid',
  showSaleBadge = true,
}: {
  product: Product;
  view?: 'grid' | 'list';
  showSaleBadge?: boolean;
}) {
  const { addItem } = useCart();
  const [adding, setAdding] = useState(false);
  const [added, setAdded] = useState(false);

  const image = imageMediumUrl(product.images[0]) ?? imageUrl(product.images[0]);
  const hoverImage = imageMediumUrl(product.images[1]) ?? imageUrl(product.images[1]);
  const wishlistItem: WishlistItem = {
    id: product.id,
    name: product.name,
    slug: product.slug,
    price: product.price,
    sale_price: product.sale_price,
    image: product.images[0] ?? null,
  };
  const hasDiscount = product.sale_price !== null && product.sale_price < product.price;
  const isConfigurable = product.product_type === 'configurable';
  const inStock = product.stock > 0;

  async function handleAddToCart(e: React.MouseEvent) {
    e.preventDefault();
    e.stopPropagation();
    if (adding || added) return;
    setAdding(true);
    try {
      await addItem(product.id, 1);
      setAdded(true);
      setTimeout(() => setAdded(false), 2500);
    } catch {
      // silent
    } finally {
      setAdding(false);
    }
  }

  const PriceBlock = () => (
    <span className="price">
      {hasDiscount ? (
        <>
          <del aria-hidden="true">
            <span className="woocommerce-Price-amount amount">
              <bdi>${formatPrice(product.price)}</bdi>
            </span>
          </del>{' '}
          <ins aria-hidden="true">
            <span className="woocommerce-Price-amount amount">
              <bdi>${formatPrice(product.sale_price!)}</bdi>
            </span>
          </ins>
        </>
      ) : (
        <span className="woocommerce-Price-amount amount">
          <bdi>${formatPrice(product.price)}</bdi>
        </span>
      )}
    </span>
  );

  if (view === 'list') {
    return (
      <>
        <div className="product-thumbnail">
          <Link
            href={`/products/${product.slug}`}
            className="woocommerce-loop-image-link woocommerce-LoopProduct-link woocommerce-loop-product__link"
            aria-label={product.name}
          >
            {showSaleBadge && hasDiscount && <span className="onsale">¡Oferta!</span>}
            {image ? (
              <Image
                src={image}
                alt={product.name}
                width={275}
                height={367}
                className="attachment-woocommerce_thumbnail size-woocommerce_thumbnail"
                sizes="200px"
              />
            ) : (
              <div className="product-image-placeholder" />
            )}
          </Link>
          <div className="product-actions">
            <WishlistButton product={wishlistItem} />
          </div>
        </div>
        <div className="entry-content-wrap product-details">
          <h2 className="woocommerce-loop-product__title">
            <Link href={`/products/${product.slug}`} className="woocommerce-LoopProduct-link-title woocommerce-loop-product__title_link">
              {product.name}
            </Link>
          </h2>
          <PriceBlock />
          {product.short_description && (
            <div className="product-excerpt">{product.short_description}</div>
          )}
          <div className="product-action-wrap style-button">
            {isConfigurable ? (
              <Link href={`/products/${product.slug}`} className="button add_to_cart_button">
                Ver opciones
              </Link>
            ) : (
              <button
                onClick={handleAddToCart}
                disabled={!inStock || adding}
                className={`button add_to_cart_button${added ? ' added' : ''}`}
              >
                {added ? '¡Agregado!' : adding ? 'Agregando...' : inStock ? 'Agregar al carrito' : 'Sin stock'}
              </button>
            )}
          </div>
        </div>
      </>
    );
  }

  return (
    <>
      <div className="product-thumbnail">
        <Link
          href={`/products/${product.slug}`}
          className={`woocommerce-loop-image-link woocommerce-LoopProduct-link woocommerce-loop-product__link${hoverImage ? ' product-has-hover-image' : ''}`}
          aria-label={product.name}
        >
          {showSaleBadge && hasDiscount && <span className="onsale">¡Oferta!</span>}
          {image ? (
            <Image
              src={image}
              alt={product.name}
              width={275}
              height={367}
              className="attachment-woocommerce_thumbnail size-woocommerce_thumbnail"
              sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 25vw"
            />
          ) : (
            <div className="product-image-placeholder" />
          )}
          {hoverImage && (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={hoverImage}
              alt=""
              loading="lazy"
              className="secondary-product-image attachment-woocommerce_thumbnail attachment-shop-catalog wp-post-image--secondary"
            />
          )}
        </Link>
        <div className="product-actions">
          <WishlistButton product={wishlistItem} />
        </div>
      </div>
      <div className="product-details content-bg entry-content-wrap">
        <h2 className="woocommerce-loop-product__title">
          <Link href={`/products/${product.slug}`} className="woocommerce-LoopProduct-link-title woocommerce-loop-product__title_link">
            {product.name}
          </Link>
        </h2>
        <PriceBlock />
        <div className="product-action-wrap style-button">
          {isConfigurable ? (
            <Link href={`/products/${product.slug}`} className="button add_to_cart_button">
              Ver opciones
            </Link>
          ) : (
            <button
              onClick={handleAddToCart}
              disabled={!inStock || adding}
              className={`button add_to_cart_button${added ? ' added' : ''}`}
            >
              {added ? '¡Agregado!' : adding ? 'Agregando...' : inStock ? 'Agregar al carrito' : 'Sin stock'}
            </button>
          )}
        </div>
      </div>
    </>
  );
}
