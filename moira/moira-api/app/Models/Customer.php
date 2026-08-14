<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'date_of_birth',
        'notes',
        'is_active',
        'google_id',
        'email_verified_at',
        'default_billing_address_id',
        'default_shipping_address_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['name'];

    protected function casts(): array
    {
        return [
            'is_active'         => 'boolean',
            'date_of_birth'     => 'date',
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function suspend(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * is_active solo se chequea al iniciar sesión, así que sin revocar los tokens
     * ya emitidos un cliente suspendido seguiría operando con la sesión que tenía
     * abierta. Va como evento y no dentro de suspend() para cubrir también al
     * admin que baja el toggle "Activo" desde el formulario.
     */
    protected static function booted(): void
    {
        static::updated(function (self $customer): void {
            if ($customer->wasChanged('is_active') && ! $customer->is_active) {
                $customer->tokens()->delete();
            }
        });
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultBillingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'default_billing_address_id');
    }

    public function defaultShippingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'default_shipping_address_id');
    }

    public function wishlist()
    {
        return $this->hasOne(Wishlist::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
