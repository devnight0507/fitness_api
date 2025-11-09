<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use Illuminate\Http\Request;

class WorkoutController extends Controller
{
    /**
     * Get all active workouts (admin view)
     */
    public function index(Request $request)
    {
        $query = Workout::with(['exercises', 'trainer'])
            ->where('is_active', true);

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by level
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        $workouts = $query->orderBy('created_at', 'desc')->get();

        return response()->json($workouts);
    }

    /**
     * Get workouts assigned to the authenticated student
     */
    public function assigned(Request $request)
    {
        $user = $request->user();

        // Only students can access this endpoint
        if ($user->role !== 'student') {
            return response()->json([
                'message' => 'Only students can view assigned workouts'
            ], 403);
        }

        $workouts = Workout::with(['exercises', 'trainer'])
            ->whereHas('assignments', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($workouts);
    }

    /**
     * Get a specific workout by ID
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $workout = Workout::with(['exercises', 'trainer'])->find($id);

        if (!$workout) {
            return response()->json([
                'message' => 'Workout not found'
            ], 404);
        }

        // Check access rights
        // Students can only see workouts assigned to them
        if ($user->role === 'student') {
            $isAssigned = $workout->assignments()
                ->where('user_id', $user->id)
                ->exists();

            if (!$isAssigned) {
                return response()->json([
                    'message' => 'You do not have access to this workout'
                ], 403);
            }
        }

        // Admins can see all workouts

        return response()->json($workout);
    }

    /**
     * Create a new workout (admin only)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admins can create workouts'
            ], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:Gym,Home,Outdoor,HIIT,Cardio,Strength',
            'duration' => 'required|string|max:50',
            'level' => 'required|in:Beginner,Intermediate,Advanced',
            'description' => 'nullable|string',
            'thumbnail_path' => 'nullable|string',
            'video_path' => 'nullable|string',
            'video_duration' => 'nullable|integer',
            'exercises' => 'nullable|array',
            'exercises.*.name' => 'required|string',
            'exercises.*.sets' => 'nullable|string',
            'exercises.*.reps' => 'nullable|string',
            'exercises.*.rest' => 'nullable|string',
            'exercises.*.order_index' => 'nullable|integer',
        ]);

        $workout = Workout::create([
            'title' => $request->title,
            'category' => $request->category,
            'duration' => $request->duration,
            'level' => $request->level,
            'description' => $request->description,
            'thumbnail_path' => $request->thumbnail_path,
            'video_path' => $request->video_path,
            'video_duration' => $request->video_duration,
            'trainer_id' => $user->id,
            'is_active' => true,
        ]);

        // Create exercises if provided
        if ($request->has('exercises')) {
            foreach ($request->exercises as $index => $exercise) {
                $workout->exercises()->create([
                    'name' => $exercise['name'],
                    'sets' => $exercise['sets'] ?? null,
                    'reps' => $exercise['reps'] ?? null,
                    'rest' => $exercise['rest'] ?? null,
                    'order_index' => $exercise['order_index'] ?? $index,
                ]);
            }
        }

        return response()->json(
            $workout->load('exercises'),
            201
        );
    }

    /**
     * Assign workout to student(s) (admin only)
     */
    public function assign(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admins can assign workouts'
            ], 403);
        }

        $workout = Workout::find($id);

        if (!$workout) {
            return response()->json([
                'message' => 'Workout not found'
            ], 404);
        }

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $assigned = [];
        foreach ($request->user_ids as $userId) {
            $assignment = $workout->assignments()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'assignable_type' => Workout::class,
                    'assignable_id' => $workout->id,
                ],
                [
                    'assigned_by' => $user->id,
                ]
            );
            $assigned[] = $assignment;
        }

        return response()->json([
            'message' => 'Workout assigned successfully',
            'assignments' => $assigned,
        ]);
    }
}
