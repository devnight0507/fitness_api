<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Exception\ApiErrorException;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Get available subscription plans
     */
    public function getPlans(Request $request)
    {
        $prices = config('services.stripe.prices');

        $plans = [
            [
                'category' => 'StartClass',
                'name' => 'START CLASS – AULAS',
                'description' => 'Unlimited classes access',
                'plans' => [
                    [
                        'type' => 'monthly',
                        'price_id' => $prices['startclass']['monthly'],
                        'price' => 39.00,
                        'total_price' => 39.00,
                        'currency' => 'eur',
                        'interval' => 'month',
                        'display_price' => '€39/mês',
                        'discount' => null,
                    ],
                    [
                        'type' => 'quarterly',
                        'price_id' => $prices['startclass']['quarterly'],
                        'price' => 35.00,
                        'total_price' => 105.00,
                        'currency' => 'eur',
                        'interval' => '3 months',
                        'display_price' => '€105 (€35/mês)',
                        'discount' => '10%',
                    ],
                    [
                        'type' => 'annual',
                        'price_id' => $prices['startclass']['annual'],
                        'price' => 30.00,
                        'total_price' => 360.00,
                        'currency' => 'eur',
                        'interval' => 'year',
                        'display_price' => '€360 (€30/mês)',
                        'discount' => '25%',
                    ],
                ],
                'features' => [
                    '✔ Unlimited Pilates classes',
                    '✔ Unlimited Mobility classes',
                    '✔ Unlimited Core classes',
                    '✔ Unlimited Stretching',
                    '✔ Postural Correction',
                    '✔ New content added regularly',
                    '✔ Watch anytime, anywhere',
                ],
                'not_included' => [
                    '✖ Custom workout plan',
                    '✖ Custom nutrition plan',
                    '✖ Monthly assessment',
                    '✖ Workout/diet adjustments',
                    '✖ Direct chat',
                    '✖ Individual support',
                    '✖ Calendar with events',
                ],
            ],
            [
                'category' => 'UpLevel',
                'name' => 'UP LEVEL – PREMIUM',
                'description' => 'Individual coaching with full personalization',
                'plans' => [
                    [
                        'type' => 'monthly',
                        'price_id' => $prices['uplevel']['monthly'],
                        'price' => 119.00,
                        'total_price' => 119.00,
                        'currency' => 'eur',
                        'interval' => 'month',
                        'display_price' => '€119/mês',
                        'discount' => null,
                    ],
                    [
                        'type' => 'quarterly',
                        'price_id' => $prices['uplevel']['quarterly'],
                        'price' => 110.00,
                        'total_price' => 330.00,
                        'currency' => 'eur',
                        'interval' => '3 months',
                        'display_price' => '€330 (€110/mês)',
                        'discount' => '8%',
                    ],
                    [
                        'type' => 'annual',
                        'price_id' => $prices['uplevel']['annual'],
                        'price' => 90.00,
                        'total_price' => 1080.00,
                        'currency' => 'eur',
                        'interval' => 'year',
                        'display_price' => '€1080 (€90/mês)',
                        'discount' => '25%',
                    ],
                ],
                'features' => [
                    '✔ Everything from Start Class',
                    '✔ Custom workout plan',
                    '✔ Custom nutrition plan',
                    '✔ Weekly/monthly adjustments',
                    '✔ Monthly assessment',
                    '✔ Calendar with training and events',
                    '✔ Direct chat with trainer',
                    '✔ Individual support',
                    '✔ Personalized responses',
                    '✔ Continuous support',
                    '✔ Error correction',
                    '✔ Plan adapted to your routine',
                    '✔ Progress monitoring',
                    '✔ Adjustments based on evolution',
                ],
                'not_included' => [],
            ],
        ];

        return response()->json([
            'success' => true,
            'plans' => $plans,
        ]);
    }

    /**
     * Create Stripe Checkout Session
     */
    public function createCheckoutSession(Request $request)
    {
        $validated = $request->validate([
            'price_id' => 'required|string',
        ]);

        $user = $request->user();

        try {
            // Check if user already has an active subscription
            $activeSubscription = $user->subscriptions()
                ->where('status', 'active')
                ->where('current_period_end', '>', now())
                ->first();

            if ($activeSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active subscription. Please cancel your current subscription before purchasing a new one.',
                    'active_subscription' => [
                        'plan_category' => $activeSubscription->plan_category,
                        'plan_type' => $activeSubscription->plan_type,
                        'expires_at' => $activeSubscription->current_period_end,
                    ],
                ], 400);
            }

            // Map price_id to plan details
            $planDetails = $this->getPlanDetailsByPriceId($validated['price_id']);

            if (!$planDetails) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid plan selected',
                ], 400);
            }

            $session = StripeCheckoutSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $validated['price_id'],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => 'mfitness://payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'mfitness://payment/failure',
                'client_reference_id' => $user->id,
                'customer_email' => $user->email,
                'metadata' => [
                    'user_id' => (string)$user->id,
                    'plan_category' => $planDetails['category'],
                    'plan_type' => $planDetails['type'],
                ],
                'subscription_data' => [
                    'metadata' => [
                        'user_id' => (string)$user->id,
                        'plan_category' => $planDetails['category'],
                        'plan_type' => $planDetails['type'],
                    ],
                ],
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $session->id,
                'checkout_url' => $session->url,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe checkout session creation failed', [
                'user_id' => $user->id,
                'price_id' => $validated['price_id'],
                'error' => $e->getMessage(),
                'stripe_key_configured' => config('services.stripe.secret') ? 'yes' : 'no',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create checkout session',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error creating checkout session', [
                'user_id' => $user->id,
                'price_id' => $validated['price_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's active subscription
     */
    public function getActiveSubscription(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscriptions()->where('status', 'active')->first();

        if (!$subscription) {
            return response()->json([
                'success' => true,
                'subscription' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'subscription' => $subscription,
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscriptions()->where('status', 'active')->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        try {
            // Cancel subscription at Stripe
            $stripeSubscription = \Stripe\Subscription::retrieve($subscription->stripe_subscription_id);
            $stripeSubscription->cancel();

            // Update local subscription
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription canceled successfully. Access will continue until end of billing period.',
            ]);
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper function to map price_id to plan details
     */
    private function getPlanDetailsByPriceId($priceId)
    {
        $prices = config('services.stripe.prices');

        $priceMap = [
            // Start Class
            $prices['startclass']['monthly'] => ['category' => 'StartClass', 'type' => 'monthly'],
            $prices['startclass']['quarterly'] => ['category' => 'StartClass', 'type' => 'quarterly'],
            $prices['startclass']['annual'] => ['category' => 'StartClass', 'type' => 'annual'],
            // Up Level
            $prices['uplevel']['monthly'] => ['category' => 'UpLevel', 'type' => 'monthly'],
            $prices['uplevel']['quarterly'] => ['category' => 'UpLevel', 'type' => 'quarterly'],
            $prices['uplevel']['annual'] => ['category' => 'UpLevel', 'type' => 'annual'],
        ];

        return $priceMap[$priceId] ?? null;
    }
}
