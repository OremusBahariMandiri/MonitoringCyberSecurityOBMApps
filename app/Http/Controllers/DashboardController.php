<?php

namespace App\Http\Controllers;

use App\Models\{
    Activity,
    ActiveSession,
    RecentActivity,
    SecuritySummary,
    Application,
    UserSession,
    SecurityLog,
    Alert
};
use App\Services\ActivityHubService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $activityHub;

    public function __construct(ActivityHubService $activityHub)
    {
        $this->activityHub = $activityHub;
    }

    public function index(Request $request)
    {
        // Get dashboard statistics
        $stats = $this->activityHub->getDashboardStats(null, 30);

        // Get all applications
        $applications = Application::active()->get();

        // // Get recent activities (last 24 hours)
        // $recentActivities = RecentActivity::take(10)->get();

        // // Get active sessions (last 15 minutes)
        // $activeSessions = ActiveSession::take(10)->get();

        // Get security summary (last 7 days)
        // $securitySummary = SecuritySummary::take(10)->get();

        // Get activity trend for chart (last 7 days)
        $activityTrend = Activity::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => \Carbon\Carbon::parse($item->date)->format('d M'),
                    'total' => $item->total
                ];
            });

        return view('home', compact(
            'stats',
            'applications',
            // 'recentActivities',
            // 'activeSessions',
            // 'securitySummary',
            'activityTrend'
        ));
    }
}