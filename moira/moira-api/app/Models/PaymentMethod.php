<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'is_sandbox',
        'credentials',
        'config',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'is_sandbox'  => 'boolean',
            'credentials' => 'array',
            'config'      => 'array',
            'sort_order'  => 'integer',
        ];
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return $this->credentials[$key] ?? $default;
    }

    public function activePublicKey(): ?string
    {
        $key = $this->is_sandbox ? 'sandbox_public_key' : 'production_public_key';

        return $this->credential($key);
    }

    public function activePrivateKey(): ?string
    {
        $key = $this->is_sandbox ? 'sandbox_private_key' : 'production_private_key';

        return $this->credential($key);
    }

    public function configValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
