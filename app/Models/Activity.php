<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'app_name',
        'user_id',
        'user_email',
        'user_name',
        'ip_address',
        'activity_type',
        'activity_name',
        'description',
        'data',
        'user_agent',
        'url',
        'method',
        'status_code',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'user_id' => 'integer',
        'status_code' => 'integer',
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

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByActivityType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeByIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeWithErrors($query)
    {
        return $query->where('status_code', '>=', 400);
    }

    // Accessors
    public function getIsErrorAttribute()
    {
        return $this->status_code >= 400;
    }

    public function getIsSuccessAttribute()
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }
}