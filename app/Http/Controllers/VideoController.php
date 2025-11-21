<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\ViewLog;
use App\Services\VideoStream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    /**
     * Stream video for a workout
     */
    public function stream(Request $request, $workoutId)
    {
        // Get user from token (supports both header and query parameter)
        $user = $request->user();

        // If no user from header, try to get token from query parameter
        if (!$user && $request->has('token')) {
            $token = $request->query('token');
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

            if ($personalAccessToken) {
                $user = $personalAccessToken->tokenable;
            }
        }

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }
        $workout = Workout::find($workoutId);

        if (!$workout) {
            return response()->json([
                'message' => 'Workout not found'
            ], 404);
        }

        // Check if video exists
        if (!$workout->video_path) {
            return response()->json([
                'message' => 'No video available for this workout'
            ], 404);
        }

        // Check access rights (students can only see assigned workouts)
        if ($user->role === 'student') {
            // Check if it's a personal workout assigned directly to this student
            $isPersonalWorkout = $workout->is_personal && $workout->assigned_user_id == $user->id;

            // Check if it's assigned via user_assignments table
            $isAssigned = $workout->assignments()
                ->where('user_id', $user->id)
                ->exists();

            if (!$isPersonalWorkout && !$isAssigned) {
                return response()->json([
                    'message' => 'You do not have access to this video'
                ], 403);
            }
        }

        // Admins can only see their own workout videos
        if ($user->role === 'admin' && $workout->admin_id !== $user->id) {
            return response()->json([
                'message' => 'You do not have access to this video'
            ], 403);
        }

        // Get video file path
        $videoPath = storage_path('app/public/' . $workout->video_path);

        if (!file_exists($videoPath)) {
            return response()->json([
                'message' => 'Video file not found on server'
            ], 404);
        }

        // Stream the video
        $stream = new VideoStream($videoPath);
        return $stream->start();
    }

    /**
     * Stream video for an exercise
     */
    public function streamExercise(Request $request, $exerciseId)
    {
        // Get user from token (supports both header and query parameter)
        $user = $request->user();

        // If no user from header, try to get token from query parameter
        if (!$user && $request->has('token')) {
            $token = $request->query('token');
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

            if ($personalAccessToken) {
                $user = $personalAccessToken->tokenable;
            }
        }

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $exercise = WorkoutExercise::with('workout')->find($exerciseId);

        if (!$exercise) {
            return response()->json([
                'message' => 'Exercise not found'
            ], 404);
        }

        // Check if video exists
        if (!$exercise->video_path) {
            return response()->json([
                'message' => 'No video available for this exercise'
            ], 404);
        }

        $workout = $exercise->workout;

        // Check access rights (students can only see assigned workouts)
        if ($user->role === 'student') {
            // Check if it's a personal workout assigned directly to this student
            $isPersonalWorkout = $workout->is_personal && $workout->assigned_user_id == $user->id;

            // Check if it's assigned via user_assignments table
            $isAssigned = $workout->assignments()
                ->where('user_id', $user->id)
                ->exists();

            if (!$isPersonalWorkout && !$isAssigned) {
                return response()->json([
                    'message' => 'You do not have access to this video'
                ], 403);
            }
        }

        // Admins can only see their own workout videos
        if ($user->role === 'admin' && $workout->admin_id !== $user->id) {
            return response()->json([
                'message' => 'You do not have access to this video'
            ], 403);
        }

        // Get video file path
        $videoPath = storage_path('app/public/' . $exercise->video_path);

        if (!file_exists($videoPath)) {
            return response()->json([
                'message' => 'Video file not found on server'
            ], 404);
        }

        // Stream the video
        $stream = new VideoStream($videoPath);
        return $stream->start();
    }

    /**
     * Get video thumbnail
     */
    public function thumbnail(Request $request, $workoutId)
    {
        $user = $request->user();
        $workout = Workout::find($workoutId);

        if (!$workout) {
            return response()->json([
                'message' => 'Workout not found'
            ], 404);
        }

        if (!$workout->thumbnail_path) {
            return response()->json([
                'message' => 'No thumbnail available'
            ], 404);
        }

        // Check access rights
        if ($user->role === 'student') {
            $isAssigned = $workout->assignments()
                ->where('user_id', $user->id)
                ->exists();

            if (!$isAssigned) {
                return response()->json([
                    'message' => 'You do not have access to this thumbnail'
                ], 403);
            }
        }

        if ($user->role === 'admin' && $workout->admin_id !== $user->id) {
            return response()->json([
                'message' => 'You do not have access to this thumbnail'
            ], 403);
        }

        $thumbnailPath = storage_path('app/public/' . $workout->thumbnail_path);

        if (!file_exists($thumbnailPath)) {
            return response()->json([
                'message' => 'Thumbnail file not found'
            ], 404);
        }

        return response()->file($thumbnailPath);
    }

    /**
     * Log video view
     */
    public function logView(Request $request, $workoutId)
    {
        $user = $request->user();

        $request->validate([
            'duration_watched' => 'required|integer|min:0',
            'completed' => 'required|boolean',
        ]);

        $workout = Workout::find($workoutId);

        if (!$workout) {
            return response()->json([
                'message' => 'Workout not found'
            ], 404);
        }

        // Check if user has access
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

        // Create or update view log
        $viewLog = ViewLog::updateOrCreate(
            [
                'user_id' => $user->id,
                'workout_id' => $workoutId,
            ],
            [
                'duration_watched' => $request->duration_watched,
                'completed' => $request->completed,
                'watched_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'View logged successfully',
            'view_log' => $viewLog,
        ]);
    }

    /**
     * Get video viewing statistics for a workout
     */
    public function stats(Request $request, $workoutId)
    {
        $user = $request->user();
        $workout = Workout::find($workoutId);

        if (!$workout) {
            return response()->json([
                'message' => 'Workout not found'
            ], 404);
        }

        // Only admins can see stats for their workouts
        if ($user->role === 'admin' && $workout->admin_id !== $user->id) {
            return response()->json([
                'message' => 'You do not have permission to view these statistics'
            ], 403);
        }

        if ($user->role === 'student') {
            return response()->json([
                'message' => 'Students cannot view video statistics'
            ], 403);
        }

        $stats = ViewLog::where('workout_id', $workoutId)
            ->with('user:id,name,email')
            ->get();

        $summary = [
            'total_views' => $stats->count(),
            'completed_views' => $stats->where('completed', true)->count(),
            'average_duration' => $stats->avg('duration_watched'),
            'views' => $stats,
        ];

        return response()->json($summary);
    }

    /**
     * Get user's video viewing history
     */
    public function myHistory(Request $request)
    {
        $user = $request->user();

        $history = ViewLog::where('user_id', $user->id)
            ->with('workout:id,title,category,duration,thumbnail_path')
            ->orderBy('watched_at', 'desc')
            ->get();

        return response()->json($history);
    }

    /**
     * Upload video for a workout (Admin only)
     */
    public function upload(Request $request)
    {
        $user = $request->user();

        // Only admins can upload videos
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admins can upload videos'
            ], 403);
        }

        // Validate the request
        $validated = $request->validate([
            'video' => [
                'required',
                'file',
                'max:102400', // 100MB max
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    $allowedExtensions = ['mp4', 'mov', 'avi', 'mkv', 'webm'];

                    if (!in_array($extension, $allowedExtensions)) {
                        $fail('The video must be a file of type: mp4, mov, avi, mkv, webm.');
                    }
                },
            ],
            'workout_id' => 'required|exists:workouts,id',
        ]);

        try {
            $workout = Workout::find($request->workout_id);

            // Check if admin owns this workout
            if ($workout->admin_id !== $user->id) {
                return response()->json([
                    'message' => 'You can only upload videos for your own workouts'
                ], 403);
            }

            // Delete old video if exists
            if ($workout->video_path) {
                Storage::disk('public')->delete($workout->video_path);
            }

            // Store video
            $file = $request->file('video');
            $filename = 'workout_' . $workout->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('videos/workouts', $filename, 'public');

            // Update workout
            $workout->video_path = $path;
            $workout->save();

            return response()->json([
                'message' => 'Video uploaded successfully',
                'workout' => $workout,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload video',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload video for an exercise (Admin only)
     */
    public function uploadExerciseVideo(Request $request)
    {
        $user = $request->user();

        // Only admins can upload videos
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admins can upload videos'
            ], 403);
        }

        // Validate the request
        $validated = $request->validate([
            'video' => [
                'required',
                'file',
                'max:102400', // 100MB max
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    $allowedExtensions = ['mp4', 'mov', 'avi', 'mkv', 'webm'];

                    if (!in_array($extension, $allowedExtensions)) {
                        $fail('The video must be a file of type: mp4, mov, avi, mkv, webm.');
                    }
                },
            ],
            'exercise_id' => 'nullable|exists:workout_exercises,id',
        ]);

        try {
            // If exercise_id is provided, update the exercise
            if ($request->has('exercise_id')) {
                $exercise = WorkoutExercise::with('workout')->find($request->exercise_id);

                // Check if admin owns this workout
                if ($exercise->workout->admin_id !== $user->id) {
                    return response()->json([
                        'message' => 'You can only upload videos for your own workout exercises'
                    ], 403);
                }

                // Delete old video if exists
                if ($exercise->video_path) {
                    Storage::disk('public')->delete($exercise->video_path);
                }

                // Store video
                $file = $request->file('video');
                $filename = 'exercise_' . $exercise->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('videos/exercises', $filename, 'public');

                // Update exercise
                $exercise->video_path = $path;
                $exercise->save();

                return response()->json([
                    'message' => 'Exercise video uploaded successfully',
                    'exercise' => $exercise,
                    'video_path' => $path,
                ]);
            } else {
                // No exercise_id provided, just upload the video and return the path
                $file = $request->file('video');
                $filename = 'exercise_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('videos/exercises', $filename, 'public');

                return response()->json([
                    'message' => 'Video uploaded successfully',
                    'video_path' => $path,
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload video',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload thumbnail image for a workout or nutrition plan
     */
    public function uploadThumbnail(Request $request)
    {
        $user = $request->user();

        // Only admins can upload thumbnails
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admins can upload thumbnails'
            ], 403);
        }

        // Validate the request - workout_id is optional now
        $validated = $request->validate([
            'thumbnail' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120', // 5MB max
            ],
            'workout_id' => 'nullable|exists:workouts,id',
        ]);

        try {
            // If workout_id is provided, update the workout
            if ($request->has('workout_id')) {
                $workout = Workout::find($request->workout_id);

                // Check if admin owns this workout
                if ($workout->admin_id !== $user->id) {
                    return response()->json([
                        'message' => 'You can only upload thumbnails for your own workouts'
                    ], 403);
                }

                // Delete old thumbnail if exists and is a local file
                if ($workout->thumbnail_path && !filter_var($workout->thumbnail_path, FILTER_VALIDATE_URL)) {
                    Storage::disk('public')->delete($workout->thumbnail_path);
                }

                // Store thumbnail
                $file = $request->file('thumbnail');
                $filename = 'thumbnail_' . $workout->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('images/thumbnails', $filename, 'public');

                // Update workout
                $workout->thumbnail_path = $path;
                $workout->save();

                return response()->json([
                    'message' => 'Thumbnail uploaded successfully',
                    'workout' => $workout,
                    'thumbnail_path' => $path,
                    'thumbnail_url' => Storage::disk('public')->url($path),
                ]);
            } else {
                // No workout_id provided, just upload the thumbnail and return the path
                $file = $request->file('thumbnail');
                $filename = 'thumbnail_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('images/thumbnails', $filename, 'public');

                return response()->json([
                    'message' => 'Thumbnail uploaded successfully',
                    'thumbnail_path' => $path,
                    'thumbnail_url' => Storage::disk('public')->url($path),
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload thumbnail',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
