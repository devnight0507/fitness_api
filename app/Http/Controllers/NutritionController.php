<?php

namespace App\Http\Controllers;

use App\Models\NutritionPlan;
use Illuminate\Http\Request;

class NutritionController extends Controller
{
    /**
     * Get all active nutrition plans (admin/trainer view)
     */
    public function index(Request $request)
    {
        $query = NutritionPlan::with(['meals', 'trainer'])
            ->where('is_active', true);

        // Filter by trainer (for trainers to see their own plans)
        if ($request->user()->role === 'trainer') {
            $query->where('trainer_id', $request->user()->id);
        }

        $plans = $query->orderBy('created_at', 'desc')->get();

        return response()->json($plans);
    }

    /**
     * Get nutrition plans assigned to the authenticated student
     */
    public function assigned(Request $request)
    {
        $user = $request->user();

        // Only students can access this endpoint
        if ($user->role !== 'student') {
            return response()->json([
                'message' => 'Only students can view assigned nutrition plans'
            ], 403);
        }

        $plans = NutritionPlan::with(['meals', 'trainer'])
            ->whereHas('assignments', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($plans);
    }

    /**
     * Get a specific nutrition plan by ID
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $plan = NutritionPlan::with(['meals', 'trainer'])->find($id);

        if (!$plan) {
            return response()->json([
                'message' => 'Nutrition plan not found'
            ], 404);
        }

        // Check access rights
        // Students can only see plans assigned to them
        if ($user->role === 'student') {
            $isAssigned = $plan->assignments()
                ->where('user_id', $user->id)
                ->exists();

            if (!$isAssigned) {
                return response()->json([
                    'message' => 'You do not have access to this nutrition plan'
                ], 403);
            }
        }

        // Trainers can only see their own plans
        if ($user->role === 'trainer' && $plan->trainer_id !== $user->id) {
            return response()->json([
                'message' => 'You do not have access to this nutrition plan'
            ], 403);
        }

        return response()->json($plan);
    }

    /**
     * Create a new nutrition plan (trainer/admin only)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['trainer', 'admin'])) {
            return response()->json([
                'message' => 'Only trainers and admins can create nutrition plans'
            ], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'calories' => 'required|integer',
            'protein' => 'required|integer',
            'carbs' => 'required|integer',
            'fats' => 'required|integer',
            'thumbnail_path' => 'nullable|string',
            'meals' => 'nullable|array',
            'meals.*.type' => 'required|in:Breakfast,Lunch,Dinner,Snack',
            'meals.*.time' => 'nullable|string',
            'meals.*.name' => 'required|string',
            'meals.*.calories' => 'required|integer',
            'meals.*.ingredients' => 'nullable|array',
            'meals.*.instructions' => 'nullable|string',
        ]);

        $plan = NutritionPlan::create([
            'title' => $request->title,
            'description' => $request->description,
            'calories' => $request->calories,
            'protein' => $request->protein,
            'carbs' => $request->carbs,
            'fats' => $request->fats,
            'thumbnail_path' => $request->thumbnail_path,
            'trainer_id' => $user->id,
            'is_active' => true,
        ]);

        // Create meals if provided
        if ($request->has('meals')) {
            foreach ($request->meals as $mealData) {
                $plan->meals()->create([
                    'type' => $mealData['type'],
                    'time' => $mealData['time'] ?? null,
                    'name' => $mealData['name'],
                    'calories' => $mealData['calories'],
                    'ingredients' => $mealData['ingredients'] ?? null,
                    'instructions' => $mealData['instructions'] ?? null,
                ]);
            }
        }

        return response()->json(
            $plan->load('meals'),
            201
        );
    }

    /**
     * Assign nutrition plan to student(s) (trainer/admin only)
     */
    public function assign(Request $request, $id)
    {
        $user = $request->user();

        if (!in_array($user->role, ['trainer', 'admin'])) {
            return response()->json([
                'message' => 'Only trainers and admins can assign nutrition plans'
            ], 403);
        }

        $plan = NutritionPlan::find($id);

        if (!$plan) {
            return response()->json([
                'message' => 'Nutrition plan not found'
            ], 404);
        }

        // Trainers can only assign their own plans
        if ($user->role === 'trainer' && $plan->trainer_id !== $user->id) {
            return response()->json([
                'message' => 'You can only assign your own nutrition plans'
            ], 403);
        }

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $assigned = [];
        foreach ($request->user_ids as $userId) {
            $assignment = $plan->assignments()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'assignable_type' => NutritionPlan::class,
                    'assignable_id' => $plan->id,
                ],
                [
                    'assigned_by' => $user->id,
                ]
            );
            $assigned[] = $assignment;
        }

        return response()->json([
            'message' => 'Nutrition plan assigned successfully',
            'assignments' => $assigned,
        ]);
    }
}
