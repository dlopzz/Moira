<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('social_facebook')->nullable()->after('email');
            $table->string('social_instagram')->nullable()->after('social_facebook');
            $table->string('social_whatsapp')->nullable()->after('social_instagram');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['social_facebook', 'social_instagram', 'social_whatsapp']);
        });
    }
};
