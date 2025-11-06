<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk view v_active_sessions
 * View ini menampilkan session yang masih aktif
 */
class ActiveSession extends Model
{
    protected $table = 'v_active_sessions';

    public $timestamps = false;

    // View adalah read-only
    protected $guarded = ['*'];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'last_activity' => 'datetime',
        'login_at' => 'datetime',
        'idle_minutes' => 'integer',
    ];

    // Scopes
    public function scopeByApp($query, $appName)
    {
        return $query->where('app_name', $appName);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where('user_email', $email);
    }

    public function scopeIdle($query, $minutes = 5)
    {
        return $query->where('idle_minutes', '>=', $minutes);
    }
}