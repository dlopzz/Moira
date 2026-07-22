<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El buscador del catálogo filtra con `ILIKE '%term%'` sobre products.name,
 * products.sku y products.short_description. El comodín inicial impide usar un
 * índice btree, así que cada búsqueda es un seq scan de la tabla entera. Los
 * índices GIN con gin_trgm_ops (extensión pg_trgm) sí aceleran el patrón
 * `%term%`, convirtiéndolo en index scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        DB::statement('CREATE INDEX IF NOT EXISTS products_name_trgm_idx ON products USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_sku_trgm_idx ON products USING gin (sku gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_short_description_trgm_idx ON products USING gin (short_description gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_name_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS products_sku_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS products_short_description_trgm_idx');
    }
};
