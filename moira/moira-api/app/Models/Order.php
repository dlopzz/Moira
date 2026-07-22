<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use SoftDeletes;

    public const RESTOCKING_STATUSES = ['cancelled', 'refunded'];

    public const STATUS_LABELS = [
        'pending' => 'Pendiente',
        'paid' => 'Pagado',
        'processing' => 'En proceso',
        'shipped' => 'Enviado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
        'refunded' => 'Reembolsado',
    ];

    public const STATUS_COLORS = [
        'pending' => 'warning',
        'paid' => 'info',
        'processing' => 'info',
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger',
        'refunded' => 'danger',
    ];

    /** cancelled/refunded son terminales: una vez ahí, ya se restockeó y no hay vuelta atrás. */
    private const ALLOWED_TRANSITIONS = [
        'pending' => ['paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'],
        'paid' => ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'],
        'processing' => ['pending', 'paid', 'shipped', 'delivered', 'cancelled', 'refunded'],
        'shipped' => ['pending', 'paid', 'processing', 'delivered', 'cancelled', 'refunded'],
        'delivered' => ['pending', 'paid', 'processing', 'shipped', 'cancelled', 'refunded'],
        'cancelled' => [],
        'refunded' => [],
    ];

    protected $fillable = [
        'customer_id',
        'payment_method_id',
        'shipping_method_id',
        'shipping_method_label',
        'tracking_number',
        'shipping_address',
        'status',
        'restocked_at',
        'shipped_at',
        'subtotal',
        'shipping_cost',
        'discount',
        'coupon_code',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'restocked_at' => 'datetime',
            'shipped_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * Opera sobre una copia bloqueada de la fila y refresca $this al final, así
     * el caller siempre ve el status/restocked_at/shipped_at ya actualizados sin
     * tener que acordarse de llamar refresh() por su cuenta.
     */
    public function transitionStatus(string $newStatus): void
    {
        // Fast path solo de performance: evita abrir transacción/lock para un
        // no-op. La corrección de la transición en sí la garantiza el chequeo
        // equivalente de más abajo, ya con la fila bloqueada.
        if ($this->status === $newStatus) {
            return;
        }

        DB::transaction(function () use ($newStatus): void {
            // Bloquear la fila: OrderReturn::createWithItems() también bloquea esta
            // misma orden antes de crear una devolución, así ambas operaciones se
            // serializan en vez de competir (sin esto, una devolución podría crearse
            // entre el chequeo de abajo y el commit, y terminar restockeando dos veces).
            $locked = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === $newStatus) {
                return;
            }

            // Re-validar la transición contra el status recién bloqueado, no el que
            // teníamos antes de bloquear: otra request pudo haber cambiado el status
            // mientras esperábamos el lock, y una transición válida "de lejos" puede
            // ya no serlo desde el status real y actual de la fila.
            $allowed = self::ALLOWED_TRANSITIONS[$locked->status] ?? [];

            abort_unless(in_array($newStatus, $allowed, true), 422, "No se puede pasar de {$locked->status} a {$newStatus}.");

            $entering = in_array($newStatus, self::RESTOCKING_STATUSES, true);

            if ($entering && ! $locked->restocked_at) {
                // No permitir cancelar/reembolsar mientras haya devoluciones sin resolver:
                // restockItems() calcula cuánto restockear en base al resultado final de
                // cada devolución (rechazada = no restockea, resto = ya restockeado o lo
                // hará su propio flujo). Con una devolución "requested"/"approved" abierta
                // ese cálculo sería una suposición que puede quedar mal si luego se rechaza.
                abort_if(
                    $locked->returns()->whereIn('status', [OrderReturn::STATUS_REQUESTED, OrderReturn::STATUS_APPROVED])->exists(),
                    422,
                    'Hay devoluciones pendientes de resolver antes de cancelar/reembolsar esta orden.'
                );

                $locked->restockItems();
                $locked->restocked_at = now();
            }

            $locked->status = $newStatus;

            if ($newStatus === 'shipped' && ! $locked->shipped_at) {
                $locked->shipped_at = now();
            }

            $locked->save();
        });

        $this->refresh();
    }

    protected function restockItems(): void
    {
        $returnedByItem = OrderItem::returnedQuantities($this->items->pluck('id'));

        foreach ($this->items as $item) {
            // Excluir cantidad reclamada por devoluciones activas (no rechazadas): esa
            // porción se restockea por su propio flujo (OrderReturn::restockItems) cuando
            // corresponda, evitando doble restock o restockear ítems no revendibles.
            $quantity = max(0, $item->quantity - (int) ($returnedByItem[$item->id] ?? 0));

            if ($quantity > 0) {
                $item->incrementStock($quantity);
            }
        }
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transaction()
    {
        return $this->hasOne(PaymentTransaction::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }
}
