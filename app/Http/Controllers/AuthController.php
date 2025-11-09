<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    /**
     * Send password reset code
     * For now, just generates a code and returns it (no email sending)
     * In production, this should send an email
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Don't reveal if email exists or not for security
            return response()->json([
                'message' => 'If that email exists, a reset code has been sent.',
            ]);
        }

        // Generate 6-digit reset code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Delete old reset tokens for this email
        DB::table('password_resets')
            ->where('email', $request->email)
            ->delete();

        // Store reset code (in production, hash this)
        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => Hash::make($code),
            'created_at' => now(),
        ]);

        // In production, send email here
        // For development, return the code (REMOVE IN PRODUCTION!)
        return response()->json([
            'message' => 'If that email exists, a reset code has been sent.',
            'code' => $code, // ONLY FOR DEVELOPMENT - REMOVE IN PRODUCTION
        ]);
    }

    /**
     * Verify reset code
     */
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$reset) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired reset code.'],
            ]);
        }

        // Check if code is expired (15 minutes)
        if (now()->diffInMinutes($reset->created_at) > 15) {
            DB::table('password_resets')
                ->where('email', $request->email)
                ->delete();

            throw ValidationException::withMessages([
                'code' => ['Reset code has expired.'],
            ]);
        }

        // Verify code
        if (!Hash::check($request->code, $reset->token)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid reset code.'],
            ]);
        }

        // Generate temporary token for password reset
        $resetToken = Str::random(60);

        // Update the reset record with the temporary token
        DB::table('password_resets')
            ->where('email', $request->email)
            ->update([
                'token' => Hash::make($resetToken),
                'created_at' => now(),
            ]);

        return response()->json([
            'message' => 'Code verified successfully',
            'reset_token' => $resetToken,
        ]);
    }

    /**
     * Reset password with verified token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'reset_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$reset) {
            throw ValidationException::withMessages([
                'reset_token' => ['Invalid or expired reset token.'],
            ]);
        }

        // Check if token is expired (15 minutes)
        if (now()->diffInMinutes($reset->created_at) > 15) {
            DB::table('password_resets')
                ->where('email', $request->email)
                ->delete();

            throw ValidationException::withMessages([
                'reset_token' => ['Reset token has expired.'],
            ]);
        }

        // Verify reset token
        if (!Hash::check($request->reset_token, $reset->token)) {
            throw ValidationException::withMessages([
                'reset_token' => ['Invalid reset token.'],
            ]);
        }

        // Update user password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete reset token
        DB::table('password_resets')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'message' => 'Password reset successfully',
        ]);
    }
}
