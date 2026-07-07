# Header — Design Reference

Referencia completa para replicar el header del tema WordPress Fabzy/Avanam en moira-web.

## Referencia visual

Ver `screenshot.png` en esta carpeta (1440×170px).

Estructura visual (3 filas):
- **Row 1 — Top bar**: fondo sage (`#bec2b4`), iconos sociales izquierda, texto promo derecha
- **Row 2 — Main nav**: fondo blanco (`#ffffff`), logo izquierda, categorías centro, Account+Cart derecha
- **Row 3 — Bottom bar**: fondo blanco, Customer Care + buscador alineados a la derecha
- **Sticky**: al hacer scroll, Row 1 y Row 3 desaparecen, queda solo Row 2

---

## Componente actual en moira-web

`/src/components/Header.tsx` — ya existe y tiene la estructura correcta.

---

## HTML original de WordPress

Ver `header.html` — HTML renderizado real del sitio.

Clases clave:
```
#masthead.site-header
  #main-header.site-header-wrap
    .site-top-header-wrap           → Row 1: top bar (sage)
    .site-main-header-wrap          → Row 2: logo + nav + icons
    .site-bottom-header-wrap        → Row 3: contact + search
```

---

## CSS original del tema

Ver `header-source.css` — CSS completo del header extraído del tema (33KB).

### Variables CSS clave

```css
/* Colores */
--global-palette1: #bec2b4;   /* sage — top bar bg, badge, hover */
--global-palette2: #000000;   /* negro */
--global-palette3: #325b0e;   /* verde oscuro — logo, nav, iconos derecha */
--global-palette4: #878787;   /* gris — texto secundario */
--global-palette6: #a1a1a1;   /* gris claro — labels "Get All Option", "Items" */
--global-palette9: #ffffff;   /* blanco — bg nav, mobile menu */

/* Tipografía */
--global-body-font-family: Montserrat, sans-serif;
--global-heading-font-family: Montserrat;
```

---

## Row 1: Top bar

```css
.site-top-header-wrap .site-header-row-container-inner {
  background: #bec2b4;          /* --palette1: sage */
  color: #ffffff;
}
.site-top-header-inner-wrap {
  min-height: 40px;
}

/* Social icons */
.header-social-wrap {
  margin: 0px 0px 0px -15px;   /* alinea flush izquierda del container */
}
.header-social-inner-wrap {
  font-size: 15px;
  gap: 0.3em;
}
.social-button {
  color: #ffffff;
  padding: 4px 6px;
}

/* Texto promo (derecha) */
.header-html {
  font-size: 12px;
  letter-spacing: 1px;
  color: #ffffff;
  margin: 0px -15px 0px 0px;   /* flush derecha del container */
}
```

---

## Row 2: Main nav (logo + categorías + iconos)

```css
.site-main-header-wrap .site-header-row-container-inner {
  background: #ffffff;          /* --palette9: blanco */
}
.site-main-header-inner-wrap {
  min-height: 60px;
}

/* Logo — .site-branding */
.site-branding {
  padding: 0px 75px 0px 30px;
}
/* Logo texto: "FABZY" = weight 300 + 800 */
/* En moira-web: "MOI" weight 300 + "RA" weight 800 */

/* Nav items */
.header-navigation .nav-2 ul a,
.header-navigation .nav-2 ul > li > a {
  color: #325b0e;               /* --palette3 */
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  padding: 18px 22px;           /* top/bottom 18px, left/right 22px */
  /* Note: en moira-web: paddingLeft/Right = calc(44px / 2) = 22px */
}
.header-navigation .nav-2 ul > li > a:hover {
  color: #bec2b4;               /* --palette1: sage */
}

/* Account widget — .header-account-wrap */
.header-account-wrap .header-account-button {
  padding: 0px 10px 0px 0px;
}
.header-account-wrap .header-account-label {
  font-weight: 500;
  font-size: 12px;
  letter-spacing: 1px;
  color: #325b0e;               /* --palette3 */
}
.header-account-wrap .header-account-sub-label {
  font-weight: 400;
  font-size: 9px;
  color: #a1a1a1;               /* --palette6 */
}

/* Cart widget — .header-cart-wrap */
.header-cart-wrap .header-cart-button {
  padding: 0px 0px 0px 10px;
}
/* Cart badge */
.header-cart-wrap .cart-count {
  background: #bec2b4;          /* --palette1: sage */
  color: #ffffff;
  width: 18px;
  height: 18px;
  font-size: 9px;
}
/* Cart label */
.header-cart-wrap .header-cart-label {
  font-weight: 500;
  font-size: 12px;
  letter-spacing: 1px;
  color: #325b0e;
}
.header-cart-wrap .header-cart-sub-label {
  font-weight: 400;
  font-size: 9px;
  color: #a1a1a1;
}
```

---

## Row 3: Bottom bar (Customer Care + Search)

```css
.site-bottom-header-wrap .site-header-row-container-inner {
  background: #ffffff;          /* --palette9: blanco */
  border-top: 1px solid var(--global-gray-400);  /* #ebebeb */
}
.site-bottom-header-inner-wrap {
  min-height: 60px;
}

/* Contact (Customer Care) */
.header-contact-wrap {
  margin: 0px 20px 0px 0px;
}
.header-contact-item {
  font-weight: 500;
  font-size: 12px;
  color: #325b0e;               /* --palette3 */
}
.header-contact-wrap .base-svg-iconset {
  font-size: 15px;              /* icon size */
}

/* Search */
.header-search-advanced {
  margin: 0px -15px 0px 10px;  /* flush derecha del container */
}
.header-search-advanced form {
  max-width: 100%;
  width: 275px;
  background: #f5f5f5;         /* --palette7 */
}
.header-search-advanced input {
  font-size: 12px;
  text-transform: uppercase;
  padding: 10px 10px 10px 15px;
  height: 40px;
}
.header-search-advanced .search-submit {
  width: 44px;
  height: 40px;
  color: #325b0e;
}
.header-search-advanced .search-submit:hover {
  color: #bec2b4;
}
```

---

## Dropdown menu (mega menu / submenu)

El dropdown aparece al hover sobre items con hijos.

```html
<!-- WP genera: -->
<ul class="sub-menu">
  <li class="menu-item"><a href="...">Child Category</a></li>
  ...
</ul>
```

```css
/* Dropdown container */
.header-navigation .nav-2 ul ul {
  background: #ffffff;
  box-shadow: 0px 2px 13px 0px rgba(0,0,0,0.1);
  min-width: 240px;
  padding: 8px 0;               /* py-2 */
  position: absolute;
  top: 100%;
  left: 0;
  z-index: 50;
}

/* Dropdown link */
.header-navigation .nav-2 ul ul li a {
  color: #878787;               /* --palette4 */
  font-size: 14px;
  font-weight: 400;
  padding: 0.3em 0.5em;
  line-height: 1.6;
  text-decoration: none;
  text-transform: capitalize;   /* no uppercase — distinto del nav principal */
  width: 240px;
  display: block;
}
.header-navigation .nav-2 ul ul li a:hover {
  color: #bec2b4;               /* --palette1: sage */
}
```

En moira-web (`Header.tsx`), el dropdown está implementado con:
- `onMouseEnter`/`onMouseLeave` en el `<div>` padre para mostrar/ocultar
- Estado `catOpen: number | null` con el ID de la categoría activa
- `position: absolute; top: 100%; left: 0; z-index: 50`
- `minWidth: 240px; boxShadow: 0px 2px 13px 0px rgba(0,0,0,0.1)`
- `backgroundColor: 'var(--surface)'` = `#ffffff`

---

## Sticky behavior

```css
/* Al hacer scroll > 80px: */
/* - Se oculta Row 1 (top bar sage) y Row 3 (contact + search) */
/* - Row 2 (main nav) recibe box-shadow */
.site-header.scrolled .site-top-header-wrap,
.site-header.scrolled .site-bottom-header-wrap {
  display: none;
}
/* shadow en sticky */
.site-header.scrolled {
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}
```

En moira-web: estado `sticky: boolean`, listener `scroll > 80px`.
- `!sticky &&` envuelve Row 1 y Row 3
- `boxShadow: sticky ? '0 2px 12px rgba(0,0,0,0.08)' : 'none'` en el wrapper blanco

---

## Mobile menu

```css
/* Hamburger — visible en < lg (1024px) */
/* Menú desplegable debajo del header */
.lg:hidden

/* Items de primer nivel */
color: #325b0e;
font-size: 12px;
font-weight: 700;
text-transform: uppercase;
letter-spacing: 0.07em;
border-bottom: 1px solid var(--bg-subtle);

/* Sub-items (hijos de categoría) */
color: #878787;
font-size: 14px;
padding: 10px 32px;             /* px-8 py-2.5 */
```

---

## Estructura de filas en moira-web (Header.tsx)

| Elemento | Background | Min-height | Visible sticky |
|----------|-----------|------------|---------------|
| Row 1 — top bar | `var(--accent)` = `#bec2b4` | 40px | ❌ oculto |
| Row 2 — main nav | `var(--surface)` = `#ffffff` | 60px | ✅ siempre |
| Row 3 — search | `var(--surface)` = `#ffffff` | 60px | ❌ oculto |
| Mobile menu | `var(--surface)` = `#ffffff` | — | — |

---

## Variables CSS que usa Header.tsx en moira-web

| Variable usada | Valor | Equivalente en WP |
|----------------|-------|-------------------|
| `var(--accent)` | `#bec2b4` | `--global-palette1` |
| `var(--title)` | `#325b0e` | `--global-palette3` |
| `var(--text)` | `#878787` | `--global-palette4` |
| `var(--text-subtle)` | `#a1a1a1` | `--global-palette6` |
| `var(--surface)` | `#ffffff` | `--global-palette9` |
| `var(--bg-subtle)` | `#fafafa` | body background |
| `var(--border)` | `#e9e9e9` | `--global-gray-400` |

---

## Notas de implementación

1. **Logo**: texto "MOIRA" — "MOI" font-weight 300 + "RA" font-weight 800 (igual que "FABZY")
2. **Nav padding**: `padding: 18px 22px` por item (en moira-web: `calc(44px / 2)` = 22px por lado)
3. **Logo padding**: `padding: 0px 75px 0px 30px` — espacio grande a la derecha antes de la nav
4. **Search bg**: `#f5f5f5` (WP usa `--palette7`) → en moira-web: `var(--bg-subtle)` = `#fafafa` (diferencia mínima)
5. **Container**: `maxWidth: 1200px; padding: 0 0.9375rem`
6. **position: sticky; top: 0; z-index: 100** en el `<header>` raíz
