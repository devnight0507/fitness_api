<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarController extends Controller
{
    /**
     * Get all calendar events for the authenticated user
     * Can filter by date range
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = CalendarEvent::where('user_id', $user->id);

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        // Filter by event type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $events = $query->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->get();

        return response()->json($events);
    }

    /**
     * Get today's events for the authenticated user
     */
    public function today(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $events = CalendarEvent::where('user_id', $user->id)
            ->where('date', $today)
            ->orderBy('time', 'asc')
            ->get();

        return response()->json($events);
    }

    /**
     * Get events grouped by date
     * Returns format: { "2025-11-05": [...events], "2025-11-06": [...events] }
     */
    public function grouped(Request $request)
    {
        $user = $request->user();
        $query = CalendarEvent::where('user_id', $user->id);

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $events = $query->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->get();

        // Group by date
        $grouped = $events->groupBy('date')->map(function ($dateEvents) {
            return $dateEvents->values();
        });

        return response()->json($grouped);
    }

    /**
     * Create a new calendar event
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:workout,nutrition,rest,assessment,other',
            'date' => 'required|date',
            'time' => 'required|string|max:20',
            'description' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id', // Optional: admins can specify student
        ]);

        // Determine who the event is for
        $targetUserId = $user->id; // Default: create for self

        // If user_id is provided, check if admin can create for student
        if ($request->has('user_id') && $request->user_id != $user->id) {
            if ($user->role === 'admin') {
                // Verify the student belongs to this admin
                $student = \App\Models\User::find($request->user_id);

                if (!$student) {
                    return response()->json([
                        'message' => 'Student not found'
                    ], 404);
                }

                if ($student->admin_id !== $user->id) {
                    return response()->json([
                        'message' => 'You can only create events for your own students'
                    ], 403);
                }

                $targetUserId = $request->user_id;
            } else {
                return response()->json([
                    'message' => 'Only admins can create events for other users'
                ], 403);
            }
        }

        $event = CalendarEvent::create([
            'user_id' => $targetUserId,
            'name' => $request->name,
            'type' => $request->type,
            'date' => $request->date,
            'time' => $request->time,
            'description' => $request->description,
            'created_by' => $user->id,
        ]);

        return response()->json($event, 201);
    }

    /**
     * Update a calendar event
     * Only admins can update events (students have read-only access)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        // Only admins can update events
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admins can update calendar events'
            ], 403);
        }

        $event = CalendarEvent::find($id);

        if (!$event) {
            return response()->json([
                'message' => 'Calendar event not found'
            ], 404);
        }

        // Check if admin created this event
        if ($event->created_by !== $user->id) {
            return response()->json([
                'message' => 'You can only update events you created'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:workout,nutrition,rest,assessment,other',
            'date' => 'sometimes|required|date',
            'time' => 'sometimes|required|string|max:20',
            'description' => 'nullable|string',
        ]);

        $event->update($request->only([
            'name', 'type', 'date', 'time', 'description'
        ]));

        return response()->json($event);
    }

    /**
     * Delete a calendar event
     * Only admins can delete events (students have read-only access)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        // Only admins can delete events
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Only admins can delete calendar events'
            ], 403);
        }

        $event = CalendarEvent::find($id);

        if (!$event) {
            return response()->json([
                'message' => 'Calendar event not found'
            ], 404);
        }

        // Check if admin created this event
        if ($event->created_by !== $user->id) {
            return response()->json([
                'message' => 'You can only delete events you created'
            ], 403);
        }

        $event->delete();

        return response()->json([
            'message' => 'Calendar event deleted successfully'
        ]);
    }

    /**
     * Get a specific calendar event
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $event = CalendarEvent::find($id);

        if (!$event) {
            return response()->json([
                'message' => 'Calendar event not found'
            ], 404);
        }

        // Check if user owns this event
        if ($event->user_id !== $user->id) {
            return response()->json([
                'message' => 'You do not have permission to view this event'
            ], 403);
        }

        return response()->json($event);
    }

    /**
     * Create multiple calendar events at once
     * Useful for creating weekly workout schedules
     */
    public function storeBulk(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'events' => 'required|array|min:1|max:50', // Max 50 events at once
            'events.*.name' => 'required|string|max:255',
            'events.*.type' => 'required|in:workout,nutrition,rest,assessment,other',
            'events.*.date' => 'required|date',
            'events.*.time' => 'required|string|max:20',
            'events.*.description' => 'nullable|string',
            'events.*.user_id' => 'nullable|exists:users,id',
        ]);

        $createdEvents = [];
        $errors = [];

        foreach ($request->events as $index => $eventData) {
            // Determine target user
            $targetUserId = $user->id;

            if (isset($eventData['user_id']) && $eventData['user_id'] != $user->id) {
                if ($user->role === 'admin') {
                    $student = \App\Models\User::find($eventData['user_id']);

                    if (!$student || $student->admin_id !== $user->id) {
                        $errors[] = [
                            'index' => $index,
                            'message' => 'Invalid student or not your student'
                        ];
                        continue;
                    }

                    $targetUserId = $eventData['user_id'];
                } else {
                    $errors[] = [
                        'index' => $index,
                        'message' => 'Only admins can create events for other users'
                    ];
                    continue;
                }
            }

            try {
                $event = CalendarEvent::create([
                    'user_id' => $targetUserId,
                    'name' => $eventData['name'],
                    'type' => $eventData['type'],
                    'date' => $eventData['date'],
                    'time' => $eventData['time'],
                    'description' => $eventData['description'] ?? null,
                    'created_by' => $user->id,
                ]);

                $createdEvents[] = $event;
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'message' => 'Failed to create event: ' . $e->getMessage()
                ];
            }
        }

        return response()->json([
            'created' => count($createdEvents),
            'failed' => count($errors),
            'events' => $createdEvents,
            'errors' => $errors
        ], 201);
    }
}
