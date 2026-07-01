<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'payment_method_id',
        'gateway_transaction_id',
        'site_transaction_id',
        'card_authorization_code',
        'status',
        'amount_cents',
        'currency',
        'installments',
        'bin',
        'card_brand',
        'response_raw',
    ];

    protected function casts(): array
    {
        return [
            'response_raw' => 'array',
            'installments' => 'integer',
            'amount_cents' => 'integer',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
