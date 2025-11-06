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
        ]);

        $event = CalendarEvent::create([
            'user_id' => $user->id,
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
     */
    public function update(Request $request, $id)
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
                'message' => 'You do not have permission to update this event'
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
     */
    public function destroy(Request $request, $id)
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
                'message' => 'You do not have permission to delete this event'
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
}
