<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class OrderReturn extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_LABELS = [
        'requested' => 'Solicitada',
        'approved' => 'Aprobada',
        'rejected' => 'Rechazada',
        'received' => 'Recibida',
        'refunded' => 'Reembolsada',
        'closed' => 'Cerrada',
    ];

    public const STATUS_COLORS = [
        'requested' => 'warning',
        'approved' => 'info',
        'rejected' => 'danger',
        'received' => 'primary',
        'refunded' => 'success',
        'closed' => 'gray',
    ];

    public const REASONS = [
        'Talla incorrecta',
        'Producto con falla',
        'No es lo que esperaba',
        'Cambio de opinión',
        'Otro',
    ];

    public const RETURN_WINDOW_DAYS = 30;

    private const ALLOWED_TRANSITIONS = [
        'requested' => ['approved', 'rejected'],
        'approved' => ['received', 'rejected'],
        'received' => ['refunded', 'closed'],
        'rejected' => [],
        'refunded' => [],
        'closed' => [],
    ];

    protected $fillable = [
        'order_id',
        'status',
        'reason',
        'description',
        'telephone',
        'restock',
        'restocked_at',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'restock' => 'boolean',
            'restocked_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class);
    }

    /**
     * Opera sobre una copia bloqueada de la fila y refresca $this al final, así
     * el caller siempre ve el status/restocked_at/refunded_at ya actualizados sin
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
            // Bloquear la fila: sin esto, dos requests concurrentes transicionando la
            // misma devolución a "received" (doble click, dos pestañas admin) podrían
            // leer restocked_at=null cada una y restockear el stock dos veces.
            $locked = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === $newStatus) {
                return;
            }

            $allowed = self::ALLOWED_TRANSITIONS[$locked->status] ?? [];

            abort_unless(in_array($newStatus, $allowed, true), 422, "No se puede pasar de {$locked->status} a {$newStatus}.");

            if ($newStatus === self::STATUS_RECEIVED && $locked->restock && ! $locked->restocked_at) {
                $locked->restockItems();
                $locked->restocked_at = now();
            }

            if ($newStatus === self::STATUS_REFUNDED) {
                $locked->refunded_at = now();
            }

            $locked->status = $newStatus;
            $locked->save();
        });

        $this->refresh();
    }

    protected function restockItems(): void
    {
        foreach ($this->items()->with('orderItem')->get() as $returnItem) {
            $returnItem->orderItem?->incrementStock($returnItem->quantity);
        }
    }

    /**
     * Reglas de elegibilidad para pedir una devolución sobre esta orden: única
     * fuente de verdad, usada tanto al crear la devolución (createWithItems)
     * como al listar ítems elegibles (OrderReturnController::eligibleItems) y
     * al exponer el campo `return_eligible` en OrderResource (evita que el
     * frontend reimplemente esta regla con sus propias constantes).
     */
    public static function assertOrderIsReturnable(Order $order): void
    {
        abort_unless(self::isWithinReturnWindow($order), 422, 'La orden supera los '.self::RETURN_WINDOW_DAYS.' días de comprada.');
        abort_unless(self::isStatusReturnable($order), 422, 'La orden ya está cancelada/reembolsada.');
    }

    public static function isOrderReturnable(Order $order): bool
    {
        return self::isWithinReturnWindow($order) && self::isStatusReturnable($order);
    }

    public static function isWithinReturnWindow(Order $order): bool
    {
        return ! $order->created_at->lt(now()->subDays(self::RETURN_WINDOW_DAYS));
    }

    public static function isStatusReturnable(Order $order): bool
    {
        return ! in_array($order->status, Order::RESTOCKING_STATUSES, true);
    }

    /**
     * @param  array<int, array{order_item_id: int, quantity: int}>  $rows
     */
    public static function createWithItems(
        Order $order,
        array $rows,
        string $reason,
        ?string $description,
        string $telephone,
        bool $restock = true,
    ): self {
        return DB::transaction(function () use ($order, $rows, $reason, $description, $telephone, $restock): self {
            // Bloquear la orden: Order::transitionStatus() también la bloquea antes
            // de cancelar/reembolsar, así ambas operaciones se serializan en vez de
            // competir por leer un status que todavía no committeó la otra.
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            self::assertOrderIsReturnable($lockedOrder);

            $return = self::create([
                'order_id' => $order->id,
                'status' => self::STATUS_REQUESTED,
                'reason' => $reason,
                'description' => $description,
                'telephone' => $telephone,
                'restock' => $restock,
            ]);

            foreach ($rows as $row) {
                $quantity = (int) ($row['quantity'] ?? 0);

                if ($quantity < 1) {
                    continue;
                }

                $orderItem = OrderItem::where('order_id', $order->id)
                    ->whereKey($row['order_item_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $remaining = $orderItem->remainingReturnableQuantity();

                abort_if($quantity > $remaining, 422, "La cantidad a devolver de \"{$orderItem->product_name}\" excede lo disponible ({$remaining}).");

                $return->items()->create([
                    'order_item_id' => $orderItem->id,
                    'quantity' => $quantity,
                ]);
            }

            abort_if($return->items()->doesntExist(), 422, 'Debés seleccionar al menos un producto a devolver.');

            return $return;
        });
    }
}
