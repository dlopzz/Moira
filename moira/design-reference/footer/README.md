# Footer — Design Reference

Referencia completa para replicar el footer del tema WordPress Fabzy/Avanam en moira-web.

## Referencia visual

Ver `screenshot.png` en esta carpeta.

Estructura visual:
- **Sección superior** — 4 columnas sobre fondo claro, separadas por líneas verticales
- **Barra inferior** — fondo sage verde (`#bec2b4`), copyright izquierda, logos de pago derecha

---

## Componente actual en moira-web

`/src/components/Footer.tsx` — ya existe y tiene la estructura correcta.

---

## HTML original de WordPress

Ver `footer.html` — HTML renderizado real del sitio.

Estructura de las 4 columnas:
| Columna | Título | Contenido |
|---------|--------|-----------|
| 1 | _(sin título)_ | Logo `assets/logo.png` (156×35px) + párrafo descriptivo |
| 2 | INFORMATION | Contact Us, About Us |
| 3 | OUR SERVICES | Return Policy, Terms Of Use, Security, Privacy, Sitemap |
| 4 | CONTACT US | Dirección (ícono 📍), Teléfono (ícono ☎), Email (ícono ✉) |

---

## CSS original del tema

Ver `footer-source.css` — CSS completo del footer extraído del tema.

### Variables CSS clave

```css
/* Colores */
--global-palette1: #bec2b4;   /* sage green — fondo barra inferior y accent */
--global-palette2: #000000;   /* negro */
--global-palette3: #325b0e;   /* verde oscuro — títulos de columna */
--global-palette4: #878787;   /* gris — texto de links y párrafos */
--global-palette7: #f5f5f5;   /* fondo sección superior */
--global-palette9: #ffffff;   /* blanco */

/* Tipografía */
--global-body-font-family: Montserrat, sans-serif;
--global-heading-font-family: Montserrat;
```

### CSS de sección superior (4 columnas)

```css
.site-middle-footer-wrap .site-footer-row-container-inner {
  color: #878787;           /* --palette4 */
  border-top: 1px solid #ebebeb;
}
.site-middle-footer-inner-wrap {
  min-height: 320px;
  padding-top: 0;
  padding-bottom: 0;
  column-gap: 80px;         /* desktop */
  row-gap: 80px;
}
.site-middle-footer-inner-wrap .widget-title {
  font-weight: 500;
  font-size: 18px;
  color: #325b0e;           /* --palette3 */
  margin-bottom: 30px;
}
/* Divider vertical entre columnas */
.site-middle-footer-inner-wrap .site-footer-section:not(:last-child)::after {
  border-right: 1px solid #ebebeb;
  right: -40px;             /* centro del gap de 80px */
}
/* tablet */
@media (max-width: 1024px) {
  column-gap: 30px;
  padding-top: 40px;
  padding-bottom: 30px;
}
```

### CSS de barra inferior

```css
.site-bottom-footer-wrap .site-footer-row-container-inner {
  background: #bec2b4;      /* --palette1: sage green */
  color: #ffffff;
}
.site-bottom-footer-inner-wrap {
  padding-top: 20px;
  padding-bottom: 90px;     /* espacio para scroll-to-top button */
  column-gap: 30px;
}
```

### Links del footer

```css
/* Color normal */
.site-middle-footer-wrap a { color: #878787; }
/* Hover */
.site-middle-footer-wrap a:hover { color: #bec2b4; }
/* Bottom bar */
.site-bottom-footer-wrap a { color: #ffffff; }
```

---

## Assets

| Archivo | Descripción |
|---------|-------------|
| `assets/logo.png` | Logo Fabzy — 156×35px, fondo transparente |
| `assets/payment.png` | Logos de métodos de pago — 282×25px |

---

## Variables CSS que usa Footer.tsx en moira-web

El Footer.tsx existente usa estas variables (definidas en el layout/global CSS de moira-web):

| Variable usada | Equivalente en WP theme |
|----------------|-------------------------|
| `var(--surface)` | fondo sección superior (light/blanco) |
| `var(--accent)` | `#bec2b4` — sage green (palette1) |
| `var(--text)` | `#878787` — gris (palette4) |
| `var(--black)` | `#000000` — negro (palette2) |
| `var(--title)` | `#325b0e` — verde oscuro (palette3) |

Asegurate de que estas variables estén definidas en el global CSS de moira-web.

---

## Notas de implementación

1. **Tipografía**: Montserrat debe estar importada en `layout.tsx` (Google Fonts)
2. **Logo**: el footer usa el logo de texto "FABZY" — en moira-web usar texto "MOIRA" con el mismo estilo (weight 300 + 800)
3. **Íconos de contacto**: el WP usa íconos de fuente del plugin (ver `footer.html`). En moira-web reemplazar con SVGs o emoji equivalentes
4. **Imagen de pagos**: copiar `assets/payment.png` a `/public/payment.png` en moira-web
5. **padding-bottom: 90px** en la barra inferior — es intencional para dejar espacio al botón "scroll to top"
