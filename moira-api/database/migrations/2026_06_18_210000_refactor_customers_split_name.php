<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('first_name')->after('id')->default('');
            $table->string('last_name')->after('first_name')->default('');
            $table->date('date_of_birth')->nullable()->after('email');
            $table->text('notes')->nullable()->after('date_of_birth');
        });

        DB::table('customers')->get()->each(function ($c) {
            $parts = explode(' ', $c->name, 2);
            DB::table('customers')->where('id', $c->id)->update([
                'first_name' => $parts[0] ?? '',
                'last_name'  => $parts[1] ?? '',
            ]);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['name', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('name')->after('id')->default('');
            $table->string('phone', 30)->nullable();
        });

        DB::table('customers')->get()->each(function ($c) {
            DB::table('customers')->where('id', $c->id)->update([
                'name' => trim("{$c->first_name} {$c->last_name}"),
            ]);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'date_of_birth', 'notes']);
        });
    }
};
