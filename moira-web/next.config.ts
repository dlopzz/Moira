import type { NextConfig } from "next";

// El SDK de pago se carga desde una URL guardada en la DB
// (paymentConfig.js_sdk_url): hoy es ventasonline.payway.com.ar, y se whitelistea
// también decidir.com porque es el dominio histórico del mismo proveedor. Si esa
// URL cambia por otro host, hay que sumarlo acá o el checkout deja de cargar.
// online-metrix.net es el fingerprinting antifraude que dispara el propio SDK.
// La API vive en otro origen (localhost:8080 en dev, api.moirabikinis.ar en
// prod). Sin declararlo, connect-src bloquea todos los fetch del sitio.
const apiOrigin = (() => {
  try {
    return new URL(process.env.NEXT_PUBLIC_API_URL ?? '').origin;
  } catch {
    return '';
  }
})();

const csp = [
  "default-src 'self'",
  "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.google.com https://www.gstatic.com https://*.decidir.com https://*.payway.com.ar https://*.online-metrix.net",
  "style-src 'self' 'unsafe-inline'",
  `img-src 'self' data: blob: https: ${apiOrigin}`,
  "font-src 'self' data:",
  `connect-src 'self' ${apiOrigin} https://www.google.com https://*.decidir.com https://*.payway.com.ar https://*.online-metrix.net`,
  "frame-src https://www.google.com https://*.decidir.com https://*.payway.com.ar https://*.online-metrix.net",
  "object-src 'none'",
  "base-uri 'self'",
  "form-action 'self'",
  "frame-ancestors 'none'",
].join('; ');

const securityHeaders = [
  { key: 'Strict-Transport-Security', value: 'max-age=31536000; includeSubDomains; preload' },
  { key: 'X-Frame-Options', value: 'DENY' },
  { key: 'X-Content-Type-Options', value: 'nosniff' },
  { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
  { key: 'Permissions-Policy', value: 'camera=(), microphone=(), geolocation=(), interest-cohort=()' },
  { key: 'Cross-Origin-Opener-Policy', value: 'same-origin' },
  { key: 'Content-Security-Policy', value: csp },
];

const nextConfig: NextConfig = {
  output: "standalone",
  poweredByHeader: false,
  images: {
    unoptimized: true,
  },
  async headers() {
    return [{ source: '/:path*', headers: securityHeaders }];
  },
};

export default nextConfig;
