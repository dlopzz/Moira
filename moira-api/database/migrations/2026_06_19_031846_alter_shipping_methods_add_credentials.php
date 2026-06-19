<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->boolean('is_simulation')->default(true)->after('is_active');
            $table->json('credentials')->nullable()->after('is_simulation');
            $table->json('config')->nullable()->after('credentials');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('config');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->dropColumn(['is_simulation', 'credentials', 'config', 'sort_order']);
        });
    }
};
