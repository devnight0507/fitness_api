<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
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
        $plans = [
            [
                'category' => 'StartClass',
                'name' => 'START CLASS – AULAS',
                'description' => 'Unlimited classes access',
                'plans' => [
                    [
                        'type' => 'monthly',
                        'price_id' => 'price_1SX42xPDrBJywqOwOp9kxYaX',
                        'price' => 39.00,
                        'currency' => 'eur',
                        'interval' => 'month',
                        'display_price' => '€39/mês',
                        'discount' => null,
                    ],
                    [
                        'type' => 'quarterly',
                        'price_id' => 'price_1SX42cPDrBJywqOwxzoa0Rh0',
                        'price' => 35.00,
                        'currency' => 'eur',
                        'interval' => '3 months',
                        'display_price' => '€35/mês',
                        'discount' => '10%',
                    ],
                    [
                        'type' => 'annual',
                        'price_id' => 'price_1SX42BPDrBJywqOwTvVSlk1l',
                        'price' => 30.00,
                        'currency' => 'eur',
                        'interval' => 'year',
                        'display_price' => '€30/mês',
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
                        'price_id' => 'price_1SX41tPDrBJywqOw662zPAGR',
                        'price' => 119.00,
                        'currency' => 'eur',
                        'interval' => 'month',
                        'display_price' => '€119/mês',
                        'discount' => null,
                    ],
                    [
                        'type' => 'quarterly',
                        'price_id' => 'price_1SX40xPDrBJywqOw73rkmi04',
                        'price' => 110.00,
                        'currency' => 'eur',
                        'interval' => '3 months',
                        'display_price' => '€110/mês',
                        'discount' => '8%',
                    ],
                    [
                        'type' => 'annual',
                        'price_id' => 'price_1SX40DPDrBJywqOwl8kGNjhn',
                        'price' => 90.00,
                        'currency' => 'eur',
                        'interval' => 'year',
                        'display_price' => '€90/mês',
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
                    'user_id' => $user->id,
                    'plan_category' => $planDetails['category'],
                    'plan_type' => $planDetails['type'],
                ],
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $session->id,
                'checkout_url' => $session->url,
            ]);
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create checkout session',
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
        $priceMap = [
            // Start Class
            'price_1SX42xPDrBJywqOwOp9kxYaX' => ['category' => 'StartClass', 'type' => 'monthly'],
            'price_1SX42cPDrBJywqOwxzoa0Rh0' => ['category' => 'StartClass', 'type' => 'quarterly'],
            'price_1SX42BPDrBJywqOwTvVSlk1l' => ['category' => 'StartClass', 'type' => 'annual'],
            // Up Level
            'price_1SX41tPDrBJywqOw662zPAGR' => ['category' => 'UpLevel', 'type' => 'monthly'],
            'price_1SX40xPDrBJywqOw73rkmi04' => ['category' => 'UpLevel', 'type' => 'quarterly'],
            'price_1SX40DPDrBJywqOwl8kGNjhn' => ['category' => 'UpLevel', 'type' => 'annual'],
        ];

        return $priceMap[$priceId] ?? null;
    }
}
