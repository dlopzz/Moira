<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedBigInteger('billing_address_id')->nullable()->after('checkout_address_id');
            $table->foreign('billing_address_id')->references('id')->on('customer_addresses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['billing_address_id']);
            $table->dropColumn('billing_address_id');
        });
    }
};
