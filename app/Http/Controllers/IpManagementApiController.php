<?php

namespace App\Http\Controllers;

use App\Models\IpManagement;
use Illuminate\Http\Request;

class IpManagementApiController extends Controller
{
    public function checkStatus($ip, Request $request)
    {
        // Ambil application_id dari request jika ada
        $applicationId = $request->header('X-Application-ID');

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

        return response()->json($status);
    }
}