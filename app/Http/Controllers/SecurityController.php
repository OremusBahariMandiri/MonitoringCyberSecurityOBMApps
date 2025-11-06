<?php

namespace App\Http\Controllers;

use App\Models\{SecurityLog, Application};
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        $query = SecurityLog::with('application')->latest();

        // Apply filters
        if ($request->filled('application_id')) {
            $query->byApplication($request->application_id);
        }

        if ($request->filled('event_type')) {
            $query->byEventType($request->event_type);
        }

        if ($request->filled('severity')) {
            $query->bySeverity($request->severity);
        }

        if ($request->filled('status')) {
            if ($request->status === 'unresolved') {
                $query->unresolved();
            } elseif ($request->status === 'resolved') {
                $query->resolved();
            }
        }

        if ($request->filled('ip_address')) {
            $query->byIp($request->ip_address);
        }

        $securityLogs = $query->paginate(20);
        $applications = Application::active()->get();

        // Get counts by severity
        $criticalCount = SecurityLog::unresolved()->critical()->count();
        $highCount = SecurityLog::unresolved()->high()->count();
        $mediumCount = SecurityLog::unresolved()->bySeverity('medium')->count();
        $resolvedCount = SecurityLog::resolved()->recent(30)->count();

        return view('security.index', compact(
            'securityLogs',
            'applications',
            'criticalCount',
            'highCount',
            'mediumCount',
            'resolvedCount'
        ));
    }

    public function show($id)
    {
        $log = SecurityLog::with('application')->findOrFail($id);
        return response()->json($log);
    }

    public function resolve($id, Request $request)
    {
        $log = SecurityLog::findOrFail($id);
        $log->resolve(auth()->id() ?? null, $request->input('notes'));

        return back()->with('success', 'Security event resolved successfully');
    }

    public function unresolve($id)
    {
        $log = SecurityLog::findOrFail($id);
        $log->unresolve();

        return back()->with('success', 'Security event reopened successfully');
    }
}
