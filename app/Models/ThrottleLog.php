<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThrottleLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'app_name',
        'ip_address',
        'user_id',
        'endpoint',
        'method',
        'request_count',
        'limit_exceeded',
        'throttle_key',
        'window_start',
        'window_end',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'user_id' => 'integer',
        'request_count' => 'integer',
        'limit_exceeded' => 'boolean',
        'window_start' => 'datetime',
        'window_end' => 'datetime',
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

    public function scopeByIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByEndpoint($query, $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    public function scopeLimitExceeded($query)
    {
        return $query->where('limit_exceeded', true);
    }

    public function scopeActiveWindow($query)
    {
        return $query->where('window_start', '<=', now())
                     ->where('window_end', '>=', now());
    }

    public function scopeExpiredWindow($query)
    {
        return $query->where('window_end', '<', now());
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Accessors
    public function getIsActiveWindowAttribute()
    {
        return now()->between($this->window_start, $this->window_end);
    }

    public function getWindowDurationAttribute()
    {
        return $this->window_start->diffInMinutes($this->window_end);
    }

    // Helper Methods
    public function incrementRequestCount()
    {
        $this->increment('request_count');
    }

    public function markAsExceeded()
    {
        $this->update(['limit_exceeded' => true]);
    }

    public static function logRequest($applicationId, $ipAddress, $endpoint, $method, $throttleKey, $windowMinutes = 1)
    {
        $windowStart = now();
        $windowEnd = now()->addMinutes($windowMinutes);

        return self::updateOrCreate(
            [
                'application_id' => $applicationId,
                'ip_address' => $ipAddress,
                'endpoint' => $endpoint,
                'throttle_key' => $throttleKey,
            ],
            [
                'method' => $method,
                'request_count' => \DB::raw('request_count + 1'),
                'window_start' => $windowStart,
                'window_end' => $windowEnd,
            ]
        );
    }
}