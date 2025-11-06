<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutritionPlan extends Model
{
    protected $fillable = [
        'title',
        'description',
        'calories',
        'protein',
        'carbs',
        'fats',
        'thumbnail_path',
        'trainer_id',
        'is_active',
    ];

    protected $casts = [
        'calories' => 'integer',
        'protein' => 'integer',
        'carbs' => 'integer',
        'fats' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['thumbnail_url', 'meal_count'];

    // Relationships
    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function meals()
    {
        return $this->hasMany(Meal::class)->orderBy('order_index');
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

    public function getMealCountAttribute()
    {
        return $this->meals()->count();
    }
}
