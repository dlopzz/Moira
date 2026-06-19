<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable()->after('customer_id')->constrained('orders')->cascadeOnDelete();
            $table->string('token', 36)->nullable()->unique()->after('order_id');
            $table->timestamp('submitted_at')->nullable()->after('is_approved');

            /* rating can now be null until customer submits the review */
            $table->tinyInteger('rating')->unsigned()->nullable()->change();

            /* drop old unique (product+customer) — a customer can review per order */
            $table->dropUnique('reviews_product_id_customer_id_unique');

            /* one review per product per order */
            $table->unique(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
            $table->dropColumn(['order_id', 'token', 'submitted_at']);
            $table->tinyInteger('rating')->unsigned()->nullable(false)->change();
            $table->dropUnique(['order_id', 'product_id']);
            $table->unique(['product_id', 'customer_id']);
        });
    }
};
