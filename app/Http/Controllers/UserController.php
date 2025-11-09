<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class UserController extends Controller
{
    /**
     * Update user profile information
     * Updates: name, weight, height, age, goal
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'weight' => 'sometimes|nullable|numeric|min:0|max:500',
            'height' => 'sometimes|nullable|numeric|min:0|max:300',
            'age' => 'sometimes|nullable|integer|min:1|max:150',
            'goal' => 'sometimes|nullable|in:weight_loss,muscle_gain,maintenance,general_fitness',
        ]);

        // Update only the fields that were provided
        $user->update($validated);

        // Reload user with admin relationship
        $user->load('admin');

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    /**
     * Upload and update user avatar
     * Accepts image file, resizes, and saves to storage
     */
    public function uploadAvatar(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png|max:5120', // 5MB max
        ]);

        try {
            // Delete old avatar if exists
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            // Get uploaded file
            $file = $request->file('avatar');

            // Generate unique filename
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();

            // Store the original image
            $path = $file->storeAs('avatars', $filename, 'public');

            // Update user record
            $user->avatar_path = $path;
            $user->save();

            // Reload user with admin relationship
            $user->load('admin');

            return response()->json([
                'message' => 'Avatar uploaded successfully',
                'user' => $user,
                'avatar_url' => $user->avatar_url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload avatar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete user avatar
     */
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar_path) {
            // Delete file from storage
            Storage::disk('public')->delete($user->avatar_path);

            // Clear avatar path
            $user->avatar_path = null;
            $user->save();
        }

        // Reload user with admin relationship
        $user->load('admin');

        return response()->json([
            'message' => 'Avatar deleted successfully',
            'user' => $user,
        ]);
    }

    /**
     * Update push notification token
     */
    public function updatePushToken(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'push_token' => 'required|string',
        ]);

        $user->push_token = $validated['push_token'];
        $user->save();

        return response()->json([
            'message' => 'Push token updated successfully',
            'user' => $user,
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateNotificationSettings(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'notifications_enabled' => 'required|boolean',
        ]);

        $user->notifications_enabled = $validated['notifications_enabled'];
        $user->save();

        return response()->json([
            'message' => 'Notification settings updated successfully',
            'user' => $user,
        ]);
    }
}
