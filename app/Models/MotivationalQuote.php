<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivationalQuote extends Model
{
    protected $fillable = [
        'quote',
        'author',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
