<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'app_name',
        'ip_address',
        'event_type',
        'severity',
        'user_id',
        'user_email',
        'url',
        'method',
        'user_agent',
        'request_count',
        'data',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'notes',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'user_id' => 'integer',
        'request_count' => 'integer',
        'resolved_by' => 'integer',
        'is_resolved' => 'boolean',
        'data' => 'array',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constants
    public const EVENT_DDOS_ATTEMPT = 'ddos_attempt';
    public const EVENT_THROTTLE_LIMIT = 'throttle_limit';
    public const EVENT_BLOCKED_IP = 'blocked_ip';
    public const EVENT_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    public const EVENT_BRUTE_FORCE = 'brute_force';
    public const EVENT_UNAUTHORIZED_ACCESS = 'unauthorized_access';

    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

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

    public function scopeByEventType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    public function scopeHigh($query)
    {
        return $query->where('severity', self::SEVERITY_HIGH);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Accessors
    public function getIsCriticalAttribute()
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    public function getIsHighAttribute()
    {
        return $this->severity === self::SEVERITY_HIGH;
    }

    // Helper Methods
    public function resolve($userId = null, $notes = null)
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $userId,
            'notes' => $notes ?? $this->notes,
        ]);
    }

    public function unresolve()
    {
        $this->update([
            'is_resolved' => false,
            'resolved_at' => null,
            'resolved_by' => null,
        ]);
    }

    public static function logEvent($applicationId, $ipAddress, $eventType, $severity, array $data = [])
    {
        return self::create([
            'application_id' => $applicationId,
            'app_name' => $data['app_name'] ?? null,
            'ip_address' => $ipAddress,
            'event_type' => $eventType,
            'severity' => $severity,
            'user_id' => $data['user_id'] ?? null,
            'user_email' => $data['user_email'] ?? null,
            'url' => $data['url'] ?? null,
            'method' => $data['method'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'request_count' => $data['request_count'] ?? 1,
            'data' => $data['additional_data'] ?? null,
        ]);
    }
}