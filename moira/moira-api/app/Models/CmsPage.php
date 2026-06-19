<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'slug',
        'content',
        'is_active',
        'show_in_footer',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'show_in_footer' => 'boolean',
            'sort_order'     => 'integer',
        ];
    }
}
