<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CustomerAddress;

class Quote extends Model
{
    const STATUS_ACTIVE = 'active';

    const STATUS_EXPIRED = 'expired';

    const STATUS_CONVERTED = 'converted';

    protected $fillable = [
        'customer_id',
        'status',
        'notes',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'expires_at',
        'checkout_address_id',
        'shipping_method_code',
        'shipping_method_label',
        'shipping_cost',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'shipping_cost'   => 'decimal:2',
            'expires_at'      => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function checkoutAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'checkout_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public static function getActiveForCustomer(Customer $customer): static
    {
        $expirationDays = (int) SiteSetting::getValue('cart_expiration_days', '30');

        $active = static::where('customer_id', $customer->id)
            ->where('status', self::STATUS_ACTIVE)
            ->first();

        if ($active) {
            if ($active->expires_at && $active->expires_at->isPast()) {
                $active->update(['status' => self::STATUS_EXPIRED]);

                return static::reactivateOrCreate($customer, $expirationDays);
            }

            return $active;
        }

        return static::reactivateOrCreate($customer, $expirationDays);
    }

    private static function reactivateOrCreate(Customer $customer, int $expirationDays): static
    {
        $expired = static::where('customer_id', $customer->id)
            ->where('status', self::STATUS_EXPIRED)
            ->latest()
            ->first();

        if ($expired) {
            $expired->load('items.product');
            $expired->refreshPrices();
            $expired->update([
                'status' => self::STATUS_ACTIVE,
                'expires_at' => now()->addDays($expirationDays),
            ]);

            return $expired;
        }

        return static::create([
            'customer_id' => $customer->id,
            'status' => self::STATUS_ACTIVE,
            'expires_at' => now()->addDays($expirationDays),
        ]);
    }

    public function refreshExpiry(): void
    {
        $days = (int) SiteSetting::getValue('cart_expiration_days', '30');
        $this->update(['expires_at' => now()->addDays($days)]);
    }

    public function refreshPrices(): void
    {
        foreach ($this->items as $item) {
            if (! $item->product) {
                continue;
            }
            $price = (float) ($item->product->sale_price ?? $item->product->price);
            $item->update([
                'unit_price' => $price,
                'subtotal' => $price * $item->quantity,
            ]);
        }
    }

    public function getSubtotal(): float
    {
        return (float) $this->items->sum(fn (QuoteItem $item) => $item->unit_price * $item->quantity);
    }

    public function getTotal(): float
    {
        return max(0.0, $this->getSubtotal() + (float) $this->shipping_cost - (float) $this->discount_amount);
    }

    public function recalculateDiscount(): void
    {
        if (! $this->coupon_id) {
            return;
        }

        $coupon = $this->coupon;
        if (! $coupon) {
            $this->update(['discount_amount' => 0, 'coupon_id' => null, 'coupon_code' => null]);

            return;
        }

        $subtotal = $this->getSubtotal();
        $discount = $coupon->type === 'percentage'
            ? $subtotal * ((float) $coupon->value / 100)
            : min((float) $coupon->value, $subtotal);

        $this->update(['discount_amount' => round($discount, 2)]);
    }
}
