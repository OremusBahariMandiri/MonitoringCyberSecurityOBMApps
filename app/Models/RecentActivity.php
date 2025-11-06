<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk view v_recent_activities
 * View ini menampilkan aktivitas 24 jam terakhir
 */
class RecentActivity extends Model
{
    protected $table = 'v_recent_activities';

    public $timestamps = false;

    // View adalah read-only
    protected $guarded = ['*'];

    protected $casts = [
        'id' => 'integer',
        'created_at' => 'datetime',
    ];

    // Scopes
    public function scopeByApp($query, $appName)
    {
        return $query->where('app_name', $appName);
    }

    public function scopeByUser($query, $email)
    {
        return $query->where('user_email', $email);
    }

    public function scopeByActivityType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeByIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }
}