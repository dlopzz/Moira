<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'stock',
        'attributes',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price'      => 'decimal:2',
            'stock'      => 'integer',
            'attributes' => 'array',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Effective price: own price if set, otherwise parent product price. */
    public function effectivePrice(): float
    {
        return (float) ($this->price ?? $this->product->sale_price ?? $this->product->price);
    }

    /** Human-readable label derived from attributes: "Color: Rojo / Talle: M" */
    public function label(): string
    {
        return collect($this->attributes)
            ->map(fn ($value, $key) => "{$key}: {$value}")
            ->join(' / ');
    }
}
