<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpManagement extends Model
{
    use HasFactory;

    protected $table = 'ip_management';

    protected $fillable = [
        'application_id',
        'ip_address',
        'type',
        'reason',
        'added_by',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'added_by' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constants
    public const TYPE_WHITELIST = 'whitelist';
    public const TYPE_BLACKLIST = 'blacklist';
    public const TYPE_WATCH = 'watch';

    // Relationships
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
    }

    public function scopeWhitelist($query)
    {
        return $query->where('type', self::TYPE_WHITELIST);
    }

    public function scopeBlacklist($query)
    {
        return $query->where('type', self::TYPE_BLACKLIST);
    }

    public function scopeWatch($query)
    {
        return $query->where('type', self::TYPE_WATCH);
    }

    public function scopeByIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeByApplication($query, $applicationId)
    {
        return $query->where('application_id', $applicationId);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('application_id');
    }

    // Accessors
    public function getIsExpiredAttribute()
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isPast();
    }

    public function getIsWhitelistAttribute()
    {
        return $this->type === self::TYPE_WHITELIST;
    }

    public function getIsBlacklistAttribute()
    {
        return $this->type === self::TYPE_BLACKLIST;
    }

    public function getIsWatchAttribute()
    {
        return $this->type === self::TYPE_WATCH;
    }

    // Helper Methods
    public static function isIpWhitelisted($ip, $applicationId = null)
    {
        return self::active()
            ->whitelist()
            ->byIp($ip)
            ->where(function ($query) use ($applicationId) {
                $query->whereNull('application_id')
                      ->orWhere('application_id', $applicationId);
            })
            ->exists();
    }

    public static function isIpBlacklisted($ip, $applicationId = null)
    {
        return self::active()
            ->blacklist()
            ->byIp($ip)
            ->where(function ($query) use ($applicationId) {
                $query->whereNull('application_id')
                      ->orWhere('application_id', $applicationId);
            })
            ->exists();
    }

    public static function isIpWatched($ip, $applicationId = null)
    {
        return self::active()
            ->watch()
            ->byIp($ip)
            ->where(function ($query) use ($applicationId) {
                $query->whereNull('application_id')
                      ->orWhere('application_id', $applicationId);
            })
            ->exists();
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }
}