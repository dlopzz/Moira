<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table): void {
            $table->boolean('show_in_footer')->default(false)->after('is_active');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('show_in_footer');
        });
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table): void {
            $table->dropColumn(['show_in_footer', 'sort_order']);
        });
    }
};
