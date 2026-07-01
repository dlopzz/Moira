<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway_transaction_id')->nullable()->index();
            $table->string('site_transaction_id')->nullable();
            $table->string('card_authorization_code')->nullable();
            $table->string('status');
            $table->integer('amount_cents');
            $table->string('currency', 3)->default('ARS');
            $table->unsignedTinyInteger('installments')->default(1);
            $table->string('bin', 6)->nullable();
            $table->string('card_brand')->nullable();
            $table->json('response_raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
