<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IpManagement;
use App\Services\ActivityHubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ActivityHubController extends Controller
{
    protected $activityHub;

    public function __construct(ActivityHubService $activityHub)
    {
        $this->activityHub = $activityHub;
    }

    /**
     * Log aktivitas
     * POST /api/activities
     */
    public function logActivity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'activity_type' => 'required|string|max:100',
            'activity_name' => 'required|string|max:255',
            'user_id' => 'nullable|integer',
            'user_email' => 'nullable|email',
            'user_name' => 'nullable|string',
            'description' => 'nullable|string',
            'data' => 'nullable|array',
            'url' => 'nullable|string',
            'method' => 'nullable|string|max:10',
            'status_code' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $application = $request->attributes->get('application');

        // Record IP address if not already in database
        $this->recordIpAddress($request->ip(), $application->id);

        $activity = $this->activityHub->logActivity($application, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Activity logged successfully',
            'data' => $activity,
        ], 201);
    }

    /**
     * Track user session
     * POST /api/sessions/track
     */
    public function trackSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'user_email' => 'required|email',
            'user_name' => 'nullable|string',
            'session_id' => 'required|string',
            'login_at' => 'nullable|date',
            'ip_address' => 'required|ip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $application = $request->attributes->get('application');

        // Record IP address from login to ip_management
        $this->recordIpAddress($request->input('ip_address'), $application->id);

        $session = $this->activityHub->trackSession($application, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Session tracked successfully',
            'data' => $session,
        ], 200);
    }

    /**
     * Record IP address to ip_management if not already recorded
     *
     * @param string $ipAddress
     * @param int $applicationId
     * @return IpManagement|null
     */
    private function recordIpAddress($ipAddress, $applicationId)
    {
        try {
            // Check if IP already exists
            $existingIp = IpManagement::where('ip_address', $ipAddress)
                ->where(function ($query) use ($applicationId) {
                    $query->where('application_id', $applicationId)
                        ->orWhereNull('application_id');
                })
                ->first();

            if (!$existingIp) {
                // Add new IP as "watch" by default
                return IpManagement::create([
                    'ip_address' => $ipAddress,
                    'type' => IpManagement::TYPE_WATCH,
                    'reason' => 'Auto-recorded from user activity',
                    'application_id' => $applicationId,
                    'added_by' => 0, // System
                    'is_active' => true,
                ]);
            }

            return $existingIp;
        } catch (\Throwable $e) {
            Log::error('Failed to record IP address', [
                'error' => $e->getMessage(),
                'ip' => $ipAddress,
                'application_id' => $applicationId
            ]);
            return null;
        }
    }

    /**
     * Logout session
     * POST /api/sessions/logout
     */
    public function logoutSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $application = $request->attributes->get('application');

        $session = \App\Models\UserSession::where('application_id', $application->id)
            ->where('session_id', $request->session_id)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        $session->logout();

        return response()->json([
            'success' => true,
            'message' => 'Session logged out successfully',
        ], 200);
    }

    /**
     * Log security event
     * POST /api/security/log
     */
    public function logSecurityEvent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ip_address' => 'required|ip',
            'event_type' => 'required|in:ddos_attempt,throttle_limit,blocked_ip,suspicious_activity,brute_force,unauthorized_access,blacklisted_ip_access,watched_ip_access',
            'severity' => 'required|in:low,medium,high,critical',
            'user_id' => 'nullable|integer',
            'user_email' => 'nullable|email',
            'url' => 'nullable|string',
            'method' => 'nullable|string',
            'request_count' => 'nullable|integer',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $application = $request->attributes->get('application');

        // For security events, also record IP
        $this->recordIpAddress($request->ip_address, $application->id);

        $log = $this->activityHub->logSecurityEvent(
            $application,
            $request->ip_address,
            $request->event_type,
            $request->severity,
            $request->except(['ip_address', 'event_type', 'severity'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Security event logged successfully',
            'data' => $log,
        ], 201);
    }

    /**
     * Log data change
     * POST /api/data-changes
     */
    public function logDataChange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table_name' => 'required|string|max:100',
            'record_id' => 'required|integer',
            'action' => 'required|in:create,update,delete,restore',
            'user_id' => 'nullable|integer',
            'user_email' => 'nullable|email',
            'old_values' => 'nullable|array',
            'new_values' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $application = $request->attributes->get('application');

        // Record IP for data changes as well
        $this->recordIpAddress($request->ip(), $application->id);

        $change = $this->activityHub->logDataChange(
            $application,
            $request->table_name,
            $request->record_id,
            $request->action,
            $request->old_values,
            $request->new_values,
            $request->only(['user_id', 'user_email'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Data change logged successfully',
            'data' => $change,
        ], 201);
    }

    /**
     * Check IP status
     * GET /api/ip/check/{ip}
     */
    public function checkIpStatus(Request $request, $ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid IP address',
            ], 422);
        }

        $application = $request->attributes->get('application');

        $isBlocked = $this->activityHub->isIpBlocked($application->id, $ip);
        $isWhitelisted = $this->activityHub->isIpWhitelisted($application->id, $ip);
        $isWatched = $this->activityHub->isIpWatched($application->id, $ip);

        // If this is a new IP, record it
        $this->recordIpAddress($ip, $application->id);

        return response()->json([
            'success' => true,
            'data' => [
                'ip_address' => $ip,
                'is_blacklisted' => $isBlocked,
                'is_whitelisted' => $isWhitelisted,
                'is_watched' => $isWatched,
                'status' => $isBlocked ? 'blacklisted' : ($isWhitelisted ? 'whitelisted' : ($isWatched ? 'watched' : 'normal')),
            ],
        ], 200);
    }

    /**
     * Register IP address
     * POST /api/ip/register
     */
    public function registerIp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ip_address' => 'required|ip',
            'type' => 'required|in:whitelist,blacklist,watch',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $application = $request->attributes->get('application');

        try {
            // Check if IP already exists
            $existingIp = IpManagement::where('ip_address', $request->ip_address)
                ->where(function ($query) use ($application) {
                    $query->where('application_id', $application->id)
                        ->orWhereNull('application_id');
                })
                ->first();

            if ($existingIp) {
                // Update existing record if type or reason changed
                if ($existingIp->type !== $request->type ||
                    $existingIp->reason !== ($request->reason ?? 'Auto-registered via API')) {

                    $existingIp->update([
                        'type' => $request->type,
                        'reason' => $request->reason ?? 'Auto-registered via API',
                        'is_active' => true,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'IP updated successfully',
                    'data' => $existingIp->fresh(),
                ]);
            }

            // Create new IP record
            $ipManagement = IpManagement::create([
                'ip_address' => $request->ip_address,
                'type' => $request->type,
                'reason' => $request->reason ?? 'Auto-registered via API',
                'application_id' => $application->id,
                'added_by' => 0, // System
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'IP registered successfully',
                'data' => $ipManagement,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register IP',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     * GET /api/statistics/dashboard
     */
    public function getDashboardStats(Request $request)
    {
        $days = $request->input('days', 30);
        $application = $request->attributes->get('application');

        $stats = $this->activityHub->getDashboardStats($application->id, $days);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ], 200);
    }

    /**
     * Get activity trend
     * GET /api/statistics/activity-trend
     */
    public function getActivityTrend(Request $request)
    {
        $days = $request->input('days', 30);
        $application = $request->attributes->get('application');

        $trend = $this->activityHub->getActivityTrend($application->id, $days);

        return response()->json([
            'success' => true,
            'data' => $trend,
        ], 200);
    }

    /**
     * Get IP statistics
     * GET /api/statistics/ip-summary
     */
    public function getIpStatistics(Request $request)
    {
        $application = $request->attributes->get('application');

        $summary = [
            'total_unique_ips' => IpManagement::where(function ($query) use ($application) {
                $query->where('application_id', $application->id)
                    ->orWhereNull('application_id');
            })->count(),
            'whitelisted' => IpManagement::whitelist()->where(function ($query) use ($application) {
                $query->where('application_id', $application->id)
                    ->orWhereNull('application_id');
            })->count(),
            'blacklisted' => IpManagement::blacklist()->where(function ($query) use ($application) {
                $query->where('application_id', $application->id)
                    ->orWhereNull('application_id');
            })->count(),
            'watched' => IpManagement::watch()->where(function ($query) use ($application) {
                $query->where('application_id', $application->id)
                    ->orWhereNull('application_id');
            })->count(),
            'active' => IpManagement::active()->where(function ($query) use ($application) {
                $query->where('application_id', $application->id)
                    ->orWhereNull('application_id');
            })->count(),
            'expired' => IpManagement::expired()->where(function ($query) use ($application) {
                $query->where('application_id', $application->id)
                    ->orWhereNull('application_id');
            })->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ], 200);
    }
}