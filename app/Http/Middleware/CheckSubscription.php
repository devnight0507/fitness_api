<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $requiredPlan = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Check if user has any active subscription
        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('current_period_end', '>', now())
            ->first();

        if (!$activeSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription required. Please subscribe to access this feature.',
                'requires_subscription' => true,
            ], 403);
        }

        // If specific plan required, check if user has that plan
        if ($requiredPlan) {
            if ($requiredPlan === 'UpLevel' && $activeSubscription->plan_category !== 'UpLevel') {
                return response()->json([
                    'success' => false,
                    'message' => 'This feature requires UP LEVEL Premium subscription.',
                    'requires_upgrade' => true,
                    'current_plan' => $activeSubscription->plan_category,
                ], 403);
            }
        }

        // Attach subscription to request for use in controllers
        $request->merge(['active_subscription' => $activeSubscription]);

        return $next($request);
    }
}
