<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends Controller
{
    /**
     * Handle Stripe webhook events
     */
    public function handleStripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;

            case 'invoice.paid':
                $this->handleInvoicePaid($event->data->object);
                break;

            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event->data->object);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object);
                break;

            default:
                Log::info('Unhandled Stripe webhook event', ['type' => $event->type]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle checkout session completed
     */
    private function handleCheckoutSessionCompleted($session)
    {
        Log::info('Checkout session completed', ['session_id' => $session->id]);

        $userId = $session->metadata->user_id ?? $session->client_reference_id;

        if (!$userId) {
            Log::error('No user_id found in checkout session');
            return;
        }

        // The subscription will be created by customer.subscription.created event
        // This is just for logging
    }

    /**
     * Handle invoice paid (subscription activated/renewed)
     */
    private function handleInvoicePaid($invoice)
    {
        $subscriptionId = $invoice->subscription;

        if (!$subscriptionId) {
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'active',
                'current_period_start' => \Carbon\Carbon::createFromTimestamp($invoice->period_start),
                'current_period_end' => \Carbon\Carbon::createFromTimestamp($invoice->period_end),
            ]);

            Log::info('Subscription activated/renewed', [
                'subscription_id' => $subscriptionId,
                'user_id' => $subscription->user_id,
            ]);
        }
    }

    /**
     * Handle subscription created
     */
    private function handleSubscriptionCreated($stripeSubscription)
    {
        // Get user_id from metadata
        $userId = $stripeSubscription->metadata->user_id ?? null;

        if (!$userId) {
            Log::error('No user_id in subscription metadata');
            return;
        }

        $planCategory = $stripeSubscription->metadata->plan_category ?? 'StartClass';
        $planType = $stripeSubscription->metadata->plan_type ?? 'monthly';

        // Create or update subscription
        Subscription::updateOrCreate(
            ['stripe_subscription_id' => $stripeSubscription->id],
            [
                'user_id' => $userId,
                'stripe_customer_id' => $stripeSubscription->customer,
                'stripe_price_id' => $stripeSubscription->items->data[0]->price->id,
                'stripe_product_id' => $stripeSubscription->items->data[0]->price->product,
                'plan_category' => $planCategory,
                'plan_type' => $planType,
                'status' => $stripeSubscription->status,
                'current_period_start' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end),
            ]
        );

        Log::info('Subscription created', [
            'subscription_id' => $stripeSubscription->id,
            'user_id' => $userId,
            'plan_category' => $planCategory,
            'plan_type' => $planType,
        ]);
    }

    /**
     * Handle subscription updated
     */
    private function handleSubscriptionUpdated($stripeSubscription)
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if ($subscription) {
            $subscription->update([
                'status' => $stripeSubscription->status,
                'current_period_start' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                'canceled_at' => $stripeSubscription->canceled_at
                    ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->canceled_at)
                    : null,
            ]);

            Log::info('Subscription updated', [
                'subscription_id' => $stripeSubscription->id,
                'status' => $stripeSubscription->status,
            ]);
        }
    }

    /**
     * Handle subscription deleted
     */
    private function handleSubscriptionDeleted($stripeSubscription)
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);

            Log::info('Subscription deleted', [
                'subscription_id' => $stripeSubscription->id,
                'user_id' => $subscription->user_id,
            ]);
        }
    }

    /**
     * Handle invoice payment failed
     */
    private function handleInvoicePaymentFailed($invoice)
    {
        $subscriptionId = $invoice->subscription;

        if (!$subscriptionId) {
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'past_due',
            ]);

            Log::warning('Invoice payment failed', [
                'subscription_id' => $subscriptionId,
                'user_id' => $subscription->user_id,
            ]);
        }
    }
}
