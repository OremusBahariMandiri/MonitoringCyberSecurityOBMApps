<?php

namespace App\Services;

use App\Models\{
    Activity,
    Alert,
    ApiRequest,
    Application,
    DataChange,
    IpManagement,
    SecurityLog,
    StatisticsDaily,
    ThrottleLog,
    UserSession
};
use Illuminate\Support\Facades\DB;

class ActivityHubService
{
    /**
     * Verifikasi API Key dari aplikasi
     */
    public function verifyApiKey($apiKey)
    {
        $application = Application::active()
            ->get()
            ->first(function ($app) use ($apiKey) {
                return $app->verifyApiKey($apiKey);
            });

        return $application;
    }

    /**
     * Log aktivitas dari aplikasi eksternal
     */
    public function logActivity(Application $app, array $data)
    {
        return Activity::create([
            'application_id' => $app->id,
            'app_name' => $app->name,
            'user_id' => $data['user_id'] ?? null,
            'user_email' => $data['user_email'] ?? null,
            'user_name' => $data['user_name'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'activity_type' => $data['activity_type'],
            'activity_name' => $data['activity_name'],
            'description' => $data['description'] ?? null,
            'data' => $data['data'] ?? null,
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'url' => $data['url'] ?? null,
            'method' => $data['method'] ?? null,
            'status_code' => $data['status_code'] ?? null,
        ]);
    }

    /**
     * Track user session
     */
    public function trackSession(Application $app, array $data)
    {
        return UserSession::updateOrCreate(
            [
                'application_id' => $app->id,
                'user_id' => $data['user_id'],
                'session_id' => $data['session_id'],
            ],
            [
                'app_name' => $app->name,
                'user_email' => $data['user_email'],
                'user_name' => $data['user_name'] ?? null,
                'ip_address' => $data['ip_address'] ?? request()->ip(),
                'user_agent' => $data['user_agent'] ?? request()->userAgent(),
                'last_activity' => now(),
                'login_at' => $data['login_at'] ?? now(),
                'is_active' => true,
            ]
        );
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(Application $app, $ipAddress, $eventType, $severity, array $data = [])
    {
        $log = SecurityLog::logEvent($app->id, $ipAddress, $eventType, $severity, array_merge($data, [
            'app_name' => $app->name,
        ]));

        // Check jika perlu auto-block IP
        $this->checkAndBlockSuspiciousIp($app->id, $ipAddress);

        return $log;
    }

    /**
     * Log perubahan data
     */
    public function logDataChange(Application $app, $tableName, $recordId, $action, $oldValues = null, $newValues = null, array $metadata = [])
    {
        return DataChange::logChange(
            $app->id,
            $tableName,
            $recordId,
            $action,
            $oldValues,
            $newValues,
            array_merge($metadata, ['app_name' => $app->name])
        );
    }

    /**
     * Check dan block IP yang mencurigakan
     */
    protected function checkAndBlockSuspiciousIp($applicationId, $ipAddress)
    {
        $suspiciousEvents = SecurityLog::byApplication($applicationId)
            ->byIp($ipAddress)
            ->whereIn('event_type', [
                SecurityLog::EVENT_DDOS_ATTEMPT,
                SecurityLog::EVENT_BRUTE_FORCE,
                SecurityLog::EVENT_SUSPICIOUS_ACTIVITY
            ])
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($suspiciousEvents >= 5) {
            IpManagement::updateOrCreate(
                [
                    'application_id' => $applicationId,
                    'ip_address' => $ipAddress,
                    'type' => IpManagement::TYPE_BLACKLIST,
                ],
                [
                    'reason' => "Auto-blocked: {$suspiciousEvents} suspicious activities detected",
                    'is_active' => true,
                ]
            );

            // Create alert
            Alert::createAlert(
                $applicationId,
                Alert::TYPE_SECURITY,
                Alert::SEVERITY_CRITICAL,
                'IP Auto-blocked',
                "IP {$ipAddress} has been automatically blacklisted due to suspicious activities",
                ['ip_address' => $ipAddress, 'event_count' => $suspiciousEvents]
            );
        }
    }

    /**
     * Check apakah IP di-blacklist
     */
    public function isIpBlocked($applicationId, $ipAddress)
    {
        return IpManagement::isIpBlacklisted($ipAddress, $applicationId);
    }

    /**
     * Check apakah IP di-whitelist
     */
    public function isIpWhitelisted($applicationId, $ipAddress)
    {
        return IpManagement::isIpWhitelisted($ipAddress, $applicationId);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats($applicationId = null, $days = 30)
    {
        $query = Activity::query();

        if ($applicationId) {
            $query->byApplication($applicationId);
        }

        $stats = $query->recent($days)
            ->selectRaw('
                COUNT(*) as total_activities,
                COUNT(DISTINCT user_id) as total_users,
                COUNT(DISTINCT ip_address) as unique_ips,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as total_errors
            ')
            ->first();

        $securityQuery = SecurityLog::query();
        if ($applicationId) {
            $securityQuery->byApplication($applicationId);
        }
        $securityEvents = $securityQuery->recent($days)->count();

        $activeSessionsQuery = UserSession::active();
        if ($applicationId) {
            $activeSessionsQuery->byApplication($applicationId);
        }
        $activeSessions = $activeSessionsQuery->count();

        return [
            'total_activities' => $stats->total_activities ?? 0,
            'total_users' => $stats->total_users ?? 0,
            'unique_ips' => $stats->unique_ips ?? 0,
            'total_errors' => $stats->total_errors ?? 0,
            'security_events' => $securityEvents,
            'active_sessions' => $activeSessions,
        ];
    }

    /**
     * Get activity trend (daily)
     */
    public function getActivityTrend($applicationId = null, $days = 30)
    {
        $query = Activity::query();

        if ($applicationId) {
            $query->byApplication($applicationId);
        }

        return $query->recent($days)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get top activities
     */
    public function getTopActivities($applicationId = null, $days = 7, $limit = 10)
    {
        $query = Activity::query();

        if ($applicationId) {
            $query->byApplication($applicationId);
        }

        return $query->recent($days)
            ->selectRaw('activity_type, activity_name, COUNT(*) as total')
            ->groupBy('activity_type', 'activity_name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /**
     * Get security events summary
     */
    public function getSecuritySummary($applicationId = null, $days = 7)
    {
        $query = SecurityLog::query();

        if ($applicationId) {
            $query->byApplication($applicationId);
        }

        return $query->recent($days)
            ->selectRaw('event_type, severity, COUNT(*) as total, COUNT(DISTINCT ip_address) as unique_ips')
            ->groupBy('event_type', 'severity')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Cleanup old logs
     */
    public function cleanupOldLogs($daysToKeep = 90)
    {
        return DB::select('CALL sp_cleanup_old_logs(?)', [$daysToKeep]);
    }

    /**
     * Generate daily statistics
     */
    public function generateDailyStatistics($date = null)
    {
        $date = $date ?? now()->subDay()->toDateString();

        $applications = Application::active()->get();

        foreach ($applications as $app) {
            StatisticsDaily::generateForDate($app->id, $date);
        }

        return true;
    }

    /**
     * Close inactive sessions
     */
    public function closeInactiveSessions($inactiveMinutes = 30)
    {
        return UserSession::where('is_active', true)
            ->where('last_activity', '<', now()->subMinutes($inactiveMinutes))
            ->update([
                'is_active' => false,
                'logout_at' => now(),
            ]);
    }
}