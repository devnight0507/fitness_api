<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'workout_id',
        'duration_watched',
        'completed',
        'watched_at',
    ];

    protected $casts = [
        'duration_watched' => 'integer',
        'completed' => 'boolean',
        'watched_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }
}
