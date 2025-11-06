<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk view v_security_summary
 * View ini menampilkan ringkasan security events 7 hari terakhir
 */
class SecuritySummary extends Model
{
    protected $table = 'v_security_summary';

    public $timestamps = false;

    // View adalah read-only
    protected $guarded = ['*'];

    protected $casts = [
        'total_events' => 'integer',
        'unique_ips' => 'integer',
        'last_occurrence' => 'datetime',
    ];

    // Scopes
    public function scopeByApp($query, $appName)
    {
        return $query->where('app_name', $appName);
    }

    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('severity', 'high');
    }

    public function scopeOrderByTotal($query, $direction = 'desc')
    {
        return $query->orderBy('total_events', $direction);
    }
}