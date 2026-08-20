<?php

namespace App\Models;

use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * El front renderiza este campo con dangerouslySetInnerHTML, y Filament no
     * sanitiza la salida del RichEditor. Se limpia al guardar, así lo que queda
     * en la base ya está sano para cualquier consumidor.
     */
    protected function content(): Attribute
    {
        return Attribute::set(fn (?string $value) => HtmlSanitizer::clean($value));
    }

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'show_in_footer' => 'boolean',
            'sort_order'     => 'integer',
        ];
    }
}
