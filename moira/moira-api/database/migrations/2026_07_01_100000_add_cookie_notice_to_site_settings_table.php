<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->boolean('cookie_notice_enabled')->default(false)->after('recaptcha_enabled');
            $table->text('cookie_notice_text')->nullable()->after('cookie_notice_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['cookie_notice_enabled', 'cookie_notice_text']);
        });
    }
};
