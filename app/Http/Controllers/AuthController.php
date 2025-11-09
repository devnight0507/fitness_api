<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|in:student,trainer',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'student',
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Load trainer relationship
        $user->load('trainer');

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user()->load('trainer'));
    }

    /**
     * Get list of users for chat (trainers get their students, admins get all users)
     */
    public function getChatUsers(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'student') {
            // Students can only chat with their trainer
            if (!$user->trainer_id) {
                return response()->json([]);
            }

            $trainer = User::select('id', 'name', 'email', 'role', 'avatar_path')
                ->find($user->trainer_id);

            return response()->json($trainer ? [$trainer] : []);
        }

        if ($user->role === 'trainer') {
            // Trainers can chat with their students
            $students = User::select('id', 'name', 'email', 'role', 'avatar_path')
                ->where('trainer_id', $user->id)
                ->where('role', 'student')
                ->orderBy('name')
                ->get();

            return response()->json($students);
        }

        if ($user->role === 'admin') {
            // Admins can chat with everyone
            $users = User::select('id', 'name', 'email', 'role', 'avatar_path')
                ->where('id', '!=', $user->id) // Exclude self
                ->orderBy('role')
                ->orderBy('name')
                ->get();

            return response()->json($users);
        }

        return response()->json([]);
    }
}
