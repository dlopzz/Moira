<?php

namespace App\Support;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Sanitiza HTML con una allowlist antes de guardarlo.
 *
 * El contenido de las páginas CMS y la descripción de productos se renderiza
 * en el front con dangerouslySetInnerHTML. Filament no sanitiza la salida del
 * RichEditor, así que un usuario del panel con permisos acotados podía inyectar
 * JavaScript que corría en el navegador de todos los visitantes y del resto de
 * los admins (XSS almacenado).
 *
 * Se sanitiza al ESCRIBIR, no al leer: lo que queda en la base ya está limpio,
 * así que cualquier otro consumidor (feed, mail, export) también está cubierto.
 *
 * Allowlist en vez de denylist a propósito: una lista de cosas prohibidas
 * siempre se queda corta.
 */
class HtmlSanitizer
{
    /** Tags permitidos. Todo lo demás se descarta conservando el texto adentro. */
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'div', 'span',
        'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'small', 'mark',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'blockquote', 'pre', 'code',
        'a', 'img',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
    ];

    /** Atributos permitidos por tag. */
    private const ALLOWED_ATTRIBUTES = [
        'a'   => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'td'  => ['colspan', 'rowspan'],
        'th'  => ['colspan', 'rowspan'],
        '*'   => ['class'],
    ];

    /** Esquemas de URL permitidos en href y src. */
    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /** Tags que se eliminan enteros, con su contenido. */
    private const STRIP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'svg', 'math'];

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $doc = new DOMDocument();

        // El wrapper con charset evita que DOMDocument interprete UTF-8 como
        // latin1 y rompa los acentos.
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root__">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root__');

        if (! $root) {
            return '';
        }

        self::removeDangerousNodes($doc);
        self::cleanNode($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    /** Borra script, style, iframe y compañía junto con todo su contenido. */
    private static function removeDangerousNodes(DOMDocument $doc): void
    {
        $xpath = new DOMXPath($doc);
        $query = implode(' | ', array_map(fn ($t) => '//'.$t, self::STRIP_WITH_CONTENT));

        foreach (iterator_to_array($xpath->query($query) ?: []) as $node) {
            $node->parentNode?->removeChild($node);
        }

        // Comentarios: pueden esconder condicionales de IE con script adentro.
        foreach (iterator_to_array($xpath->query('//comment()') ?: []) as $comment) {
            $comment->parentNode?->removeChild($comment);
        }
    }

    private static function cleanNode(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;   // texto: se deja tal cual, saveHTML lo escapa
            }

            $tag = strtolower($child->nodeName);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Tag no permitido: se descarta el tag pero se conserva el texto,
                // para no perder contenido legítimo mal maquetado.
                self::cleanNode($child);
                self::unwrap($child);

                continue;
            }

            self::cleanAttributes($child, $tag);
            self::cleanNode($child);
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = array_merge(
            self::ALLOWED_ATTRIBUTES[$tag] ?? [],
            self::ALLOWED_ATTRIBUTES['*'] ?? []
        );

        /** @var DOMAttr $attr */
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);

            // Cualquier on* (onclick, onerror, onload...) se va sin excepción.
            if (! in_array($name, $allowed, true) || str_starts_with($name, 'on')) {
                $el->removeAttribute($attr->nodeName);

                continue;
            }

            if (in_array($name, ['href', 'src'], true) && ! self::isSafeUrl($attr->nodeValue)) {
                $el->removeAttribute($attr->nodeName);
            }
        }

        // Un link que abre en otra pestaña sin rel expone window.opener.
        if ($tag === 'a' && $el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    }

    /** Bloquea javascript:, data:, vbscript: y similares. */
    private static function isSafeUrl(?string $url): bool
    {
        $url = trim((string) $url);

        if ($url === '') {
            return false;
        }

        // Relativas y anclas son seguras.
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        // Se normaliza antes de mirar el esquema: "java\tscript:" y
        // "JaVaScRiPt:" son el mismo ataque.
        $normalized = strtolower(preg_replace('/[\s\x00-\x1F]/', '', $url) ?? '');

        if (! str_contains($normalized, ':')) {
            return true;   // relativa sin esquema
        }

        $scheme = strstr($normalized, ':', true);

        return in_array($scheme, self::ALLOWED_SCHEMES, true);
    }

    /** Reemplaza un elemento por sus hijos. */
    private static function unwrap(DOMElement $el): void
    {
        $parent = $el->parentNode;

        if (! $parent) {
            return;
        }

        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }

        $parent->removeChild($el);
    }
}
