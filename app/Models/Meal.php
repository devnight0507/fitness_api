<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    protected $fillable = [
        'nutrition_plan_id',
        'type',
        'time',
        'name',
        'calories',
        'ingredients',
        'instructions',
        'order_index',
    ];

    protected $casts = [
        'calories' => 'integer',
        'ingredients' => 'array',
        'order_index' => 'integer',
    ];

    // Relationships
    public function nutritionPlan()
    {
        return $this->belongsTo(NutritionPlan::class);
    }
}
