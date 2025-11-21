<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Workout;
use App\Models\NutritionPlan;

Route::get('/', function () {
    return view('welcome');
});

// Admin login page
Route::get('/admin/login', function () {
    return Inertia::render('Login');
})->name('admin.login');

// Admin panel (check if user has token in localStorage via Vue)
Route::get('/admin', function () {
    // Check if user is authenticated via bearer token in request
    // If not, Vue will handle showing login
    $workouts = [];
    $user = null;

    // Try to get bearer token from cookie or session if available
    if (request()->bearerToken()) {
        try {
            $user = auth('sanctum')->user();
            if ($user && $user->role === 'admin') {
                $workouts = Workout::with('exercises')->orderBy('created_at', 'desc')->get();
            }
        } catch (\Exception $e) {
            // Token invalid, will show login
        }
    }

    return Inertia::render('Admin', [
        'workouts' => $workouts,
        'user' => $user,
    ]);
})->name('admin.dashboard');

// Create workout page
Route::get('/admin/workouts/create', function () {
    return Inertia::render('WorkoutForm', [
        'workout' => null,
    ]);
})->name('admin.workouts.create');

// Edit workout page
Route::get('/admin/workouts/{id}/edit', function ($id) {
    $workout = Workout::with('exercises')->find($id);

    if (!$workout) {
        return redirect('/admin')->with('error', 'Workout not found');
    }

    return Inertia::render('WorkoutForm', [
        'workout' => $workout,
    ]);
})->name('admin.workouts.edit');

// Workouts management page
Route::get('/admin/workouts', function () {
    return Inertia::render('Workouts');
})->name('admin.workouts');

// Students management
Route::get('/admin/students', function () {
    return Inertia::render('Students', [
        'students' => [],
        'user' => null,
    ]);
})->name('admin.students');

Route::get('/admin/students/{id}', function ($id) {
    return Inertia::render('StudentProfile', [
        'student' => ['id' => $id],
        'workouts' => [],
        'user' => null,
    ]);
})->name('admin.students.show');

// Messages/Chat management
Route::get('/admin/messages', function () {
    return Inertia::render('Messages', [
        'user' => null,
    ]);
})->name('admin.messages');

// Calendar management
Route::get('/admin/calendar', function () {
    return Inertia::render('Calendar', [
        'user' => null,
    ]);
})->name('admin.calendar');

// Nutrition management
Route::get('/admin/nutrition/create', function () {
    return Inertia::render('NutritionForm', [
        'nutrition' => null,
    ]);
})->name('admin.nutrition.create');

Route::get('/admin/nutrition/{id}/edit', function ($id) {
    $nutrition = NutritionPlan::with(['meals', 'assignments'])->find($id);

    if (!$nutrition) {
        return redirect('/admin')->with('error', 'Nutrition plan not found');
    }

    // Get the first assigned student's ID (if any)
    $studentId = $nutrition->assignments->first()?->user_id;

    return Inertia::render('NutritionForm', [
        'nutrition' => $nutrition,
        'student_id' => $studentId,
    ]);
})->name('admin.nutrition.edit');

// Logout route
Route::post('/admin/logout', function () {
    auth()->guard('sanctum')->user()?->tokens()->delete();
    return redirect('/admin/login');
})->name('admin.logout');

Route::get('/status', function () {
    $milestones = [
        'Milestone 1: Complete UI implementation with 10 screens and navigation',
        'Milestone 1: Mock data integration for all features',
        'Milestone 2: Laravel backend setup and API development',
        'Milestone 2: Database schema and migrations',
        'Milestone 2: Authentication system (Student/Admin roles)',
        'Milestone 2: Workout management and video streaming',
        'Milestone 2: Nutrition plans management',
        'Milestone 2: Real-time messaging system',
        'Milestone 2: Admin panel for content management',
        'Milestone 3: Full mobile app backend integration',
        'Milestone 3: Custom video player with controls',
        'Milestone 3: YouTube video integration',
        'Milestone 3: Calendar and progress tracking',
        'Milestone 3: Profile management and avatar uploads',
    ];

    return Inertia::render('Status', [
        'milestones' => $milestones,
    ]);
});
