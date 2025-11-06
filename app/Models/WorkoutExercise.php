<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutExercise extends Model
{
    protected $fillable = [
        'workout_id',
        'name',
        'sets',
        'reps',
        'rest',
        'order_index',
    ];

    protected $casts = [
        'sets' => 'integer',
        'rest' => 'integer',
        'order_index' => 'integer',
    ];

    // Relationships
    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }
}
