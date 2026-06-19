<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['name', 'url', 'address', 'zip_code', 'phone', 'email', 'cart_expiration_days'];

    public static function instance(): static
    {
        return static::firstOrCreate([], [
            'name' => '',
            'url' => '',
            'address' => '',
            'zip_code' => '',
            'phone' => '',
            'email' => '',
        ]);
    }

    public static function getValue(string $key, string $default = ''): string
    {
        return (string) (static::instance()->{$key} ?? $default);
    }
}
