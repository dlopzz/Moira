<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'applies_to',
        'min_order_amount',
        'max_uses',
        'uses_per_customer',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value'            => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'starts_at'        => 'datetime',
            'expires_at'       => 'datetime',
            'is_active'        => 'boolean',
        ];
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'coupon_product');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasReachedMaxUses(): bool
    {
        return $this->max_uses && $this->used_count >= $this->max_uses;
    }
}
