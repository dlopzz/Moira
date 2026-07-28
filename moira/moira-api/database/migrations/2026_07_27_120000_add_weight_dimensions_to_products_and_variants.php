<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Peso (gramos) y dimensiones (cm) para cotizar envío por peso facturable.
     * En variantes, null = hereda del producto.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('weight')->nullable()->after('stock');
            $table->unsignedSmallInteger('length')->nullable()->after('weight');
            $table->unsignedSmallInteger('width')->nullable()->after('length');
            $table->unsignedSmallInteger('height')->nullable()->after('width');
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->unsignedInteger('weight')->nullable()->after('stock');
            $table->unsignedSmallInteger('length')->nullable()->after('weight');
            $table->unsignedSmallInteger('width')->nullable()->after('length');
            $table->unsignedSmallInteger('height')->nullable()->after('width');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['weight', 'length', 'width', 'height']);
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn(['weight', 'length', 'width', 'height']);
        });
    }
};
