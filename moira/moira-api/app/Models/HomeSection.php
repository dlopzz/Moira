<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    protected $fillable = ['type', 'title', 'settings', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'settings'   => 'array',
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
