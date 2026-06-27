<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('guest_email')->nullable()->after('guest_token');
            $table->string('shipping_firstname')->nullable()->after('guest_email');
            $table->string('shipping_lastname')->nullable()->after('shipping_firstname');
            $table->string('shipping_telephone')->nullable()->after('shipping_lastname');
            $table->string('shipping_street')->nullable()->after('shipping_telephone');
            $table->string('shipping_city')->nullable()->after('shipping_street');
            $table->string('shipping_state')->nullable()->after('shipping_city');
            $table->string('shipping_zip_code')->nullable()->after('shipping_state');
            $table->string('shipping_country')->nullable()->default('AR')->after('shipping_zip_code');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'guest_email',
                'shipping_firstname', 'shipping_lastname', 'shipping_telephone',
                'shipping_street', 'shipping_city', 'shipping_state',
                'shipping_zip_code', 'shipping_country',
            ]);
        });
    }
};
