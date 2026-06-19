<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete()->after('notes');
            $table->string('coupon_code')->nullable()->after('coupon_id');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('coupon_code');
            $table->timestamp('expires_at')->nullable()->after('discount_amount');
        });

        DB::table('quotes')->where('status', 'draft')->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'coupon_code', 'discount_amount', 'expires_at']);
        });
        DB::table('quotes')->where('status', 'active')->update(['status' => 'draft']);
    }
};
