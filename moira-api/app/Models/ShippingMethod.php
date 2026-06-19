<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'is_active',
        'is_simulation',
        'credentials',
        'config',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price'         => 'decimal:2',
            'is_active'     => 'boolean',
            'is_simulation' => 'boolean',
            'credentials'   => 'array',
            'config'        => 'array',
            'sort_order'    => 'integer',
        ];
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return $this->credentials[$key] ?? $default;
    }

    public function configValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
