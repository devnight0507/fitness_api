<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'assignable_type',
        'assignable_id',
        'assigned_by',
        'assigned_at',
        'expires_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
