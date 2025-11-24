<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar_path',
        'push_token',
        'notifications_enabled',
        'weight',
        'height',
        'age',
        'goal',
        'injuries',
        'notes',
        'admin_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'avatar_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'weight' => 'decimal:2',
            'height' => 'decimal:2',
        ];
    }

    // Relationships
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function students()
    {
        return $this->hasMany(User::class, 'admin_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function calendarEvents()
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest();
    }

    public function hasActiveSubscription()
    {
        return $this->subscriptions()->where('status', 'active')
            ->where('current_period_end', '>', now())
            ->exists();
    }

    public function hasUpLevelAccess()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('plan_category', 'UpLevel')
            ->where('current_period_end', '>', now())
            ->exists();
    }

    public function hasStartClassAccess()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('plan_category', 'StartClass')
            ->where('current_period_end', '>', now())
            ->exists();
    }

    public function viewLogs()
    {
        return $this->hasMany(ViewLog::class);
    }

    public function assignments()
    {
        return $this->hasMany(UserAssignment::class);
    }

    // Attributes
    public function getAvatarUrlAttribute()
    {
        return $this->avatar_path
            ? url('storage/' . $this->avatar_path)
            : null;
    }
}
