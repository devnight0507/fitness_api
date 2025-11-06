<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Get all conversations for the authenticated user
     * Returns list of users with their last message
     */
    public function conversations(Request $request)
    {
        $user = $request->user();

        // For students, they can only chat with their trainer
        if ($user->role === 'student') {
            if (!$user->trainer_id) {
                return response()->json([
                    'conversations' => []
                ]);
            }

            $trainer = User::find($user->trainer_id);
            if (!$trainer) {
                return response()->json([
                    'conversations' => []
                ]);
            }

            // Get last message between student and trainer
            $lastMessage = Message::where(function ($query) use ($user, $trainer) {
                $query->where('sender_id', $user->id)
                    ->where('receiver_id', $trainer->id);
            })->orWhere(function ($query) use ($user, $trainer) {
                $query->where('sender_id', $trainer->id)
                    ->where('receiver_id', $user->id);
            })->orderBy('created_at', 'desc')->first();

            return response()->json([
                'conversations' => [[
                    'user' => $trainer,
                    'last_message' => $lastMessage,
                    'unread_count' => Message::where('sender_id', $trainer->id)
                        ->where('receiver_id', $user->id)
                        ->whereNull('read_at')
                        ->count()
                ]]
            ]);
        }

        // For trainers, get all their students with last messages
        if ($user->role === 'trainer') {
            $students = User::where('trainer_id', $user->id)->get();

            $conversations = $students->map(function ($student) use ($user) {
                $lastMessage = Message::where(function ($query) use ($user, $student) {
                    $query->where('sender_id', $user->id)
                        ->where('receiver_id', $student->id);
                })->orWhere(function ($query) use ($user, $student) {
                    $query->where('sender_id', $student->id)
                        ->where('receiver_id', $user->id);
                })->orderBy('created_at', 'desc')->first();

                return [
                    'user' => $student,
                    'last_message' => $lastMessage,
                    'unread_count' => Message::where('sender_id', $student->id)
                        ->where('receiver_id', $user->id)
                        ->whereNull('read_at')
                        ->count()
                ];
            });

            return response()->json([
                'conversations' => $conversations
            ]);
        }

        return response()->json([
            'conversations' => []
        ]);
    }

    /**
     * Get messages between authenticated user and another user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $otherUserId = $request->query('user_id');

        if (!$otherUserId) {
            return response()->json([
                'message' => 'user_id is required'
            ], 400);
        }

        // Verify relationship
        $otherUser = User::find($otherUserId);
        if (!$otherUser) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Check if users can chat (student with trainer only)
        if ($user->role === 'student' && $user->trainer_id != $otherUserId) {
            return response()->json([
                'message' => 'You can only chat with your trainer'
            ], 403);
        }

        if ($user->role === 'trainer' && $otherUser->trainer_id != $user->id) {
            return response()->json([
                'message' => 'You can only chat with your students'
            ], 403);
        }

        // Get messages between the two users
        $messages = Message::with(['sender', 'receiver'])
            ->where(function ($query) use ($user, $otherUserId) {
                $query->where('sender_id', $user->id)
                    ->where('receiver_id', $otherUserId);
            })
            ->orWhere(function ($query) use ($user, $otherUserId) {
                $query->where('sender_id', $otherUserId)
                    ->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark messages as read
        Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    /**
     * Send a message
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:5000',
        ]);

        $receiverId = $request->receiver_id;
        $receiver = User::find($receiverId);

        // Check if users can chat (student with trainer only)
        if ($user->role === 'student' && $user->trainer_id != $receiverId) {
            return response()->json([
                'message' => 'You can only chat with your trainer'
            ], 403);
        }

        if ($user->role === 'trainer' && $receiver->trainer_id != $user->id) {
            return response()->json([
                'message' => 'You can only chat with your students'
            ], 403);
        }

        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'message' => $request->message,
            'read_at' => null,
        ]);

        return response()->json(
            $message->load(['sender', 'receiver']),
            201
        );
    }

    /**
     * Mark a message as read
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();

        $message = Message::find($id);

        if (!$message) {
            return response()->json([
                'message' => 'Message not found'
            ], 404);
        }

        // Only the receiver can mark a message as read
        if ($message->receiver_id !== $user->id) {
            return response()->json([
                'message' => 'You can only mark your own messages as read'
            ], 403);
        }

        $message->update([
            'read_at' => now(),
        ]);

        return response()->json($message);
    }

    /**
     * Get unread message count
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();

        $count = Message::where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unread_count' => $count
        ]);
    }
}
