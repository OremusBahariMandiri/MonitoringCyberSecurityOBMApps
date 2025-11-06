<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatisticsDaily extends Model
{
    use HasFactory;

    protected $table = 'statistics_daily';

    protected $fillable = [
        'application_id',
        'date',
        'total_requests',
        'total_users',
        'total_activities',
        'total_errors',
        'total_security_events',
        'unique_ips',
        'avg_response_time',
        'data',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'date' => 'date',
        'total_requests' => 'integer',
        'total_users' => 'integer',
        'total_activities' => 'integer',
        'total_errors' => 'integer',
        'total_security_events' => 'integer',
        'unique_ips' => 'integer',
        'avg_response_time' => 'decimal:2',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    // Scopes
    public function scopeByApplication($query, $applicationId)
    {
        return $query->where('application_id', $applicationId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('date', now()->year)
                     ->whereMonth('date', now()->month);
    }

    public function scopeLastMonth($query)
    {
        return $query->whereYear('date', now()->subMonth()->year)
                     ->whereMonth('date', now()->subMonth()->month);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('date', now()->year);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('date', '>=', now()->subDays($days)->toDateString());
    }

    // Accessors
    public function getErrorRateAttribute()
    {
        if ($this->total_requests == 0) {
            return 0;
        }
        return round(($this->total_errors / $this->total_requests) * 100, 2);
    }

    public function getAvgActivitiesPerUserAttribute()
    {
        if ($this->total_users == 0) {
            return 0;
        }
        return round($this->total_activities / $this->total_users, 2);
    }

    public function getAvgRequestsPerIpAttribute()
    {
        if ($this->unique_ips == 0) {
            return 0;
        }
        return round($this->total_requests / $this->unique_ips, 2);
    }

    // Helper Methods
    public static function generateForDate($applicationId, $date)
    {
        $date = is_string($date) ? $date : $date->toDateString();

        $activities = Activity::byApplication($applicationId)
            ->whereDate('created_at', $date)
            ->selectRaw('
                COUNT(*) as total_activities,
                COUNT(DISTINCT user_id) as total_users,
                COUNT(DISTINCT ip_address) as unique_ips,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as total_errors
            ')
            ->first();

        $securityEvents = SecurityLog::byApplication($applicationId)
            ->whereDate('created_at', $date)
            ->count();

        $apiRequests = ApiRequest::byApplication($applicationId)
            ->whereDate('created_at', $date)
            ->selectRaw('
                COUNT(*) as total_requests,
                AVG(response_time) as avg_response_time
            ')
            ->first();

        return self::updateOrCreate(
            [
                'application_id' => $applicationId,
                'date' => $date,
            ],
            [
                'total_requests' => $apiRequests->total_requests ?? 0,
                'total_users' => $activities->total_users ?? 0,
                'total_activities' => $activities->total_activities ?? 0,
                'total_errors' => $activities->total_errors ?? 0,
                'total_security_events' => $securityEvents,
                'unique_ips' => $activities->unique_ips ?? 0,
                'avg_response_time' => $apiRequests->avg_response_time ?? null,
            ]
        );
    }

    public static function getSummary($applicationId, $days = 30)
    {
        return self::byApplication($applicationId)
            ->recent($days)
            ->selectRaw('
                SUM(total_requests) as total_requests,
                SUM(total_users) as total_users,
                SUM(total_activities) as total_activities,
                SUM(total_errors) as total_errors,
                SUM(total_security_events) as total_security_events,
                AVG(unique_ips) as avg_unique_ips,
                AVG(avg_response_time) as avg_response_time
            ')
            ->first();
    }
}