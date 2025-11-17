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
        $query = Workout::with(['exercises', 'admin'])
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

        $workouts = Workout::with(['exercises', 'admin'])
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

        $workout = Workout::with(['exercises', 'admin'])->find($id);

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
            'youtube_url' => 'nullable|string|url',
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
            'youtube_url' => $request->youtube_url,
            'video_duration' => $request->video_duration,
            'admin_id' => $user->id,
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
     * Update a workout (admin only)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admins can update workouts'
            ], 403);
        }

        $workout = Workout::find($id);

        if (!$workout) {
            return response()->json([
                'message' => 'Workout not found'
            ], 404);
        }

        // Check if admin owns this workout
        if ($workout->admin_id !== $user->id) {
            return response()->json([
                'message' => 'You can only update your own workouts'
            ], 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|in:Gym,Home,Outdoor,HIIT,Cardio,Strength',
            'duration' => 'sometimes|required|string|max:50',
            'level' => 'sometimes|required|in:Beginner,Intermediate,Advanced',
            'description' => 'nullable|string',
            'thumbnail_path' => 'nullable|string',
            'video_path' => 'nullable|string',
            'youtube_url' => 'nullable|string|url',
            'video_duration' => 'nullable|integer',
            'exercises' => 'nullable|array',
            'exercises.*.name' => 'required|string',
            'exercises.*.sets' => 'nullable|string',
            'exercises.*.reps' => 'nullable|string',
            'exercises.*.rest' => 'nullable|string',
            'exercises.*.order_index' => 'nullable|integer',
        ]);

        $workout->update($request->only([
            'title',
            'category',
            'duration',
            'level',
            'description',
            'thumbnail_path',
            'video_path',
            'youtube_url',
            'video_duration',
        ]));

        // Update exercises if provided
        if ($request->has('exercises')) {
            // Delete old exercises
            $workout->exercises()->delete();

            // Create new exercises
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
            $workout->load('exercises')
        );
    }

    /**
     * Delete a workout (admin only)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admins can delete workouts'
            ], 403);
        }

        $workout = Workout::find($id);

        if (!$workout) {
            return response()->json([
                'message' => 'Workout not found'
            ], 404);
        }

        // Check if admin owns this workout
        if ($workout->admin_id !== $user->id) {
            return response()->json([
                'message' => 'You can only delete your own workouts'
            ], 403);
        }

        // Delete associated video file if exists
        if ($workout->video_path) {
            \Storage::disk('public')->delete($workout->video_path);
        }

        // Delete exercises
        $workout->exercises()->delete();

        // Delete assignments
        $workout->assignments()->delete();

        // Delete workout
        $workout->delete();

        return response()->json([
            'message' => 'Workout deleted successfully'
        ]);
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

    /**
     * Get current assignments for a workout (admin only)
     */
    public function getAssignments(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admins can view assignments'
            ], 403);
        }

        $workout = Workout::find($id);

        if (!$workout) {
            return response()->json([
                'message' => 'Workout not found'
            ], 404);
        }

        $assignments = $workout->assignments()
            ->with('user:id,name,email')
            ->get();

        return response()->json($assignments);
    }
}
