<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'payment_method_id',
        'shipping_method_id',
        'shipping_method_label',
        'shipping_address',
        'status',
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
            'subtotal'         => 'decimal:2',
            'shipping_cost'    => 'decimal:2',
            'discount'         => 'decimal:2',
            'total'            => 'decimal:2',
        ];
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
}
