<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    protected $fillable = ['email', 'token', 'unsubscribed_at'];

    protected $casts = ['unsubscribed_at' => 'datetime'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $subscriber): void {
            $subscriber->token ??= (string) Str::uuid();
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unsubscribed_at');
    }
}
