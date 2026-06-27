'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { api, type Cart, ApiError, imageUrl, formatPrice } from '@/lib/api';
import Header from '@/components/Header';
import Breadcrumb from '@/components/Breadcrumb';

const crumbs = [{ name: 'Inicio', href: '/' }, { name: 'Carrito' }];

export default function CartPage() {
  const [cart, setCart] = useState<Cart | null>(null);
  const [loading, setLoading] = useState(true);
  const [couponInput, setCouponInput] = useState('');
  const [couponError, setCouponError] = useState('');
  const [couponLoading, setCouponLoading] = useState(false);

  useEffect(() => {
    api.getCart()
      .then((res) => setCart(res.data))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  async function updateQty(itemId: number, qty: number) {
    if (qty < 1) return;
    const res = await api.updateCartItem(itemId, qty);
    setCart(res.data);
  }

  async function removeItem(itemId: number) {
    const res = await api.removeCartItem(itemId);
    setCart(res.data);
  }

  async function applyCoupon() {
    if (!couponInput.trim() || couponLoading) return;
    setCouponError('');
    setCouponLoading(true);
    try {
      const res = await api.applyCoupon(couponInput.trim());
      setCart(res.data);
      setCouponInput('');
    } catch (err) {
      if (err instanceof ApiError) setCouponError(err.message);
    } finally {
      setCouponLoading(false);
    }
  }

  async function removeCoupon() {
    const res = await api.removeCoupon();
    setCart(res.data);
  }

  const isEmpty = !cart || cart.items.length === 0;

  return (
    <>
      <Header />

      <div className="entry-hero woocommerce-cart-hero-section">
        <div className="entry-hero-container-inner">
          <div className="hero-section-overlay" />
          <div className="hero-container site-container">
            <header className="entry-header">
              <Breadcrumb crumbs={crumbs} />
              <h1 className="entry-title page-title">Carrito</h1>
            </header>
          </div>
        </div>
      </div>

      <div className="woocommerce woocommerce-cart woocommerce-page">
        <div className="site-container cart-page-container">

          {loading ? (
            <p className="cart-loading">Cargando carrito...</p>
          ) : isEmpty ? (
            <>
              <div className="wc-empty-cart-message">
                <p className="cart-empty">Tu carrito está vacío.</p>
              </div>
              <p className="return-to-shop">
                <Link href="/" className="button wc-backward">Volver a la tienda</Link>
              </p>
            </>
          ) : (
            <div className="base-woo-cart-form-wrap">
              {/* ── Left: Cart form ── */}
              <form className="woocommerce-cart-form" onSubmit={(e) => e.preventDefault()}>
                <div className="cart-summary">
                  <h2>Resumen del carrito</h2>
                </div>

                <table className="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellSpacing={0}>
                  <thead>
                    <tr>
                      <th className="product-remove"><span className="screen-reader-text">Eliminar artículo</span></th>
                      <th className="product-thumbnail"><span className="screen-reader-text">Imagen</span></th>
                      <th className="product-name">Producto</th>
                      <th className="product-price">Precio</th>
                      <th className="product-quantity">Cantidad</th>
                      <th className="product-subtotal">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    {cart.items.map((item) => {
                      const href = `/products/${item.product_slug ?? item.product_id}`;
                      return (
                        <tr key={item.id} className="woocommerce-cart-form__cart-item cart_item">
                          <td className="product-remove">
                            <button
                              type="button"
                              className="remove"
                              onClick={() => removeItem(item.id)}
                              aria-label={`Eliminar ${item.name}`}
                            >&times;</button>
                          </td>
                          <td className="product-thumbnail">
                            <Link href={href}>
                              {item.image ? (
                                <Image
                                  src={imageUrl(item.image)!}
                                  alt={item.name}
                                  width={86}
                                  height={115}
                                  style={{ objectFit: 'cover' }}
                                />
                              ) : (
                                <div className="cart-item-no-image" />
                              )}
                            </Link>
                          </td>
                          <td className="product-name" data-title="Producto">
                            <Link href={href}>{item.name}</Link>
                            {item.variant_label && (
                              <dl className="variation">
                                <dt>Variante:</dt>
                                <dd>{item.variant_label}</dd>
                              </dl>
                            )}
                            {item.sku && (
                              <p className="cart-item-sku">SKU: {item.sku}</p>
                            )}
                          </td>
                          <td className="product-price" data-title="Precio">
                            <span className="woocommerce-Price-amount amount">
                              <bdi>${formatPrice(item.unit_price)}</bdi>
                            </span>
                          </td>
                          <td className="product-quantity" data-title="Cantidad">
                            <div className="quantity spinners-added">
                              <button
                                type="button"
                                className="quantity-btn minus"
                                onClick={() => updateQty(item.id, item.quantity - 1)}
                                aria-label="Reducir cantidad"
                              >−</button>
                              <input
                                type="number"
                                className="input-text qty text"
                                value={item.quantity}
                                min={1}
                                onChange={(e) => updateQty(item.id, parseInt(e.target.value) || 1)}
                                aria-label="Cantidad"
                              />
                              <button
                                type="button"
                                className="quantity-btn plus"
                                onClick={() => updateQty(item.id, item.quantity + 1)}
                                aria-label="Aumentar cantidad"
                              >+</button>
                            </div>
                          </td>
                          <td className="product-subtotal" data-title="Subtotal">
                            <span className="woocommerce-Price-amount amount">
                              <bdi>${formatPrice(item.subtotal)}</bdi>
                            </span>
                          </td>
                        </tr>
                      );
                    })}

                    <tr>
                      <td colSpan={6} className="actions">
                        <div className="coupon">
                          {cart.coupon_code ? (
                            <div className="applied-coupon">
                              <span>
                                Cupón <strong>{cart.coupon_code}</strong> aplicado
                                {cart.summary.discount > 0 && ` (−$${formatPrice(cart.summary.discount)})`}
                              </span>
                              <button type="button" className="button" onClick={removeCoupon}>
                                Quitar cupón
                              </button>
                            </div>
                          ) : (
                            <>
                              <label htmlFor="coupon_code" className="screen-reader-text">Cupón:</label>
                              <input
                                type="text"
                                name="coupon_code"
                                className="input-text"
                                id="coupon_code"
                                value={couponInput}
                                onChange={(e) => { setCouponInput(e.target.value); setCouponError(''); }}
                                onKeyDown={(e) => e.key === 'Enter' && applyCoupon()}
                                placeholder="Código de cupón"
                              />
                              <button
                                type="button"
                                className="button"
                                onClick={applyCoupon}
                              >
                                {couponLoading ? '...' : 'Aplicar cupón'}
                              </button>
                            </>
                          )}
                          {couponError && <p className="coupon-error">{couponError}</p>}
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </form>

              {/* ── Right: Cart totals ── */}
              <div className="cart-collaterals">
                <div className="cart_totals">
                  <div className="cart_totals_summary">
                    <h2>Total del carrito</h2>
                    <table className="shop_table shop_table_responsive">
                      <tbody>
                        <tr className="cart-subtotal">
                          <th>Subtotal</th>
                          <td data-title="Subtotal">
                            <span className="woocommerce-Price-amount amount">
                              <bdi>${formatPrice(cart.summary.subtotal)}</bdi>
                            </span>
                          </td>
                        </tr>
                        {cart.summary.discount > 0 && (
                          <tr className="discount coupon">
                            <th>Descuento</th>
                            <td data-title="Descuento">
                              <span className="woocommerce-Price-amount amount">
                                −<bdi>${formatPrice(cart.summary.discount)}</bdi>
                              </span>
                            </td>
                          </tr>
                        )}
                        {cart.summary.shipping_cost > 0 && (
                          <tr className="shipping">
                            <th>Envío</th>
                            <td data-title="Envío">
                              <span className="woocommerce-Price-amount amount">
                                <bdi>${formatPrice(cart.summary.shipping_cost)}</bdi>
                              </span>
                            </td>
                          </tr>
                        )}
                        <tr className="order-total">
                          <th>Total</th>
                          <td data-title="Total">
                            <strong>
                              <span className="woocommerce-Price-amount amount">
                                <bdi>${formatPrice(cart.summary.total)}</bdi>
                              </span>
                            </strong>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                    <div className="wc-proceed-to-checkout">
                      <Link href="/checkout/shipping" className="checkout-button button alt wc-forward">
                        Ir a finalizar la compra
                      </Link>
                    </div>
                    <fieldset className="single-product-payments payments-color-scheme-inherit">
                      <ul>
                        <li className="single-product-payments-visa">
                          <span className="base-svg-iconset"><svg width="100%" height="100%" viewBox="0 0 750 471" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlnsXlink="http://www.w3.org/1999/xlink" style={{fillRule:'evenodd',clipRule:'evenodd',strokeLinejoin:'round',strokeMiterlimit:2}}><title>Visa Card</title><g><path d="M750,40c0,-22.077 -17.923,-40 -40,-40l-670,0c-22.077,0 -40,17.923 -40,40l0,391c0,22.077 17.923,40 40,40l670,0c22.077,0 40,-17.923 40,-40l0,-391Z" style={{fill:'rgb(14,69,149)'}}></path><path d="M278.197,334.228l33.361,-195.763l53.36,0l-33.385,195.763l-53.336,0Zm246.11,-191.54c-10.572,-3.966 -27.136,-8.222 -47.822,-8.222c-52.725,0 -89.865,26.55 -90.18,64.603c-0.298,28.13 26.513,43.822 46.753,53.186c20.77,9.594 27.752,15.714 27.654,24.283c-0.132,13.121 -16.587,19.116 -31.923,19.116c-21.357,0 -32.703,-2.966 -50.226,-10.276l-6.876,-3.111l-7.49,43.824c12.464,5.464 35.51,10.198 59.438,10.443c56.09,0 92.501,-26.246 92.916,-66.882c0.2,-22.268 -14.016,-39.216 -44.8,-53.188c-18.65,-9.055 -30.072,-15.099 -29.951,-24.268c0,-8.137 9.667,-16.839 30.556,-16.839c17.45,-0.27 30.089,3.535 39.937,7.5l4.781,2.26l7.234,-42.43m137.307,-4.222l-41.231,0c-12.774,0 -22.332,3.487 -27.942,16.234l-79.245,179.404l56.032,0c0,0 9.161,-24.123 11.233,-29.418c6.124,0 60.554,0.084 68.337,0.084c1.596,6.853 6.491,29.334 6.491,29.334l49.513,0l-43.188,-195.638Zm-65.418,126.407c4.413,-11.279 21.26,-54.723 21.26,-54.723c-0.316,0.522 4.38,-11.334 7.075,-18.684l3.606,16.879c0,0 10.217,46.728 12.352,56.528l-44.293,0Zm-363.293,-126.406l-52.24,133.496l-5.567,-27.13c-9.725,-31.273 -40.025,-65.155 -73.898,-82.118l47.766,171.203l56.456,-0.065l84.004,-195.386l-56.521,0Z" style={{fill:'white'}}></path><path d="M131.92,138.465l-86.041,0l-0.681,4.073c66.938,16.204 111.231,55.363 129.618,102.414l-18.71,-89.96c-3.23,-12.395 -12.597,-16.094 -24.186,-16.526" style={{fill:'rgb(242,174,20)'}}></path></g></svg></span>
                        </li>
                        <li className="single-product-payments-mastercard">
                          <span className="base-svg-iconset"><svg width="100%" height="100%" viewBox="0 0 750 471" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlnsXlink="http://www.w3.org/1999/xlink" style={{fillRule:'evenodd',clipRule:'evenodd',strokeLinejoin:'round',strokeMiterlimit:2}}><title>MasterCard</title><g><path d="M750,40c0,-22.077 -17.923,-40 -40,-40l-670,0c-22.077,0 -40,17.923 -40,40l0,391c0,22.077 17.923,40 40,40l670,0c22.077,0 40,-17.923 40,-40l0,-391Z" style={{fill:'rgb(244,244,244)'}}></path><path d="M624.508,278.631l0,-5.52l-1.44,0l-1.658,3.796l-1.657,-3.796l-1.44,0l0,5.52l1.017,0l0,-4.164l1.553,3.59l1.055,0l1.553,-3.6l0,4.174l1.017,0Zm-9.123,0l0,-4.578l1.845,0l0,-0.933l-4.698,0l0,0.933l1.845,0l0,4.578l1.008,0Zm9.412,-82.071c0,85.425 -69.077,154.676 -154.288,154.676c-85.21,0 -154.288,-69.25 -154.288,-154.676c0,-85.426 69.077,-154.677 154.289,-154.677c85.21,0 154.288,69.251 154.288,154.677l-0.001,0Z" style={{fill:'rgb(247,159,26)'}}></path><path d="M434.46,196.56c0,85.425 -69.078,154.676 -154.288,154.676c-85.212,0 -154.288,-69.25 -154.288,-154.676c0,-85.426 69.076,-154.677 154.288,-154.677c85.21,0 154.287,69.251 154.287,154.677l0.001,0Z" style={{fill:'rgb(234,0,27)'}}></path><path d="M375.34,74.797c-35.999,28.317 -59.107,72.318 -59.107,121.748c0,49.43 23.108,93.466 59.108,121.782c35.999,-28.316 59.107,-72.352 59.107,-121.782c0,-49.43 -23.108,-93.431 -59.107,-121.748l-0.001,0Z" style={{fill:'rgb(255,95,1)'}}></path></g></svg></span>
                        </li>
                        <li className="single-product-payments-amex">
                          <span className="base-svg-iconset svg-baseline"><svg width="100%" height="100%" viewBox="0 0 752 471" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlnsXlink="http://www.w3.org/1999/xlink" style={{fillRule:'evenodd',clipRule:'evenodd',strokeLinejoin:'round',strokeMiterlimit:2}}><title>American Express</title><g><path d="M751,40c0,-22.077 -17.923,-40 -40,-40l-670,0c-22.077,0 -40,17.923 -40,40l0,391c0,22.077 17.923,40 40,40l670,0c22.077,0 40,-17.923 40,-40l0,-391Z" style={{fill:'rgb(37,87,214)'}}></path><path d="M1,221.185l36.027,0l8.123,-19.51l18.185,0l8.101,19.51l70.88,0l0,-14.915l6.327,14.98l36.796,0l6.327,-15.202l0,15.138l176.151,0l-0.082,-32.026l3.408,0c2.386,0.083 3.083,0.302 3.083,4.226l0,27.8l91.106,0l0,-7.455c7.349,3.92 18.779,7.455 33.819,7.455l38.328,0l8.203,-19.51l18.185,0l8.021,19.51l73.86,0l0,-18.532l11.186,18.532l59.187,0l0,-122.508l-58.576,0l0,14.468l-8.202,-14.468l-60.105,0l0,14.468l-7.532,-14.468l-81.188,0c-13.59,0 -25.536,1.889 -35.186,7.153l0,-7.153l-56.026,0l0,7.153c-6.14,-5.426 -14.508,-7.153 -23.812,-7.153l-204.686,0l-13.734,31.641l-14.104,-31.641l-64.47,0l0,14.468l-7.083,-14.468l-54.983,0l-25.534,58.246l0,64.261Zm227.399,-17.67l-21.614,0l-0.08,-68.794l-30.573,68.793l-18.512,0l-30.652,-68.854l0,68.854l-42.884,0l-8.101,-19.592l-43.9,0l-8.183,19.592l-22.9,0l37.756,-87.837l31.326,0l35.859,83.164l0,-83.164l34.412,0l27.593,59.587l25.347,-59.587l35.104,0l0,87.837l0.003,0l-0.001,0.001Zm-159.622,-37.823l-14.43,-35.017l-14.35,35.017l28.78,0Zm245.642,37.821l-70.433,0l0,-87.837l70.433,0l0,18.291l-49.348,0l0,15.833l48.165,0l0,18.005l-48.166,0l0,17.542l49.348,0l0,18.166l0.001,0Zm99.256,-64.18c0,14.004 -9.386,21.24 -14.856,23.412c4.613,1.748 8.553,4.838 10.43,7.397c2.976,4.369 3.49,8.271 3.49,16.116l0,17.255l-21.266,0l-0.08,-11.077c0,-5.285 0.508,-12.886 -3.328,-17.112c-3.081,-3.09 -7.777,-3.76 -15.368,-3.76l-22.633,0l0,31.95l-21.084,0l0,-87.838l48.495,0c10.775,0 18.714,0.283 25.53,4.207c6.67,3.924 10.67,9.652 10.67,19.45Zm-26.652,13.042c-2.898,1.752 -6.324,1.81 -10.43,1.81l-25.613,0l0,-19.51l25.962,0c3.674,0 7.508,0.164 9.998,1.584c2.735,1.28 4.427,4.003 4.427,7.765c0,3.84 -1.61,6.929 -4.344,8.351Zm60.466,51.138l-21.513,0l0,-87.837l21.513,0l0,87.837Zm249.74,0l-29.879,0l-39.964,-65.927l0,65.927l-42.94,0l-8.204,-19.592l-43.799,0l-7.96,19.592l-24.673,0c-10.248,0 -23.224,-2.257 -30.572,-9.715c-7.41,-7.458 -11.265,-17.56 -11.265,-33.533c0,-13.027 2.304,-24.936 11.366,-34.347c6.816,-7.01 17.49,-10.242 32.02,-10.242l20.412,0l0,18.821l-19.984,0c-7.694,0 -12.039,1.14 -16.224,5.203c-3.594,3.699 -6.06,10.69 -6.06,19.897c0,9.41 1.878,16.196 5.797,20.628c3.245,3.476 9.144,4.53 14.694,4.53l9.469,0l29.716,-69.076l31.592,0l35.696,83.081l0,-83.08l32.103,0l37.062,61.174l0,-61.174l21.596,0l0,87.834l0.001,-0.001Zm-128.159,-37.82l-14.591,-35.017l-14.51,35.017l29.101,0Z" style={{fill:'white'}}></path></g></svg></span>
                        </li>
                      </ul>
                    </fieldset>
                  </div>
                </div>
              </div>
            </div>
          )}

        </div>
      </div>
    </>
  );
}
