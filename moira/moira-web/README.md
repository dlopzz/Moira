# Moira — Headless E-commerce

Plataforma de e-commerce headless compuesta por una API REST en Laravel y un frontend en Next.js.

## Stack Tecnológico

### Frontend — `moira-web`
| Tecnología | Versión | Uso |
|---|---|---|
| [Next.js](https://nextjs.org) | 16.x | Framework React (App Router) |
| [React](https://react.dev) | 19.x | UI |
| [TypeScript](https://www.typescriptlang.org) | 5.x | Tipado estático |
| CSS (custom) | — | Design system propio, paleta tipo Avanam/WooCommerce |

### Backend — `moira-api`
| Tecnología | Versión | Uso |
|---|---|---|
| [Laravel](https://laravel.com) | 13.x | API REST + panel admin |
| [PHP](https://www.php.net) | 8.3 | Lenguaje del servidor |
| [Laravel Sanctum](https://laravel.com/docs/sanctum) | 4.x | Autenticación por token |
| [Filament](https://filamentphp.com) | 3.x | Panel de administración |
| [Laravel Sail](https://laravel.com/docs/sail) | — | Entorno Docker local |
| MySQL | 8.x | Base de datos |

## Arquitectura

```
moira/
├── moira-api/     # Laravel 13 — API REST + Filament admin
└── moira-web/     # Next.js 16 — Storefront headless
```

El frontend consume la API en `http://moura.test:8080/api/v1`. La autenticación usa tokens Bearer (Laravel Sanctum). Los guests se identifican con un `X-Guest-Token` almacenado en `localStorage`.

## Funcionalidades

- Catálogo de productos con variantes (configurable / simple)
- Carrito para usuarios autenticados y guests
- Checkout Magento-style: email primero, detección de cuenta existente, login inline o continuar como invitado
- Direcciones con defaults independientes de facturación y envío
- Cupones de descuento
- Wishlist
- Órdenes y perfil de cliente
- Reviews de productos por token
- Panel admin con Filament (categorías, productos, órdenes, reviews)

## Desarrollo local

### API
```bash
cd moira-api
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
```

### Frontend
```bash
cd moira-web
npm install
npm run dev
```

Frontend disponible en `http://localhost:3000`.
