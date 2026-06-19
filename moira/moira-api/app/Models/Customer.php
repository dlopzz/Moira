<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
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
