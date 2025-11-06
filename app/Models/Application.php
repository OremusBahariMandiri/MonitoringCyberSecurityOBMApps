<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'api_key',
        'url',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Hapus api_key dari hidden karena kita perlu melihatnya untuk debugging
    // protected $hidden = [
    //    'api_key',
    // ];

    // Relationships
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function userSessions()
    {
        return $this->hasMany(UserSession::class);
    }

    public function securityLogs()
    {
        return $this->hasMany(SecurityLog::class);
    }

    public function ipManagement()
    {
        return $this->hasMany(IpManagement::class);
    }

    public function throttleLogs()
    {
        return $this->hasMany(ThrottleLog::class);
    }

    public function dataChanges()
    {
        return $this->hasMany(DataChange::class);
    }

    public function apiRequests()
    {
        return $this->hasMany(ApiRequest::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function statisticsDaily()
    {
        return $this->hasMany(StatisticsDaily::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    // HAPUS setter api_key yang melakukan bcrypt
    // public function setApiKeyAttribute($value)
    // {
    //     $this->attributes['api_key'] = bcrypt($value);
    // }

    // Helper Methods
    public static function generateApiKey()
    {
        return Str::random(64);
    }

    // Ubah verifyApiKey untuk plaintext comparison dengan logging
    public function verifyApiKey($apiKey)
    {
        $matches = $this->api_key === $apiKey;
        
        // Tambahkan logging untuk debug
        Log::info('API Key verification', [
            'app_id' => $this->id,
            'app_name' => $this->name,
            'matches' => $matches,
            'provided_key' => $apiKey,
            'stored_key' => $this->api_key
        ]);
        
        return $matches;
    }
}