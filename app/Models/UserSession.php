<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'app_name',
        'user_id',
        'user_email',
        'user_name',
        'ip_address',
        'session_id',
        'user_agent',
        'last_activity',
        'login_at',
        'logout_at',
        'is_active',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'user_id' => 'integer',
        'is_active' => 'boolean',
        'last_activity' => 'datetime',
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByApplication($query, $applicationId)
    {
        return $query->where('application_id', $applicationId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecentActivity($query, $minutes = 15)
    {
        return $query->where('last_activity', '>=', now()->subMinutes($minutes));
    }

    public function scopeIdle($query, $minutes = 15)
    {
        return $query->where('is_active', true)
                     ->where('last_activity', '<', now()->subMinutes($minutes));
    }

    public function scopeLoggedInToday($query)
    {
        return $query->whereDate('login_at', today());
    }

    // Accessors
    public function getIdleMinutesAttribute()
    {
        if (!$this->last_activity) {
            return null;
        }
        return $this->last_activity->diffInMinutes(now());
    }

    public function getSessionDurationAttribute()
    {
        if (!$this->login_at) {
            return null;
        }

        $end = $this->logout_at ?? now();
        return $this->login_at->diffInMinutes($end);
    }

    public function getIsIdleAttribute()
    {
        return $this->is_active && $this->idle_minutes > 15;
    }

    // Helper Methods
    public function markAsActive()
    {
        $this->update([
            'last_activity' => now(),
            'is_active' => true,
        ]);
    }

    public function logout()
    {
        $this->update([
            'is_active' => false,
            'logout_at' => now(),
        ]);
    }
}