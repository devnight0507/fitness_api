<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    protected $fillable = [
        'title',
        'category',
        'location',
        'duration',
        'level',
        'description',
        'thumbnail_path',
        'video_path',
        'youtube_url',
        'video_duration',
        'admin_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'video_duration' => 'integer',
    ];

    protected $appends = ['thumbnail_url', 'video_url', 'exercise_count'];

    // Relationships
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function exercises()
    {
        return $this->hasMany(WorkoutExercise::class)->orderBy('order_index');
    }

    public function viewLogs()
    {
        return $this->hasMany(ViewLog::class);
    }

    public function assignments()
    {
        return $this->morphMany(UserAssignment::class, 'assignable');
    }

    // Attributes
    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail_path
            ? url('storage/' . $this->thumbnail_path)
            : null;
    }

    public function getVideoUrlAttribute()
    {
        return $this->video_path
            ? url('api/videos/' . $this->id . '/stream')
            : null;
    }

    public function getExerciseCountAttribute()
    {
        return $this->exercises()->count();
    }
}
