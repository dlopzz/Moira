<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Órdenes de mentira para poder ver los gráficos del dashboard con datos.
 * Todas quedan marcadas con notes = self::MARK para poder borrarlas después
 * (ver el método clear()). NO correr en producción.
 */
class DemoOrdersSeeder extends Seeder
{
    public const MARK = '[demo]';

    /** Peso relativo de cada mes: verano argentino vende mucho más que invierno. */
    private const MONTH_WEIGHT = [
        1 => 2.4, 2 => 2.0, 3 => 1.3, 4 => 0.8, 5 => 0.6, 6 => 0.5,
        7 => 0.5, 8 => 0.6, 9 => 0.9, 10 => 1.3, 11 => 1.8, 12 => 2.6,
    ];

    public function run(): void
    {
        $this->clear();

        $customers = Customer::pluck('id')->all();
        $variants  = ProductVariant::with('product')->inRandomOrder()->limit(60)->get();

        // payment_method_id y shipping_method_id son FK: sin filas reales el
        // primer insert aborta con violación de clave foránea.
        $paymentMethods  = DB::table('payment_methods')->pluck('id')->all();
        $shippingMethods = DB::table('shipping_methods')->pluck('id')->all();

        if (empty($customers) || $variants->isEmpty()) {
            $this->command->error('Faltan customers o variants: sembrá el catálogo primero.');

            return;
        }

        if (empty($paymentMethods) || empty($shippingMethods)) {
            $this->command->error('Faltan métodos de pago o de envío: cargá al menos uno de cada uno.');

            return;
        }

        $start = Carbon::today()->subMonths(12)->startOfMonth();
        $end   = Carbon::today();
        $created = 0;

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            for ($i = 0; $i < $this->ordersFor($day); $i++) {
                $this->makeOrder($day, $customers, $variants, $paymentMethods, $shippingMethods);
                $created++;
            }
        }

        $this->makeReturns();

        $this->command->info("Órdenes demo creadas: {$created}");
    }

    /** Borra todo lo que haya sembrado una corrida anterior. */
    public function clear(): void
    {
        $ids = Order::withTrashed()->where('notes', self::MARK)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        OrderReturn::whereIn('order_id', $ids)->delete();
        DB::table('order_items')->whereIn('order_id', $ids)->delete();
        Order::withTrashed()->whereIn('id', $ids)->forceDelete();
    }

    /** Cantidad de órdenes de un día: estacionalidad por mes + empuje de fin de semana. */
    private function ordersFor(Carbon $day): int
    {
        $base = self::MONTH_WEIGHT[$day->month] * 2.2;

        if ($day->isWeekend()) {
            $base *= 1.6;
        }

        // Crecimiento suave del negocio a lo largo del año. El orden importa:
        // diffInDays() devuelve un valor con signo, y al revés la curva decrece.
        $base *= 1 + (Carbon::today()->subMonths(12)->diffInDays($day) / 900);

        return max(0, (int) round($base + random_int(-2, 3)));
    }

    private function makeOrder(
        Carbon $day,
        array $customers,
        $variants,
        array $paymentMethods,
        array $shippingMethods,
    ): void {
        $at = $day->copy()->setTime(random_int(8, 23), random_int(0, 59));

        $order = Order::create([
            'customer_id'           => $customers[array_rand($customers)],
            'payment_method_id'     => $paymentMethods[array_rand($paymentMethods)],
            'shipping_method_id'    => $shippingMethods[array_rand($shippingMethods)],
            'shipping_method_label' => 'Envío a domicilio',
            'shipping_address'      => [
                'label' => 'Casa', 'street' => 'Av. Siempre Viva 742', 'city' => 'CABA',
                'state' => 'CABA', 'zip_code' => '1000', 'country' => 'AR', 'telephone' => '1140000000',
            ],
            'status'   => $this->statusFor($day),
            'subtotal' => 0,
            'total'    => 0,
            'notes'    => self::MARK,
        ]);

        $subtotal = 0;

        foreach (range(1, random_int(1, 3)) as $ignored) {
            $variant  = $variants->random();
            $qty      = random_int(1, 2);
            $price    = (float) ($variant->price ?: $variant->product->price ?? 25000);
            $lineSub  = $price * $qty;
            $subtotal += $lineSub;

            DB::table('order_items')->insert([
                'order_id'     => $order->id,
                'product_id'   => $variant->product_id,
                'variant_id'   => $variant->id,
                'product_name' => $variant->product->name ?? 'Producto',
                'variant_label' => $variant->label(),
                'unit_price'   => $price,
                'quantity'     => $qty,
                'subtotal'     => $lineSub,
                'created_at'   => $at,
                'updated_at'   => $at,
            ]);
        }

        $shipping = random_int(0, 4) === 0 ? 0 : 8500;
        $discount = random_int(0, 6) === 0 ? round($subtotal * 0.1, 2) : 0;

        // update() directo: el modelo no debe disparar transiciones de stock acá.
        DB::table('orders')->where('id', $order->id)->update([
            'subtotal'      => $subtotal,
            'shipping_cost' => $shipping,
            'discount'      => $discount,
            'total'         => $subtotal + $shipping - $discount,
            'shipped_at'    => in_array($order->status, ['shipped', 'delivered'], true)
                ? $at->copy()->addDays(random_int(1, 3))
                : null,
            'created_at'    => $at,
            'updated_at'    => $at,
        ]);
    }

    /**
     * Las órdenes viejas ya terminaron su ciclo; las de los últimos días
     * todavía están arrancando. Sin esto el gráfico de estados queda plano.
     */
    private function statusFor(Carbon $day): string
    {
        $age = $day->diffInDays(Carbon::today());

        $pool = match (true) {
            $age <= 3   => ['pending', 'pending', 'paid', 'paid', 'paid', 'processing'],
            $age <= 10  => ['paid', 'processing', 'processing', 'shipped', 'shipped', 'cancelled'],
            $age <= 25  => ['shipped', 'delivered', 'delivered', 'delivered', 'cancelled', 'refunded'],
            default     => array_merge(
                array_fill(0, 14, 'delivered'),
                ['shipped', 'cancelled', 'refunded', 'paid'],
            ),
        };

        return $pool[array_rand($pool)];
    }

    /** Devoluciones sobre órdenes entregadas/reembolsadas, para el widget de devoluciones. */
    private function makeReturns(): void
    {
        $orders = Order::where('notes', self::MARK)
            ->whereIn('status', ['delivered', 'refunded'])
            ->inRandomOrder()
            ->limit(70)
            ->get();

        $statuses = ['requested', 'approved', 'received', 'refunded', 'rejected', 'closed'];

        foreach ($orders as $order) {
            $at = $order->created_at->copy()->addDays(random_int(3, 20));

            if ($at->isFuture()) {
                continue;
            }

            $status = $statuses[array_rand($statuses)];

            OrderReturn::create([
                'order_id'    => $order->id,
                'status'      => $status,
                'reason'      => OrderReturn::REASONS[array_rand(OrderReturn::REASONS)],
                'description' => 'Devolución de prueba generada por DemoOrdersSeeder.',
                'telephone'   => '1140000000',
                'restock'     => (bool) random_int(0, 1),
                'refunded_at' => $status === 'refunded' ? $at : null,
                'created_at'  => $at,
                'updated_at'  => $at,
            ]);
        }
    }
}
