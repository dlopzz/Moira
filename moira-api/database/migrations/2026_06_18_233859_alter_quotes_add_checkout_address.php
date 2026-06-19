<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->foreignId('checkout_address_id')
                ->nullable()
                ->constrained('customer_addresses')
                ->nullOnDelete()
                ->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropForeign(['checkout_address_id']);
            $table->dropColumn('checkout_address_id');
        });
    }
};
