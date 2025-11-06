<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IpManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IpManagementApiController extends Controller
{
    /**
     * Check IP status
     * GET /api/ip/check/{ip}
     */
    public function checkStatus($ip, Request $request)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid IP address',
            ], 422);
        }

        // Ambil application_id dari request attributes (diset oleh middleware)
        $application = $request->attributes->get('application');
        $applicationId = $application ? $application->id : null;

        $status = [
            'ip' => $ip,
            'application_id' => $applicationId,
            'is_whitelisted' => IpManagement::isIpWhitelisted($ip, $applicationId),
            'is_blacklisted' => IpManagement::isIpBlacklisted($ip, $applicationId),
            'is_watched' => IpManagement::isIpWatched($ip, $applicationId),
        ];

        // Tambahkan detail IP jika ada
        $ipDetails = IpManagement::active()
            ->byIp($ip)
            ->where(function ($query) use ($applicationId) {
                $query->whereNull('application_id')
                      ->orWhere('application_id', $applicationId);
            })
            ->first();

        if ($ipDetails) {
            $status['details'] = [
                'type' => $ipDetails->type,
                'reason' => $ipDetails->reason,
                'expires_at' => $ipDetails->expires_at,
            ];
        }

        // Simpan IP baru ke database jika belum ada
        if (!$ipDetails) {
            try {
                IpManagement::create([
                    'ip_address' => $ip,
                    'type' => IpManagement::TYPE_WATCH, // Default sebagai watch
                    'reason' => 'Auto-recorded during IP check',
                    'application_id' => $applicationId,
                    'added_by' => 0, // System
                    'is_active' => true,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to record IP during check', [
                    'error' => $e->getMessage(),
                    'ip' => $ip
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $status,
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
                    if ($application) {
                        $query->where('application_id', $application->id)
                            ->orWhereNull('application_id');
                    } else {
                        $query->whereNull('application_id');
                    }
                })
                ->first();

            if ($existingIp) {
                // Update existing record if needed
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
                    'message' => 'IP already exists and was updated',
                    'data' => $existingIp->fresh(),
                ]);
            }

            // Create new IP record
            $ipManagement = IpManagement::create([
                'ip_address' => $request->ip_address,
                'type' => $request->type,
                'reason' => $request->reason ?? 'Auto-registered via API',
                'application_id' => $application ? $application->id : null,
                'added_by' => 0, // System
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'IP registered successfully',
                'data' => $ipManagement,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register IP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List IPs for application
     * GET /api/ip/list
     */
    public function listIps(Request $request)
    {
        $application = $request->attributes->get('application');
        $type = $request->input('type');

        $query = IpManagement::query();

        // Filter by application
        if ($application) {
            $query->where(function ($q) use ($application) {
                $q->where('application_id', $application->id)
                  ->orWhereNull('application_id');
            });
        }

        // Filter by type
        if ($type && in_array($type, ['whitelist', 'blacklist', 'watch'])) {
            $query->where('type', $type);
        }

        // Filter by active status
        if ($request->has('active')) {
            $active = filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN);
            if ($active) {
                $query->active();
            } else {
                $query->where('is_active', false)
                    ->orWhere(function ($q) {
                        $q->whereNotNull('expires_at')->where('expires_at', '<=', now());
                    });
            }
        }

        $ips = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $ips,
            'pagination' => [
                'total' => $ips->total(),
                'per_page' => $ips->perPage(),
                'current_page' => $ips->currentPage(),
                'last_page' => $ips->lastPage(),
            ],
        ]);
    }
}