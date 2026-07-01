<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['name', 'url', 'address', 'zip_code', 'phone', 'email', 'cart_expiration_days', 'recaptcha_enabled', 'cookie_notice_enabled', 'cookie_notice_text'];

    protected $casts = [
        'recaptcha_enabled'     => 'boolean',
        'cookie_notice_enabled' => 'boolean',
    ];

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
