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
        'weight',
        'length',
        'width',
        'height',
        'attributes',
        'image',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'weight' => 'integer',
            'length' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'attributes' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
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

    /** Effective weight (grams): own if set, otherwise parent product weight. */
    public function effectiveWeight(): ?int
    {
        return $this->weight ?? $this->product->weight;
    }

    /**
     * Effective dimensions (cm): own if set, otherwise parent product's.
     *
     * @return array{length: int|null, width: int|null, height: int|null}
     */
    public function effectiveDimensions(): array
    {
        return [
            'length' => $this->length ?? $this->product->length,
            'width'  => $this->width ?? $this->product->width,
            'height' => $this->height ?? $this->product->height,
        ];
    }

    /** Human-readable label derived from attributes: "Color: Rojo / Talle: M" */
    public function label(): string
    {
        return collect($this->getAttribute('attributes') ?? [])
            ->map(fn ($value, $key) => ucfirst($key).': '.$value)
            ->join(' / ');
    }
}
