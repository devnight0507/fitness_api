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
        'weight',
        'height',
        'age',
        'goal',
        'trainer_id',
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
    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function students()
    {
        return $this->hasMany(User::class, 'trainer_id');
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
