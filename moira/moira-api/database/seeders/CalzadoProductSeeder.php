<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CalzadoProductSeeder extends Seeder
{
    public function run(): void
    {
        $calzado    = Category::where('name', 'Calzado')->firstOrFail();
        $zapatillas = Category::where('name', 'Zapatillas')->first();
        $formales   = Category::where('name', 'Formales')->first();
        $casuales   = Category::where('name', 'Casuales')->first();
        $botas      = Category::where('name', 'Botas')->first();
        $sandalias  = Category::where('name', 'Sandalias')->first();

        $sizesUnisex = [35, 36, 37, 38, 39, 40, 41, 42];
        $sizesWomen  = [35, 36, 37, 38, 39, 40];
        $sizesMen    = [39, 40, 41, 42, 43, 44];

        $products = [
            // ── Zapatillas ──────────────────────────────────────────────────
            [
                'name'              => 'Zapatillas Urbanas Negras',
                'sku'               => 'CAL-ZAP-001',
                'short_description' => 'Zapatillas de corte bajo en negro, ideales para el uso diario.',
                'description'       => 'Confeccionadas en tela resistente con suela de goma antideslizante. Diseño minimalista para cualquier look casual. Interior acolchado para máxima comodidad durante todo el día.',
                'price'             => 52000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $zapatillas?->id],
                'sizes'             => $sizesUnisex,
                'color'             => 'negro',
            ],
            [
                'name'              => 'Zapatillas Running Blancas',
                'sku'               => 'CAL-ZAP-002',
                'short_description' => 'Zapatillas de running con amortiguación reforzada en blanco.',
                'description'       => 'Diseñadas para entrenamiento y running urbano. Suela con tecnología de absorción de impacto, malla transpirable y cordones planos. Perfectas para deportes de alta intensidad.',
                'price'             => 78000.00,
                'sale_price'        => 65000.00,
                'categories'        => [$calzado->id, $zapatillas?->id],
                'sizes'             => $sizesUnisex,
                'color'             => 'blanco',
            ],
            [
                'name'              => 'Zapatillas Deportivas Grises',
                'sku'               => 'CAL-ZAP-003',
                'short_description' => 'Zapatillas deportivas en gris con detalles en blanco.',
                'description'       => 'Estilo deportivo clásico con capellada de cuero sintético y suela vulcanizada. Combinan con ropa casual y deportiva. Disponibles en la clásica gama de grises con rayas blancas.',
                'price'             => 65000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $zapatillas?->id],
                'sizes'             => $sizesUnisex,
                'color'             => 'gris',
            ],
            [
                'name'              => 'Zapatillas Casual Rojas',
                'sku'               => 'CAL-ZAP-004',
                'short_description' => 'Zapatillas de lona en rojo intenso, ligeras y cómodas.',
                'description'       => 'Confeccionadas en lona lavable con puntera de goma reforzada. Su color rojo intenso las convierte en el complemento ideal para outfits minimalistas. Plantilla anatómica extraíble.',
                'price'             => 48000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $zapatillas?->id],
                'sizes'             => $sizesUnisex,
                'color'             => 'rojo',
            ],
            [
                'name'              => 'Zapatillas Training Azules',
                'sku'               => 'CAL-ZAP-005',
                'short_description' => 'Zapatillas para entrenamiento funcional en azul marino.',
                'description'       => 'Diseñadas para gimnasio y entrenamiento cruzado. Base plana que ofrece estabilidad en sentadillas y levantamiento de pesas. Upper de malla doble capa y puntera protegida.',
                'price'             => 71000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $zapatillas?->id],
                'sizes'             => $sizesUnisex,
                'color'             => 'azul',
            ],
            [
                'name'              => 'Zapatillas Trekking Marrones',
                'sku'               => 'CAL-ZAP-006',
                'short_description' => 'Zapatillas de trekking resistentes al agua en marrón.',
                'description'       => 'Capellada de cuero nubuck tratado con membrana impermeable. Suela de goma con taco profundo para agarre en terrenos irregulares. Refuerzo en talón y puntera para máxima protección en senderos.',
                'price'             => 89000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $zapatillas?->id],
                'sizes'             => $sizesUnisex,
                'color'             => 'marrón',
            ],

            // ── Zapatos Formales ─────────────────────────────────────────────
            [
                'name'              => 'Zapatos Oxford Negros',
                'sku'               => 'CAL-FOR-001',
                'short_description' => 'Clásicos zapatos Oxford de cuero negro con suela de cuero.',
                'description'       => 'Confeccionados en cuero vacuno genuino con acabado brillante. Costura blake para mayor flexibilidad. Plantilla de cuero transpirable y suela de cuero con tapa de goma. Ideales para negocios y eventos formales.',
                'price'             => 112000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $formales?->id],
                'sizes'             => $sizesMen,
                'color'             => 'negro',
            ],
            [
                'name'              => 'Zapatos Derby Marrones',
                'sku'               => 'CAL-FOR-002',
                'short_description' => 'Elegantes zapatos Derby en cuero marrón oscuro.',
                'description'       => 'Diseño Derby con cordones y tapa abierta. Cuero vacuno curtido al vegetal con acabado mate. Suela de goma antideslizante. Perfectos para el trabajo de oficina o eventos semi-formales.',
                'price'             => 98000.00,
                'sale_price'        => 85000.00,
                'categories'        => [$calzado->id, $formales?->id],
                'sizes'             => $sizesMen,
                'color'             => 'marrón',
            ],
            [
                'name'              => 'Mocasines Elegantes Negros',
                'sku'               => 'CAL-FOR-003',
                'short_description' => 'Mocasines de cuero negro con detalle de borla dorada.',
                'description'       => 'Mocasines de corte clásico confeccionados en cuero liso negro de alta calidad. Detalle ornamental con borla dorada. Plantilla en cuero acolchada y suela de cuero con tapa de goma reforzada.',
                'price'             => 125000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $formales?->id],
                'sizes'             => $sizesMen,
                'color'             => 'negro',
            ],
            [
                'name'              => 'Zapatos Stiletto Negros',
                'sku'               => 'CAL-FOR-004',
                'short_description' => 'Stilettos de cuero negro con taco aguja de 8 cm.',
                'description'       => 'Zapato de salón con puntera en punta y taco aguja revestido en cuero. Capellada en cuero nacarado negro. Plantilla anatómica acolchada para mayor confort. Broche lateral ajustable. Ideales para eventos y cenas formales.',
                'price'             => 89000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $formales?->id],
                'sizes'             => $sizesWomen,
                'color'             => 'negro',
            ],

            // ── Zapatos Casuales ─────────────────────────────────────────────
            [
                'name'              => 'Mocasines Casuales Beige',
                'sku'               => 'CAL-CAS-001',
                'short_description' => 'Mocasines de cuero en beige para el día a día.',
                'description'       => 'Mocasines de horma redondeada en cuero vacuno color beige. Sin cordones, con elástico lateral oculto para un calce cómodo. Suela de goma liviana. Ideales para combinar con pantalones de gabardina o jeans claros.',
                'price'             => 76000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $casuales?->id],
                'sizes'             => $sizesUnisex,
                'color'             => 'beige',
            ],
            [
                'name'              => 'Sneakers de Cuero Blancos',
                'sku'               => 'CAL-CAS-002',
                'short_description' => 'Sneakers premium de cuero liso blanco, estilo minimalista.',
                'description'       => 'Sneakers de cuero vacuno genuino color blanco óptico. Costuras visibles en contraste. Suela EVA ultraliviana con terminación en goma. Versátiles para cualquier ocasión, desde el trabajo hasta el fin de semana.',
                'price'             => 94000.00,
                'sale_price'        => 80000.00,
                'categories'        => [$calzado->id, $casuales?->id],
                'sizes'             => $sizesUnisex,
                'color'             => 'blanco',
            ],
            [
                'name'              => 'Loafers Casuales Marrones',
                'sku'               => 'CAL-CAS-003',
                'short_description' => 'Loafers de cuero marrón con suela de goma flexible.',
                'description'       => 'Diseño loafer con bit metálico dorado. Cuero vacuno suave en color marrón tabaco. Suela de goma flexible con grip integrado. Forro interno de cuero natural. Perfectos para el uso cotidiano en la ciudad.',
                'price'             => 82000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $casuales?->id],
                'sizes'             => $sizesUnisex,
                'color'             => 'marrón',
            ],
            [
                'name'              => 'Slip-On Rayados Negros',
                'sku'               => 'CAL-CAS-004',
                'short_description' => 'Slip-on de lona a rayas, fácil puesta, cómodos todo el día.',
                'description'       => 'Clásico slip-on de lona a rayas diagonales en negro y blanco. Sin cordones, con elástico en el empeine para calce rápido. Puntera y talón de goma vulcanizada. Interior con plantilla de tela suave. Un clásico renovado.',
                'price'             => 58000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $casuales?->id],
                'sizes'             => $sizesUnisex,
                'color'             => 'negro',
            ],

            // ── Botas ────────────────────────────────────────────────────────
            [
                'name'              => 'Botas de Cuero Negras',
                'sku'               => 'CAL-BOT-001',
                'short_description' => 'Botas de caña alta en cuero negro con cierre lateral.',
                'description'       => 'Botas de caña alta (40 cm) en cuero vacuno pleno curtido. Cierre metálico lateral. Taco cuadrado de 4 cm. Forro interno de cuero natural. Suela de goma con dibujo antideslizante. Perfectas para el otoño-invierno urbano.',
                'price'             => 185000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $botas?->id],
                'sizes'             => $sizesWomen,
                'color'             => 'negro',
            ],
            [
                'name'              => 'Botinetas Con Plataforma',
                'sku'               => 'CAL-BOT-002',
                'short_description' => 'Botinetas de cuero con plataforma de 4 cm y cierre lateral.',
                'description'       => 'Botineta de caña corta en cuero ecológico negro con plataforma y taco combinado de 7 cm total. Cierre metálico dorado al costado. Suela de goma texturada. Forro interior de gamuza sintética suave. Tendencia otoño-invierno.',
                'price'             => 142000.00,
                'sale_price'        => 120000.00,
                'categories'        => [$calzado->id, $botas?->id],
                'sizes'             => $sizesWomen,
                'color'             => 'negro',
            ],
            [
                'name'              => 'Botas de Invierno Marrones',
                'sku'               => 'CAL-BOT-003',
                'short_description' => 'Botas de cuero marrón con forro de sherpa para el invierno.',
                'description'       => 'Botas de caña media (25 cm) en cuero vacuno marrón oscuro. Interior forrado en sherpa artificial para máximo abrigo. Cierre lateral invisible. Suela de goma gruesa con taco bajo de 2 cm. Ideales para los días más fríos.',
                'price'             => 165000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $botas?->id],
                'sizes'             => $sizesWomen,
                'color'             => 'marrón',
            ],

            // ── Sandalias ────────────────────────────────────────────────────
            [
                'name'              => 'Sandalias con Tiras Plateadas',
                'sku'               => 'CAL-SAN-001',
                'short_description' => 'Sandalias de cuero plateado con tiras cruzadas y taco bajo.',
                'description'       => 'Sandalias de tiras cruzadas en cuero metalizado plateado. Cierre de hebilla en el tobillo. Taco bajo de 3 cm en madera revestida. Plantilla forrada en cuero suave. Perfectas para eventos de verano y salidas nocturnas.',
                'price'             => 45000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $sandalias?->id],
                'sizes'             => $sizesWomen,
                'color'             => 'plateado',
            ],
            [
                'name'              => 'Ojotas de Goma Azules',
                'sku'               => 'CAL-SAN-002',
                'short_description' => 'Ojotas de goma resistente en azul, ideales para la playa.',
                'description'       => 'Ojotas de goma EVA ultraliviana en azul turquesa. Suela con textura antideslizante. Correa entre el dedo con refuerzo de nylon. Impermeables y fáciles de lavar. Perfectas para la pileta, la playa o el uso diario en verano.',
                'price'             => 28000.00,
                'sale_price'        => null,
                'categories'        => [$calzado->id, $sandalias?->id],
                'sizes'             => $sizesUnisex,
                'color'             => 'azul',
            ],
            [
                'name'              => 'Sandalias Planas Doradas',
                'sku'               => 'CAL-SAN-003',
                'short_description' => 'Sandalias planas en cuero dorado con pulsera en el tobillo.',
                'description'       => 'Sandalias planas minimalistas en cuero metalizado dorado. Tira fina con pulsera ajustable en el tobillo mediante hebilla dorada. Plantilla de cuero con amortiguación ligera. Combinan con vestidos, faldas y shorts para looks de verano.',
                'price'             => 38000.00,
                'sale_price'        => 32000.00,
                'categories'        => [$calzado->id, $sandalias?->id],
                'sizes'             => $sizesWomen,
                'color'             => 'dorado',
            ],
        ];

        foreach ($products as $data) {
            $categoryIds = array_filter($data['categories']);
            $sizes       = $data['sizes'];
            $color       = $data['color'];

            unset($data['categories'], $data['sizes'], $data['color']);

            $data['slug']             = Str::slug($data['name']);
            $data['meta_title']       = $data['name'];
            $data['meta_description'] = $data['short_description'];
            $data['images']           = [];
            $data['is_active']        = true;
            $data['product_type']     = 'configurable';
            $data['stock']            = 0;

            $product = Product::updateOrCreate(['sku' => $data['sku']], $data);

            $product->categories()->sync($categoryIds);

            $product->variants()->delete();

            foreach ($sizes as $i => $size) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku'        => $data['sku'] . '-' . $size,
                    'price'      => $data['sale_price'] ?? $data['price'],
                    'stock'      => 10,
                    'attributes' => ['talle' => (string) $size, 'color' => $color],
                    'sort_order' => $i,
                    'is_active'  => true,
                ]);
            }
        }

        $this->command->info('✓ 20 productos de Calzado creados/actualizados.');
    }
}
