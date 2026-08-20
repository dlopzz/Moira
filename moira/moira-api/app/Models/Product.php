<?php

namespace App\Models;

use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'weight',
        'length',
        'width',
        'height',
        'images',
        'is_active',
        'product_type',
    ];

    /**
     * El front renderiza la descripción con dangerouslySetInnerHTML, así que
     * aunque el campo del admin sea un textarea plano hay que sanitizarla:
     * un <script> escrito ahí se ejecutaría en la ficha del producto.
     */
    protected function description(): Attribute
    {
        return Attribute::set(fn (?string $value) => HtmlSanitizer::clean($value));
    }

    protected function casts(): array
    {
        return [
            'price'        => 'decimal:2',
            'sale_price'   => 'decimal:2',
            'stock'        => 'integer',
            'weight'       => 'integer',
            'length'       => 'integer',
            'width'        => 'integer',
            'height'       => 'integer',
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
