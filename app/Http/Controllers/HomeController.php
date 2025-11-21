<?php

namespace App\Http\Controllers;

use App\Models\MotivationalQuote;
use App\Models\Workout;
use App\Models\NutritionPlan;
use App\Models\CalendarEvent;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Get dashboard data for authenticated user
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $data = [
            'user' => $user->load('admin'),
            'quote' => $this->getRandomQuote(),
        ];

        // Student dashboard
        if ($user->role === 'student') {
            // Get assigned workouts
            $workouts = Workout::with(['exercises', 'admin'])
                ->whereHas('assignments', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Get assigned nutrition plans
            $nutritionPlans = NutritionPlan::with(['meals', 'admin'])
                ->whereHas('assignments', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();

            // Get today's calendar events
            $today = Carbon::today()->toDateString();
            $todayEvents = CalendarEvent::where('user_id', $user->id)
                ->where('date', $today)
                ->orderBy('time', 'asc')
                ->get();

            // Get unread messages count
            $unreadMessagesCount = Message::where('receiver_id', $user->id)
                ->whereNull('read_at')
                ->count();

            $data['workouts'] = $workouts;
            $data['nutrition_plans'] = $nutritionPlans;
            $data['today_events'] = $todayEvents;
            $data['unread_messages_count'] = $unreadMessagesCount;

            // Add counts for mobile app
            $data['workouts_count'] = Workout::whereHas('assignments', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('is_active', true)->count();

            $data['nutrition_plans_count'] = NutritionPlan::whereHas('assignments', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('is_active', true)->count();

            $data['upcoming_events_count'] = CalendarEvent::where('user_id', $user->id)
                ->where('date', '>=', Carbon::today()->toDateString())
                ->count();
        }

        // Admin dashboard
        if ($user->role === 'admin') {
            // Get students count
            $studentsCount = \App\Models\User::where('admin_id', $user->id)->count();

            // Get own workouts count
            $workoutsCount = Workout::where('admin_id', $user->id)->count();

            // Get own nutrition plans count
            $nutritionPlansCount = NutritionPlan::where('admin_id', $user->id)->count();

            // Get unread messages count
            $unreadMessagesCount = Message::where('receiver_id', $user->id)
                ->whereNull('read_at')
                ->count();

            // Get recent students (last 5)
            $recentStudents = \App\Models\User::where('admin_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $data['students_count'] = $studentsCount;
            $data['workouts_count'] = $workoutsCount;
            $data['nutrition_plans_count'] = $nutritionPlansCount;
            $data['unread_messages_count'] = $unreadMessagesCount;
            $data['recent_students'] = $recentStudents;
        }

        return response()->json($data);
    }

    /**
     * Get a random motivational quote
     */
    public function randomQuote(Request $request)
    {
        $quote = MotivationalQuote::where('is_active', true)
            ->inRandomOrder()
            ->first();

        if (!$quote) {
            return response()->json([
                'quote' => 'Stay strong and keep pushing!',
                'author' => 'Unknown',
            ]);
        }

        return response()->json($quote);
    }

    /**
     * Get a random motivational quote (private helper)
     */
    private function getRandomQuote()
    {
        $quote = MotivationalQuote::where('is_active', true)
            ->inRandomOrder()
            ->first();

        if (!$quote) {
            return [
                'quote' => 'Stay strong and keep pushing!',
                'author' => 'Unknown',
            ];
        }

        return $quote;
    }

    /**
     * Get activity summary for the week
     */
    public function weeklySummary(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'student') {
            return response()->json([
                'message' => 'Only students can view weekly summary'
            ], 403);
        }

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // Get this week's events
        $events = CalendarEvent::where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get();

        // Count by type
        $summary = [
            'total_events' => $events->count(),
            'workout_events' => $events->where('type', 'workout')->count(),
            'nutrition_events' => $events->where('type', 'nutrition')->count(),
            'rest_events' => $events->where('type', 'rest')->count(),
            'assessment_events' => $events->where('type', 'assessment')->count(),
            'week_start' => $startOfWeek->toDateString(),
            'week_end' => $endOfWeek->toDateString(),
        ];

        return response()->json($summary);
    }
}
