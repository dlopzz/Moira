<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    const STATUS_ACTIVE     = 'active';
    const STATUS_PROCESSING = 'processing';
    const STATUS_EXPIRED    = 'expired';
    const STATUS_CONVERTED  = 'converted';

    protected $fillable = [
        'customer_id',
        'guest_token',
        'guest_email',
        'shipping_firstname',
        'shipping_lastname',
        'shipping_telephone',
        'shipping_street',
        'shipping_city',
        'shipping_state',
        'shipping_zip_code',
        'shipping_country',
        'status',
        'notes',
        'order_notes',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'expires_at',
        'checkout_address_id',
        'billing_address_id',
        'shipping_method_code',
        'shipping_method_label',
        'shipping_cost',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount'     => 'decimal:2',
            'shipping_cost'       => 'decimal:2',
            'expires_at'          => 'datetime',
            'checkout_address_id' => 'integer',
            'billing_address_id'  => 'integer',
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

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'billing_address_id');
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

    public static function getActiveForGuest(string $guestToken): static
    {
        $expirationDays = (int) SiteSetting::getValue('cart_expiration_days', '30');

        // guest_token tiene un índice único (sin scope de status). Por eso no se
        // puede SELECT-solo-activos y luego INSERT: si ya existe una fila con ese
        // token en otro estado (expired/converted/processing) el insert viola el
        // unique. Se reutiliza siempre la misma fila.
        $quote = static::where('guest_token', $guestToken)->first();

        if ($quote) {
            $stillActive = $quote->status === self::STATUS_ACTIVE
                && (! $quote->expires_at || ! $quote->expires_at->isPast());

            if ($stillActive) {
                return $quote;
            }

            // Si venía de una orden ya generada o de un checkout en curso, se
            // arranca un carrito vacío; si solo expiró, se conservan los ítems.
            if (in_array($quote->status, [self::STATUS_CONVERTED, self::STATUS_PROCESSING], true)) {
                $quote->items()->delete();
            }

            $quote->update([
                'status' => self::STATUS_ACTIVE,
                'expires_at' => now()->addDays($expirationDays),
            ]);
            $quote->load('items.product');
            $quote->refreshPrices();

            return $quote;
        }

        return static::create([
            'guest_token' => $guestToken,
            'status' => self::STATUS_ACTIVE,
            'expires_at' => now()->addDays($expirationDays),
        ]);
    }

    /**
     * Variante de solo lectura de getActiveForCustomer(): nunca inserta una fila.
     * Devuelve null si el cliente todavía no tiene ningún carrito, así un GET no
     * deja un carrito vacío en la tabla.
     */
    public static function findActiveForCustomer(Customer $customer): ?static
    {
        $exists = static::where('customer_id', $customer->id)
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_EXPIRED])
            ->exists();

        return $exists ? static::getActiveForCustomer($customer) : null;
    }

    /**
     * Variante de solo lectura de getActiveForGuest(): nunca inserta una fila.
     * Esto es lo que evita que cada visitante nuevo del sitio genere un carrito
     * vacío — el front pide GET /cart al montar, mucho antes de agregar nada.
     */
    public static function findActiveForGuest(string $guestToken): ?static
    {
        $quote = static::where('guest_token', $guestToken)->first();

        if (! $quote) {
            return null;
        }

        // Ya convertido en orden o con un pago en curso: para una lectura equivale
        // a un carrito vacío, porque getActiveForGuest le vaciaría los ítems al
        // reutilizar la fila. Devolver null evita hacer esa escritura acá.
        if (in_array($quote->status, [self::STATUS_CONVERTED, self::STATUS_PROCESSING], true)) {
            return null;
        }

        // La fila ya existe, así que reutilizarla (reactivándola si expiró) no
        // agrega basura a la tabla.
        return static::getActiveForGuest($guestToken);
    }

    public static function guestCartHasItems(string $guestToken): bool
    {
        return static::where('guest_token', $guestToken)
            ->where('status', self::STATUS_ACTIVE)
            ->whereHas('items')
            ->exists();
    }

    /**
     * Carrito vacío sin persistir, para responder lecturas cuando no hay ninguna
     * fila que devolver. Se le fija la relación items para que CartResource no
     * dispare una query con quote_id null.
     */
    public static function emptyCart(): static
    {
        $quote = new static(['status' => self::STATUS_ACTIVE]);
        $quote->setRelation('items', new EloquentCollection());

        return $quote;
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
