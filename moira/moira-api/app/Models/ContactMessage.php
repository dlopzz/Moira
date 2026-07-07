<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['name', 'last_name', 'email', 'message'];

    protected $casts = ['is_read' => 'boolean'];
}
