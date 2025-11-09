<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\NutritionController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\HomeController;
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

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/users/chat', [AuthController::class, 'getChatUsers']);

    // Home / Dashboard
    Route::get('/dashboard', [HomeController::class, 'dashboard']);
    Route::get('/quote/random', [HomeController::class, 'randomQuote']);
    Route::get('/weekly-summary', [HomeController::class, 'weeklySummary']);

    // Workouts
    Route::get('/workouts', [WorkoutController::class, 'index']);
    Route::get('/workouts/assigned', [WorkoutController::class, 'assigned']);
    Route::get('/workouts/{id}', [WorkoutController::class, 'show']);
    Route::post('/workouts', [WorkoutController::class, 'store']);
    Route::post('/workouts/{id}/assign', [WorkoutController::class, 'assign']);

    // Nutrition Plans
    Route::get('/nutrition', [NutritionController::class, 'index']);
    Route::get('/nutrition/assigned', [NutritionController::class, 'assigned']);
    Route::get('/nutrition/{id}', [NutritionController::class, 'show']);
    Route::post('/nutrition', [NutritionController::class, 'store']);
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

    // Videos
    Route::get('/videos/{workoutId}/stream', [VideoController::class, 'stream']);
    Route::get('/videos/{workoutId}/thumbnail', [VideoController::class, 'thumbnail']);
    Route::post('/videos/{workoutId}/log-view', [VideoController::class, 'logView']);
    Route::get('/videos/{workoutId}/stats', [VideoController::class, 'stats']);
    Route::get('/videos/my-history', [VideoController::class, 'myHistory']);
});
