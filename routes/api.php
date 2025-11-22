<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\NutritionController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Test endpoint
Route::get('/test', function () {
    return response()->json([
        'message' => 'Fitness App API is running!',
        'version' => '1.0.0',
        'timestamp' => now()->toDateTimeString(),
    ]);
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Video streaming (supports token in query parameter for mobile video players)
Route::get('/videos/{workoutId}/stream', [VideoController::class, 'stream']);
Route::get('/videos/exercise/{exerciseId}/stream', [VideoController::class, 'streamExercise']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/users/chat', [AuthController::class, 'getChatUsers']);

    // User Profile
    Route::get('/users', [UserController::class, 'index']); // Get users list (admin only)
    Route::put('/users/{id}', [UserController::class, 'updateStudent']); // Update student (admin only)
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/avatar', [UserController::class, 'uploadAvatar']);
    Route::delete('/user/avatar', [UserController::class, 'deleteAvatar']);
    Route::post('/user/push-token', [UserController::class, 'updatePushToken']);
    Route::put('/user/notifications', [UserController::class, 'updateNotificationSettings']);

    // Home / Dashboard
    Route::get('/dashboard', [HomeController::class, 'dashboard']);
    Route::get('/quote/random', [HomeController::class, 'randomQuote']);
    Route::get('/weekly-summary', [HomeController::class, 'weeklySummary']);

    // Workouts
    Route::get('/workouts', [WorkoutController::class, 'index']);
    Route::get('/workouts/assigned', [WorkoutController::class, 'assigned']);
    Route::get('/workouts/{id}', [WorkoutController::class, 'show']);
    Route::post('/workouts', [WorkoutController::class, 'store']);
    Route::put('/workouts/{id}', [WorkoutController::class, 'update']);
    Route::delete('/workouts/{id}', [WorkoutController::class, 'destroy']);
    Route::get('/workouts/{id}/assignments', [WorkoutController::class, 'getAssignments']);
    Route::post('/workouts/{id}/assign', [WorkoutController::class, 'assign']);

    // Nutrition Plans
    Route::get('/nutrition', [NutritionController::class, 'index']);
    Route::get('/nutrition/assigned', [NutritionController::class, 'assigned']);
    Route::get('/nutrition/{id}', [NutritionController::class, 'show']);
    Route::post('/nutrition', [NutritionController::class, 'store']);
    Route::put('/nutrition/{id}', [NutritionController::class, 'update']);
    Route::delete('/nutrition/{id}', [NutritionController::class, 'destroy']);
    Route::post('/nutrition/{id}/assign', [NutritionController::class, 'assign']);

    // Messages / Chat
    Route::get('/messages/conversations', [MessageController::class, 'conversations']);
    Route::get('/messages/has-new', [MessageController::class, 'hasNewMessages']);
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount']);
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::patch('/messages/{id}/read', [MessageController::class, 'markAsRead']);
    Route::delete('/messages/{id}', [MessageController::class, 'destroy']);

    // Calendar Events
    Route::get('/calendar', [CalendarController::class, 'index']);
    Route::get('/calendar/today', [CalendarController::class, 'today']);
    Route::get('/calendar/grouped', [CalendarController::class, 'grouped']);
    Route::post('/calendar/bulk', [CalendarController::class, 'storeBulk']);
    Route::get('/calendar/{id}', [CalendarController::class, 'show']);
    Route::post('/calendar', [CalendarController::class, 'store']);
    Route::put('/calendar/{id}', [CalendarController::class, 'update']);
    Route::delete('/calendar/{id}', [CalendarController::class, 'destroy']);

    // Videos (stream is outside auth middleware to support query token)
    Route::get('/videos/{workoutId}/thumbnail', [VideoController::class, 'thumbnail']);
    Route::post('/videos/{workoutId}/log-view', [VideoController::class, 'logView']);
    Route::get('/videos/{workoutId}/stats', [VideoController::class, 'stats']);
    Route::get('/videos/my-history', [VideoController::class, 'myHistory']);
    Route::post('/videos/upload', [VideoController::class, 'upload']);
    Route::post('/videos/exercise/upload', [VideoController::class, 'uploadExerciseVideo']);
    Route::post('/thumbnails/upload', [VideoController::class, 'uploadThumbnail']);

    // Students
    Route::get('/students/{id}', [\App\Http\Controllers\StudentController::class, 'show']);
});
