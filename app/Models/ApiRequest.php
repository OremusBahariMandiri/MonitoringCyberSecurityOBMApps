<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'ip_address',
        'endpoint',
        'method',
        'status_code',
        'response_time',
        'payload_size',
        'user_agent',
        'api_key_used',
        'error_message',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'status_code' => 'integer',
        'response_time' => 'decimal:2',
        'payload_size' => 'integer',
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

    public function scopeByEndpoint($query, $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }

    public function scopeByStatusCode($query, $statusCode)
    {
        return $query->where('status_code', $statusCode);
    }

    public function scopeSuccessful($query)
    {
        return $query->whereBetween('status_code', [200, 299]);
    }

    public function scopeFailed($query)
    {
        return $query->where('status_code', '>=', 400);
    }

    public function scopeClientErrors($query)
    {
        return $query->whereBetween('status_code', [400, 499]);
    }

    public function scopeServerErrors($query)
    {
        return $query->whereBetween('status_code', [500, 599]);
    }

    public function scopeSlow($query, $threshold = 1000)
    {
        return $query->where('response_time', '>', $threshold);
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
    public function getIsSuccessfulAttribute()
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }

    public function getIsClientErrorAttribute()
    {
        return $this->status_code >= 400 && $this->status_code < 500;
    }

    public function getIsServerErrorAttribute()
    {
        return $this->status_code >= 500;
    }

    public function getResponseTimeInSecondsAttribute()
    {
        return $this->response_time / 1000;
    }

    public function getPayloadSizeInKbAttribute()
    {
        return round($this->payload_size / 1024, 2);
    }

    public function getPayloadSizeInMbAttribute()
    {
        return round($this->payload_size / (1024 * 1024), 2);
    }

    // Helper Methods
    public static function logRequest(array $data)
    {
        return self::create([
            'application_id' => $data['application_id'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'endpoint' => $data['endpoint'],
            'method' => $data['method'] ?? request()->method(),
            'status_code' => $data['status_code'],
            'response_time' => $data['response_time'] ?? null,
            'payload_size' => $data['payload_size'] ?? null,
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'api_key_used' => $data['api_key_used'] ?? null,
            'error_message' => $data['error_message'] ?? null,
        ]);
    }
}