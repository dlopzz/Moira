<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table): void {
            $table->string('product_sku')->nullable()->after('product_name');
            $table->string('product_image')->nullable()->after('product_sku');
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table): void {
            $table->dropColumn(['product_sku', 'product_image']);
        });
    }
};
