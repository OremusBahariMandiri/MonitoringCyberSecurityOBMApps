<?php

namespace App\Http\Controllers;

use App\Models\{UserSession, Application};
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $query = UserSession::with('application')->active()->latest('last_activity');

        if ($request->filled('application_id')) {
            $query->byApplication($request->application_id);
        }

        $activeSessions = $query->paginate(20);
        $applications = Application::active()->get();

        $idleSessions = UserSession::active()->idle(5)->count();
        $todayLogins = UserSession::whereDate('login_at', today())->count();
        $uniqueIps = UserSession::active()->distinct('ip_address')->count('ip_address');

        return view('sessions.index', compact(
            'activeSessions',
            'applications',
            'idleSessions',
            'todayLogins',
            'uniqueIps'
        ));
    }

    public function show($id)
    {
        $session = UserSession::with('application')->findOrFail($id);

        // Get recent activities for this user
        $recentActivities = Activity::byApplication($session->application_id)
            ->byUser($session->user_id)
            ->recent(1)
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'session' => $session,
            'recent_activities' => $recentActivities
        ]);
    }

    public function forceLogout($id)
    {
        $session = UserSession::findOrFail($id);
        $session->logout();

        return back()->with('success', 'Session logged out successfully');
    }

    public function closeIdle(Request $request)
    {
        $minutes = $request->input('minutes', 15);
        $count = UserSession::idle($minutes)->update([
            'is_active' => false,
            'logout_at' => now(),
        ]);

        return back()->with('success', "{$count} idle sessions closed successfully");
    }
}
