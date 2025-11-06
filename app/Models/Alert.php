<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'alert_type',
        'severity',
        'title',
        'message',
        'data',
        'is_read',
        'is_resolved',
        'read_at',
        'resolved_at',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'data' => 'array',
        'is_read' => 'boolean',
        'is_resolved' => 'boolean',
        'read_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constants
    public const TYPE_SECURITY = 'security';
    public const TYPE_PERFORMANCE = 'performance';
    public const TYPE_ERROR = 'error';
    public const TYPE_WARNING = 'warning';
    public const TYPE_INFO = 'info';

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

    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    public function scopeHigh($query)
    {
        return $query->where('severity', self::SEVERITY_HIGH);
    }

    public function scopeSecurity($query)
    {
        return $query->where('alert_type', self::TYPE_SECURITY);
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

    public function getIsSecurityAttribute()
    {
        return $this->alert_type === self::TYPE_SECURITY;
    }

    // Helper Methods
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function resolve()
    {
        if (!$this->is_resolved) {
            $this->update([
                'is_resolved' => true,
                'resolved_at' => now(),
            ]);
        }
    }

    public function unresolve()
    {
        $this->update([
            'is_resolved' => false,
            'resolved_at' => null,
        ]);
    }

    public static function createAlert($applicationId, $type, $severity, $title, $message, array $data = [])
    {
        return self::create([
            'application_id' => $applicationId,
            'alert_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
}