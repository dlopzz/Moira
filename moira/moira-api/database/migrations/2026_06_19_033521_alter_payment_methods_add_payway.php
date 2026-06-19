<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->boolean('is_simulation')->default(true)->after('is_active');
            $table->boolean('is_sandbox')->default(true)->after('is_simulation');
            $table->json('credentials')->nullable()->after('is_sandbox');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('credentials');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->dropColumn(['is_simulation', 'is_sandbox', 'credentials', 'sort_order']);
        });
    }
};
