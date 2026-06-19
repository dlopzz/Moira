<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'meta_title',
        'meta_description',
        'price',
        'sale_price',
        'stock',
        'images',
        'is_active',
        'product_type',
    ];

    protected function casts(): array
    {
        return [
            'price'        => 'decimal:2',
            'sale_price'   => 'decimal:2',
            'stock'        => 'integer',
            'images'       => 'array',
            'is_active'    => 'boolean',
            'product_type' => 'string',
        ];
    }

    public function isConfigurable(): bool
    {
        return $this->product_type === 'configurable';
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists()
    {
        return $this->belongsToMany(Wishlist::class, 'product_wishlist')->withTimestamps();
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupon_product');
    }

    public function relatedProducts()
    {
        return $this->belongsToMany(Product::class, 'product_related', 'product_id', 'related_product_id');
    }
}
