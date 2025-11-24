<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'stripe_price_id',
        'stripe_product_id',
        'plan_category',
        'plan_type',
        'status',
        'current_period_start',
        'current_period_end',
        'canceled_at',
        'trial_ends_at',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               $this->current_period_end &&
               $this->current_period_end->isFuture();
    }

    /**
     * Check if subscription is canceled
     */
    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    /**
     * Check if user has Up Level plan
     */
    public function isUpLevel(): bool
    {
        return $this->plan_category === 'UpLevel';
    }

    /**
     * Check if user has Start Class plan
     */
    public function isStartClass(): bool
    {
        return $this->plan_category === 'StartClass';
    }
}
