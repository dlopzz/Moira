<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('company')->nullable()->after('label');
            $table->string('address_line_2')->nullable()->after('street');
            $table->string('telephone', 30)->after('country');
            $table->renameColumn('province', 'state');
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->renameColumn('state', 'province');
            $table->dropColumn(['company', 'address_line_2', 'telephone']);
        });
    }
};
