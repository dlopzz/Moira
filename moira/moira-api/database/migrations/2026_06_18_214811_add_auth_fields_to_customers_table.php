<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('password');
            $table->timestamp('email_verified_at')->nullable()->after('remember_token');
        });

        /* Customers created before auth was added won't be able to login until they set a password via reset */
        DB::statement("UPDATE customers SET password = '' WHERE password IS NULL");

        Schema::table('customers', function (Blueprint $table): void {
            $table->string('password')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['password', 'remember_token', 'email_verified_at']);
        });
    }
};
