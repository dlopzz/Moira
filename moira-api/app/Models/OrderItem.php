<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'variant_label',
        'product_name',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class);
    }

    public function returnedQuantity(): int
    {
        return (int) $this->returnItems()
            ->whereHas('orderReturn', fn ($q) => $q->where('status', '!=', OrderReturn::STATUS_REJECTED))
            ->sum('quantity');
    }

    public function remainingReturnableQuantity(): int
    {
        return max(0, $this->quantity - $this->returnedQuantity());
    }

    /**
     * Cantidad ya reclamada por devoluciones activas (no rechazadas) por cada
     * order_item_id dado, en una sola query. Usado por Order::restockItems() y
     * OrderReturnController::eligibleItems() para evitar el N+1 de llamar
     * returnedQuantity() por ítem.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $itemIds
     * @return \Illuminate\Support\Collection<int, int>
     */
    public static function returnedQuantities(Collection $itemIds): Collection
    {
        return OrderReturnItem::query()
            ->whereIn('order_item_id', $itemIds)
            ->whereHas('orderReturn', fn ($q) => $q->where('status', '!=', OrderReturn::STATUS_REJECTED))
            ->selectRaw('order_item_id, sum(quantity) as total')
            ->groupBy('order_item_id')
            ->pluck('total', 'order_item_id');
    }

    public function incrementStock(int $quantity): void
    {
        if ($this->variant_id) {
            ProductVariant::where('id', $this->variant_id)->increment('stock', $quantity);
        } elseif ($this->product_id) {
            Product::where('id', $this->product_id)->increment('stock', $quantity);
        }
    }
}
