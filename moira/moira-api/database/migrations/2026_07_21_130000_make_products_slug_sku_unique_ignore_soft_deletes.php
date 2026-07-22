<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_slug_unique');
            $table->dropUnique('products_sku_unique');
        });

        // Unicidad solo entre productos vivos: un slug/sku de un producto
        // soft-deleted queda libre para reutilizar.
        DB::statement('CREATE UNIQUE INDEX products_slug_unique ON products (slug) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX products_sku_unique ON products (sku) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX products_slug_unique');
        DB::statement('DROP INDEX products_sku_unique');

        Schema::table('products', function (Blueprint $table) {
            $table->unique('slug', 'products_slug_unique');
            $table->unique('sku', 'products_sku_unique');
        });
    }
};
