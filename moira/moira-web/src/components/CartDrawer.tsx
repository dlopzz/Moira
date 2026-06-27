'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { imageUrl, formatPrice } from '@/lib/api';
import { useCart } from '@/lib/cart-context';

export default function CartDrawer() {
  const { cart, cartOpen, closeCart, updateItem, removeItem } = useCart();
  const [isVisible, setIsVisible] = useState(false);
  const [isActive, setIsActive] = useState(false);

  useEffect(() => {
    if (cartOpen) {
      const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
      document.documentElement.style.overflow = 'hidden';
      document.documentElement.style.paddingRight = `${scrollbarWidth}px`;
      setIsVisible(true);
      requestAnimationFrame(() => requestAnimationFrame(() => setIsActive(true)));
    } else {
      setIsActive(false);
      const timer = setTimeout(() => setIsVisible(false), 300);
      document.documentElement.style.overflow = '';
      document.documentElement.style.paddingRight = '';
      return () => clearTimeout(timer);
    }
  }, [cartOpen]);

  const items = cart?.items ?? [];
  const total = cart?.summary.total ?? 0;
  const subtotal = cart?.summary.subtotal ?? 0;
  const discount = cart?.summary.discount ?? 0;

  return (
    <div
      id="cart-drawer"
      className={`popup-drawer popup-drawer-layout-sidepanel popup-drawer-side-right popup-mobile-drawer-side-right${isVisible ? ' show-drawer' : ''}${isActive ? ' active' : ''}`}
      data-drawer-target-string="#cart-drawer"
    >
      <div className="drawer-overlay" onClick={closeCart} />
      <div className="drawer-inner">
        <div className="drawer-header">
          <h2 className="side-cart-header">Carrito de compras</h2>
          <button
            className="cart-toggle-close drawer-toggle"
            aria-label="Cerrar carrito"
            onClick={closeCart}
          >
            <span className="base-svg-iconset">
              <svg className="base-svg-icon base-close-svg" fill="currentColor" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                <title>Cerrar</title>
                <path d="M5.293 6.707l5.293 5.293-5.293 5.293c-0.391 0.391-0.391 1.024 0 1.414s1.024 0.391 1.414 0l5.293-5.293 5.293 5.293c0.391 0.391 1.024 0.391 1.414 0s0.391-1.024 0-1.414l-5.293-5.293 5.293-5.293c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-5.293 5.293-5.293-5.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414z" />
              </svg>
            </span>
          </button>
        </div>

        <div className="drawer-content woocommerce widget_shopping_cart">
          {items.length === 0 ? (
            <div className="woocommerce-mini-cart__empty-message">
              <h4>Tu carrito está vacío</h4>
              <p>No hay productos en tu carrito. ¡Explorá nuestros productos!</p>
              <Link href="/categories" className="button" onClick={closeCart}>
                Ver productos
              </Link>
            </div>
          ) : (
            <>
              <ul className="woocommerce-mini-cart cart_list product_list_widget">
                {items.map((item) => {
                  const img = imageUrl(item.image);
                  const href = item.product_slug
                    ? `/products/${item.product_slug}`
                    : `/products/${item.product_id}`;

                  return (
                    <li key={item.id} className="woocommerce-mini-cart-item mini_cart_item">
                      <a
                        role="button"
                        href="#"
                        className="remove remove_from_cart_button"
                        aria-label={`Eliminar ${item.name}`}
                        onClick={(e) => { e.preventDefault(); removeItem(item.id); }}
                      >
                        &times;
                      </a>

                      <Link href={href} onClick={closeCart}>
                        {img && (
                          <img
                            src={img}
                            alt={item.name}
                            width={80}
                          />
                        )}
                        {item.name}
                      </Link>

                      {item.variant_label && (
                        <dl className="variation">
                          <dt>Talle:</dt>
                          <dd><p>{item.variant_label}</p></dd>
                        </dl>
                      )}

                      <span className="quantity">
                        {item.quantity} &times;{' '}
                        <span className="woocommerce-Price-amount amount">
                          <bdi>${formatPrice(item.unit_price)}</bdi>
                        </span>
                      </span>

                      <div className="moira-qty-controls">
                        <button
                          onClick={() => updateItem(item.id, item.quantity - 1)}
                          aria-label="Reducir cantidad"
                        >−</button>
                        <span>{item.quantity}</span>
                        <button
                          onClick={() => updateItem(item.id, item.quantity + 1)}
                          aria-label="Aumentar cantidad"
                        >+</button>
                      </div>
                    </li>
                  );
                })}
              </ul>

              <div className="widget_shopping_cart_footer">
                {discount > 0 && (
                  <p className="woocommerce-mini-cart__total total moira-subtotal">
                    <strong>Subtotal:</strong>
                    <span className="woocommerce-Price-amount amount">
                      <bdi>${formatPrice(subtotal)}</bdi>
                    </span>
                  </p>
                )}
                {discount > 0 && (
                  <p className="woocommerce-mini-cart__total total moira-discount">
                    <strong>Descuento:</strong>
                    <span className="woocommerce-Price-amount amount">
                      <bdi>-${formatPrice(discount)}</bdi>
                    </span>
                  </p>
                )}
                <p className="woocommerce-mini-cart__total total">
                  <strong>Subtotal:</strong>
                  <span className="woocommerce-Price-amount amount">
                    <bdi>${formatPrice(total)}</bdi>
                  </span>
                </p>
                <p className="woocommerce-mini-cart__buttons buttons">
                  <Link href="/cart" className="button wc-forward" onClick={closeCart}>
                    Ver carrito
                  </Link>
                  <Link href="/checkout/shipping" className="button checkout wc-forward" onClick={closeCart}>
                    Finalizar compra
                  </Link>
                </p>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
