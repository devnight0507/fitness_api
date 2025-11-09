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
            'role' => 'nullable|in:student,admin',
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

        // Only allow admin and student roles to login
        if (!in_array($user->role, ['admin', 'student'])) {
            throw ValidationException::withMessages([
                'email' => ['This account type is no longer supported. Please contact support.'],
            ]);
        }

        // Load admin relationship
        $user->load('admin');

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
        return response()->json($request->user()->load('admin'));
    }

    /**
     * Get list of users for chat (students chat with admin, admins chat with all students)
     */
    public function getChatUsers(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'student') {
            // Students can only chat with their assigned admin
            if (!$user->admin_id) {
                return response()->json([]);
            }

            $admin = User::select('id', 'name', 'email', 'role', 'avatar_path')
                ->find($user->admin_id);

            return response()->json($admin ? [$admin] : []);
        }

        if ($user->role === 'admin') {
            // Admins can chat with all students
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
