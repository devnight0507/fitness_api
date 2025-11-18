<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    /**
     * Display a listing of students
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return redirect('/admin/login');
        }

        // Only admins can view students
        if ($user->role !== 'admin') {
            return redirect('/admin/login');
        }

        $students = User::where('role', 'student')
            ->select('id', 'name', 'email', 'avatar_path', 'created_at', 'age', 'goal')
            ->orderBy('name')
            ->get();

        return Inertia::render('Students', [
            'students' => $students,
            'user' => $user,
        ]);
    }

    /**
     * Show student profile with all details
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Only admins can view student profiles
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $student = User::where('role', 'student')->findOrFail($id);

        // Get student's workouts with exercises
        // Include both assigned workouts and personal workouts
        $workouts = Workout::where(function($query) use ($id) {
            // Get workouts assigned via user_assignments (public workouts)
            $query->whereHas('assignments', function($q) use ($id) {
                $q->where('user_id', $id);
            });
        })
        // OR get personal workouts created specifically for this student
        ->orWhere(function($query) use ($id) {
            $query->where('is_personal', true)
                  ->where('assigned_user_id', $id);
        })
        ->with('exercises')
        ->get();

        return response()->json([
            'student' => $student,
            'workouts' => $workouts,
        ]);
    }

    /**
     * Update student profile data
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Only admins can update student profiles
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admins can update student profiles'
            ], 403);
        }

        $student = User::where('role', 'student')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'age' => 'nullable|integer|min:1|max:120',
            'weight' => 'nullable|numeric|min:1|max:500',
            'height' => 'nullable|numeric|min:0.5|max:3',
            'goal' => 'nullable|string',
            'injuries' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $student->update($validated);

        return response()->json([
            'message' => 'Student profile updated successfully',
            'student' => $student,
        ]);
    }
}
