const BASE = process.env.NEXT_PUBLIC_API_URL!;

function getToken(): string | null {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem('token');
}

type RequestOptions = {
  method?: string;
  body?: unknown;
  token?: string | null;
};

async function request<T>(path: string, opts: RequestOptions = {}): Promise<T> {
  const token = opts.token !== undefined ? opts.token : getToken();
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };
  if (token) headers['Authorization'] = `Bearer ${token}`;

  const res = await fetch(`${BASE}${path}`, {
    method: opts.method ?? 'GET',
    headers,
    body: opts.body !== undefined ? JSON.stringify(opts.body) : undefined,
  });

  if (res.status === 204) return undefined as T;

  const data = await res.json();

  if (!res.ok) {
    const error = new ApiError(data.message ?? 'Error', res.status, data.errors);
    throw error;
  }

  return data as T;
}

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors?: Record<string, string[]>,
  ) {
    super(message);
  }
}

// Auth
export const api = {
  register: (data: { first_name: string; last_name: string; email: string; date_of_birth: string; password: string; password_confirmation: string }) =>
    request<{ data: Customer; token: string }>('/auth/register', { method: 'POST', body: data }),

  login: (data: { email: string; password: string }) =>
    request<{ data: Customer; token: string }>('/auth/login', { method: 'POST', body: data }),

  logout: () =>
    request<void>('/auth/logout', { method: 'POST' }),

  forgotPassword: (email: string) =>
    request<{ message: string }>('/auth/forgot-password', { method: 'POST', body: { email } }),

  resetPassword: (data: { token: string; email: string; password: string; password_confirmation: string }) =>
    request<{ message: string }>('/auth/reset-password', { method: 'POST', body: data }),

  resendVerification: () =>
    request<{ message: string }>('/auth/verify-email/resend', { method: 'POST' }),

  getProfile: () =>
    request<{ data: Customer }>('/profile'),

  updateProfile: (data: { first_name: string; last_name: string; email: string; date_of_birth?: string }) =>
    request<{ data: Customer }>('/profile', { method: 'PUT', body: data }),

  updatePassword: (data: { current_password: string; password: string; password_confirmation: string }) =>
    request<{ message: string }>('/password', { method: 'PUT', body: data }),

  // Catalog
  getCategories: () =>
    request<{ data: Category[] }>('/categories', { token: null }),

  getCategory: (slug: string) =>
    request<{ data: Category; breadcrumb: { name: string; slug: string }[] }>(`/categories/${slug}`, { token: null }),

  getFeaturedProducts: () =>
    request<{ data: Product[] }>('/products/featured', { token: null }),

  getProducts: (params: ProductFilters = {}) => {
    const q = new URLSearchParams();
    if (params.q) q.set('q', params.q);
    if (params.category) q.set('category', params.category);
    if (params.sort) q.set('sort', params.sort);
    if (params.min_price != null) q.set('min_price', String(params.min_price));
    if (params.max_price != null) q.set('max_price', String(params.max_price));
    if (params.per_page) q.set('per_page', String(params.per_page));
    if (params.page) q.set('page', String(params.page));
    return request<{ data: Product[]; meta: PaginationMeta }>(`/products?${q}`, { token: null });
  },

  getProduct: (slug: string) =>
    request<{ data: Product }>(`/products/${slug}`, { token: null }),

  // Site settings — public
  getSiteSettings: () =>
    request<{ data: SiteInfo }>('/settings', { token: null }),

  // CMS Pages — public
  getFooterPages: () =>
    request<{ data: CmsPage[] }>('/pages/footer', { token: null }),

  getCmsPage: (slug: string) =>
    request<{ data: CmsPage }>(`/pages/${slug}`, { token: null }),

  // Reviews — public, token-based
  getReview: (token: string) =>
    request<{ data: { token: string; product: { id: number; name: string; slug: string; image: string | null }; customer_name: string } }>(
      `/reviews/${token}`,
      { token: null },
    ),

  submitReview: (token: string, data: { rating: number; title?: string; body: string }) =>
    request<{ message: string }>(`/reviews/${token}`, { method: 'POST', body: data, token: null }),

  // Reviews
  getMyReviews: () =>
    request<{ data: MyReview[] }>('/reviews'),

  // Orders
  getOrders: (page = 1) =>
    request<{ data: Order[]; meta: PaginationMeta }>(`/orders?page=${page}`),

  getOrder: (id: number) =>
    request<{ data: Order }>(`/orders/${id}`),

  // Wishlist
  getWishlistIds: () =>
    request<{ product_ids: number[] }>('/wishlist/ids'),

  getWishlist: () =>
    request<{ product_ids: number[]; data: Product[] }>('/wishlist'),

  toggleWishlist: (productId: number) =>
    request<{ in_wishlist: boolean; product_id: number }>(`/wishlist/${productId}`, { method: 'POST' }),

  getPaymentConfig: () =>
    request<{ data: PaymentConfig | null }>('/checkout/payment-config', { token: null }),

  processPayment: (data: PaymentTokenData) =>
    request<{ data: Order }>('/checkout/pay', { method: 'POST', body: data }),

  simulatePayment: (result: 'success' | 'fail') =>
    request<{ data: Order }>('/checkout/simulate-pay', { method: 'POST', body: { result } }),

  // Checkout
  getCheckout: () =>
    request<{ cart: Cart; checkout_address: Address | null }>('/checkout'),

  setCheckoutAddress: (address_id: number) =>
    request<{ message: string }>('/checkout/address', { method: 'POST', body: { address_id } }),

  getShippingRates: () =>
    request<{ data: ShippingRate[] }>('/checkout/shipping-rates'),

  selectShipping: (rate: ShippingRate) =>
    request<{ message: string }>('/checkout/shipping', {
      method: 'POST',
      body: { code: rate.code, label: rate.label, price: rate.price },
    }),

  // Cart
  getCart: () =>
    request<{ data: Cart }>('/cart'),

  addToCart: (product_id: number, quantity = 1, variant_id?: number) =>
    request<{ data: Cart }>('/cart/items', { method: 'POST', body: { product_id, quantity, variant_id } }),

  updateCartItem: (id: number, quantity: number) =>
    request<{ data: Cart }>(`/cart/items/${id}`, { method: 'PUT', body: { quantity } }),

  removeCartItem: (id: number) =>
    request<{ data: Cart }>(`/cart/items/${id}`, { method: 'DELETE' }),

  applyCoupon: (code: string) =>
    request<{ data: Cart }>('/cart/coupon', { method: 'POST', body: { code } }),

  removeCoupon: () =>
    request<{ data: Cart }>('/cart/coupon', { method: 'DELETE' }),

  getAddresses: () =>
    request<{ data: Address[] }>('/addresses'),

  createAddress: (data: AddressPayload) =>
    request<{ data: Address }>('/addresses', { method: 'POST', body: data }),

  updateAddress: (id: number, data: AddressPayload) =>
    request<{ data: Address }>(`/addresses/${id}`, { method: 'PUT', body: data }),

  deleteAddress: (id: number) =>
    request<void>(`/addresses/${id}`, { method: 'DELETE' }),

  setDefaultAddress: (id: number) =>
    request<{ data: Address }>(`/addresses/${id}/default`, { method: 'PUT' }),
};

export type Customer = {
  id: number;
  first_name: string;
  last_name: string;
  name: string;
  email: string;
  dob: string | null;
  is_active: boolean;
  created_at: string;
};

export type Address = {
  id: number;
  label: string;
  street: string;
  address_line_2: string | null;
  city: string;
  state: string;
  zip_code: string;
  country: string;
  telephone: string;
  is_default: boolean;
};

export type AddressPayload = Omit<Address, 'id'>;

export type Category = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  image_url: string | null;
  meta_title: string | null;
  meta_description: string | null;
  children?: Category[];
};

export type ProductVariant = {
  id: number;
  sku: string | null;
  price: number | null;
  stock: number;
  attributes: Record<string, string>;
  label: string;
  sort_order: number;
};

export type ProductReview = {
  id: number;
  rating: number;
  title: string | null;
  body: string;
  customer: string;
  submitted_at: string;
};

export type RelatedProduct = {
  id: number;
  name: string;
  slug: string;
  price: number;
  sale_price: number | null;
  image: string | null;
};

export type MyReview = {
  id: number;
  token: string;
  rating: number | null;
  title: string | null;
  body: string | null;
  is_approved: boolean;
  submitted_at: string | null;
  created_at: string;
  product: { id: number; name: string; slug: string; image: string | null } | null;
};

export type Product = {
  id: number;
  name: string;
  slug: string;
  sku: string;
  short_description: string | null;
  description: string | null;
  meta_title: string | null;
  meta_description: string | null;
  price: number;
  sale_price: number | null;
  stock: number;
  images: string[];
  categories: Category[];
  product_type?: 'simple' | 'configurable';
  variants?: ProductVariant[];
  reviews?: ProductReview[];
  rating_average?: number | null;
  rating_count?: number;
  related?: RelatedProduct[];
};

export type PaginationMeta = {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
};

export type CartItem = {
  id: number;
  product_id: number;
  variant_id: number | null;
  variant_label: string | null;
  name: string;
  sku: string | null;
  image: string | null;
  unit_price: number;
  quantity: number;
  subtotal: number;
};

export type ShippingRate = {
  code: string;
  label: string;
  price: number;
  estimated_days: string;
};

export type CartSummary = {
  items_count: number;
  subtotal: number;
  shipping_cost: number;
  discount: number;
  total: number;
};

export type Cart = {
  id: number;
  status: string;
  expires_at: string | null;
  coupon_code: string | null;
  shipping: { code: string | null; label: string | null; price: number };
  items: CartItem[];
  summary: CartSummary;
};

export type OrderItem = {
  id: number;
  product_id: number | null;
  name: string;
  unit_price: number;
  quantity: number;
  subtotal: number;
};

export type Order = {
  id: number;
  number: string;
  status: string;
  shipping_address: {
    label: string;
    street: string;
    address_line_2: string | null;
    city: string;
    state: string;
    zip_code: string;
    country: string;
    telephone: string;
  };
  subtotal: number;
  shipping_cost: number;
  discount: number;
  total: number;
  created_at: string;
  items?: OrderItem[];
};

export type SiteInfo = {
  name: string;
  address: string;
  zip_code: string;
  phone: string;
  email: string;
};

export type CmsPage = {
  id: number;
  title: string;
  subtitle: string | null;
  slug: string;
  content?: string | null;
};

export type ProductFilters = {
  q?: string;
  category?: string;
  sort?: 'price_asc' | 'price_desc' | 'newest' | 'name';
  min_price?: number;
  max_price?: number;
  per_page?: number;
  page?: number;
};

export type PaymentConfig = {
  public_key: string | null;
  is_sandbox: boolean;
  sdk_endpoint: string;
  js_sdk_url: string | null;
};

export type PaymentTokenData = {
  token: string;
  bin: string;
  payment_method_id: number;
  installments: number;
  card_holder_name: string;
  card_holder_doc_type: string;
  card_holder_doc_number: string;
};
