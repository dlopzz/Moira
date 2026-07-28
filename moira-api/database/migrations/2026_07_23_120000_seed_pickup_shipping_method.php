<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure the free store-pickup shipping method always exists so the admin
     * only has to fill the address/schedule and toggle it active.
     */
    public function up(): void
    {
        if (DB::table('shipping_methods')->where('code', 'retiro_sucursal')->exists()) {
            return;
        }

        DB::table('shipping_methods')->insert([
            'name'          => 'Retiro en sucursal',
            'code'          => 'retiro_sucursal',
            'description'   => 'Retirá tu pedido en el local sin costo de envío.',
            'price'         => 0,
            'is_active'     => false,
            'is_simulation' => false,
            'config'        => json_encode([
                'pickup_address'  => '',
                'pickup_schedule' => '',
            ]),
            'sort_order'    => 1,
            'updated_at'    => now(),
            'created_at'    => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('shipping_methods')->where('code', 'retiro_sucursal')->delete();
    }
};
