<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'app_name',
        'user_id',
        'user_email',
        'table_name',
        'record_id',
        'action',
        'old_values',
        'new_values',
        'changed_fields',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'user_id' => 'integer',
        'record_id' => 'integer',
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constants
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_RESTORE = 'restore';

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

    public function scopeByTable($query, $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    public function scopeByRecord($query, $recordId)
    {
        return $query->where('record_id', $recordId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeCreates($query)
    {
        return $query->where('action', self::ACTION_CREATE);
    }

    public function scopeUpdates($query)
    {
        return $query->where('action', self::ACTION_UPDATE);
    }

    public function scopeDeletes($query)
    {
        return $query->where('action', self::ACTION_DELETE);
    }

    public function scopeRestores($query)
    {
        return $query->where('action', self::ACTION_RESTORE);
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
    public function getIsCreateAttribute()
    {
        return $this->action === self::ACTION_CREATE;
    }

    public function getIsUpdateAttribute()
    {
        return $this->action === self::ACTION_UPDATE;
    }

    public function getIsDeleteAttribute()
    {
        return $this->action === self::ACTION_DELETE;
    }

    public function getIsRestoreAttribute()
    {
        return $this->action === self::ACTION_RESTORE;
    }

    // Helper Methods
    public static function logChange($applicationId, $tableName, $recordId, $action, $oldValues = null, $newValues = null, array $metadata = [])
    {
        $changedFields = null;

        if ($action === self::ACTION_UPDATE && $oldValues && $newValues) {
            $changedFields = array_keys(array_diff_assoc(
                is_array($newValues) ? $newValues : $newValues->toArray(),
                is_array($oldValues) ? $oldValues : $oldValues->toArray()
            ));
        }

        return self::create([
            'application_id' => $applicationId,
            'app_name' => $metadata['app_name'] ?? null,
            'user_id' => $metadata['user_id'] ?? null,
            'user_email' => $metadata['user_email'] ?? null,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'ip_address' => $metadata['ip_address'] ?? request()->ip(),
            'user_agent' => $metadata['user_agent'] ?? request()->userAgent(),
        ]);
    }

    public function getDifference()
    {
        if (!$this->is_update) {
            return null;
        }

        $diff = [];
        foreach ($this->changed_fields ?? [] as $field) {
            $diff[$field] = [
                'old' => $this->old_values[$field] ?? null,
                'new' => $this->new_values[$field] ?? null,
            ];
        }

        return $diff;
    }
}