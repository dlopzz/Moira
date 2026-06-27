<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('default_billing_address_id')->nullable()->after('email_verified_at');
            $table->unsignedBigInteger('default_shipping_address_id')->nullable()->after('default_billing_address_id');

            $table->foreign('default_billing_address_id')
                ->references('id')->on('customer_addresses')
                ->nullOnDelete();

            $table->foreign('default_shipping_address_id')
                ->references('id')->on('customer_addresses')
                ->nullOnDelete();
        });

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['default_billing_address_id']);
            $table->dropForeign(['default_shipping_address_id']);
            $table->dropColumn(['default_billing_address_id', 'default_shipping_address_id']);
        });

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('telephone');
        });
    }
};
