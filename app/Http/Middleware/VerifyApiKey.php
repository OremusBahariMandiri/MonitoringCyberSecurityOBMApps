<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ActivityHubService;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    protected $activityHub;

    public function __construct(ActivityHubService $activityHub)
    {
        $this->activityHub = $activityHub;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key is required',
            ], 401);
        }

        $application = $this->activityHub->verifyApiKey($apiKey);

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API Key',
            ], 401);
        }

        // Attach application to request
        $request->merge(['application' => $application]);
        $request->attributes->set('application', $application);

        return $next($request);
    }
}