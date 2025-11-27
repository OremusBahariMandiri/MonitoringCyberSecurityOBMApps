<?php

namespace App\Http\Controllers;

use App\Models\{Activity, Application};
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function __construct()
    {
        // Set timezone ke WIB
        Carbon::setLocale('id');
        date_default_timezone_set('Asia/Jakarta');
    }

    public function index(Request $request)
    {
        try {
            $query = Activity::with('application')->latest();

            // Apply filters
            $this->applyFilters($query, $request);

            // Get activities with pagination
            $activities = $query->paginate(
                $request->input('per_page', 20),
                ['*'],
                'page',
                $request->input('page', 1)
            )->appends($request->query());

            // Get applications for filter dropdown
            $applications = Application::active()->get();

            // Calculate statistics
            $stats = $this->calculateStats($request);

            return view('activities.index', array_merge([
                'activities' => $activities,
                'applications' => $applications,
                'filters' => $this->getAppliedFilters($request),
            ], $stats));

        } catch (\Exception $e) {
            \Log::error('Activities index error: ' . $e->getMessage());

            return view('activities.index', [
                'activities' => collect([]),
                'applications' => collect([]),
                'filters' => $this->getAppliedFilters($request),
                'error' => 'Unable to load activities. Please try again later.',
                'totalActivities' => 0,
                'uniqueUsers' => 0,
                'successRate' => 0,
                'errorRate' => 0,
            ]);
        }
    }

    public function show($id)
    {
        try {
            $activity = Activity::with('application')->findOrFail($id);

            // Format data untuk detail view
            $activity->created_at_formatted = Carbon::parse($activity->created_at)
                ->setTimezone('Asia/Jakarta')
                ->format('d/m/Y H:i:s');

            return view('activities.detail', compact('activity'));

        } catch (\Exception $e) {
            return redirect()->route('activities.index')
                ->with('error', 'Activity not found');
        }
    }

    public function export(Request $request)
    {
        try {
            $query = Activity::with('application')->latest();

            // Apply same filters as index
            $this->applyFilters($query, $request);

            // Limit export untuk performance
            $activities = $query->limit($request->input('limit', 10000))->get();

            $filename = 'activities_export_' . Carbon::now('Asia/Jakarta')->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($activities) {
                $file = fopen('php://output', 'w');

                // CSV Headers
                fputcsv($file, [
                    'Timestamp',
                    'Application',
                    'User Name',
                    'User Email',
                    'Activity Type',
                    'Activity Name',
                    'IP Address',
                    'Method',
                    'Status Code',
                    'URL',
                    'User Agent',
                ]);

                // CSV Data
                foreach ($activities as $activity) {
                    fputcsv($file, [
                        Carbon::parse($activity->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
                        $activity->application->name ?? $activity->app_name ?? 'N/A',
                        $activity->user_name ?? 'N/A',
                        $activity->user_email ?? 'N/A',
                        $activity->activity_type ?? 'N/A',
                        $activity->activity_name ?? 'N/A',
                        $activity->ip_address ?? 'N/A',
                        $activity->method ?? 'N/A',
                        $activity->status_code ?? 'N/A',
                        $activity->url ?? 'N/A',
                        $activity->user_agent ?? 'N/A',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            \Log::error('Activities export error: ' . $e->getMessage());
            return back()->with('error', 'Export failed. Please try again.');
        }
    }

    public function destroy($id)
    {
        try {
            $activity = Activity::findOrFail($id);
            $activity->delete();

            return redirect()->route('activities.index')
                ->with('success', 'Activity deleted successfully');

        } catch (\Exception $e) {
            return redirect()->route('activities.index')
                ->with('error', 'Failed to delete activity');
        }
    }

    /**
     * Apply filters to the query
     */
    private function applyFilters($query, Request $request)
    {
        // Application filter
        if ($request->filled('application_id')) {
            $query->byApplication($request->application_id);
        }

        // Activity Type filter
        if ($request->filled('activity_type')) {
            $query->byActivityType($request->activity_type);
        }

        // Date Range filter
        if ($request->filled('date_range')) {
            $this->applyDateFilter($query, $request);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('activity_name', 'like', "%{$search}%")
                  ->orWhere('user_email', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            });
        }

        // Advanced filters
        if ($request->filled('user_email')) {
            $query->where('user_email', 'like', '%' . $request->user_email . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        if ($request->filled('status_code')) {
            $query->where('status_code', $request->status_code);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if (in_array($sortBy, ['created_at', 'activity_name', 'user_email', 'status_code'])) {
            $query->orderBy($sortBy, $sortOrder);
        }
    }

    /**
     * Apply date filter to query
     */
    private function applyDateFilter($query, Request $request)
    {
        $dateRange = $request->date_range;
        $now = Carbon::now('Asia/Jakarta');

        switch ($dateRange) {
            case 'today':
                $query->today();
                break;

            case 'yesterday':
                $query->whereDate('created_at', $now->subDay()->toDateString());
                break;

            case '7days':
                $query->recent(7);
                break;

            case '30days':
                $query->recent(30);
                break;

            case 'custom':
                if ($request->filled('custom_date_from')) {
                    $fromDate = Carbon::parse($request->custom_date_from, 'Asia/Jakarta')->startOfDay();
                    $query->where('created_at', '>=', $fromDate);
                }

                if ($request->filled('custom_date_to')) {
                    $toDate = Carbon::parse($request->custom_date_to, 'Asia/Jakarta')->endOfDay();
                    $query->where('created_at', '<=', $toDate);
                }
                break;
        }
    }

    /**
     * Calculate statistics for dashboard
     */
    private function calculateStats(Request $request)
    {
        try {
            // Create base query for stats (same filters as main query)
            $statsQuery = Activity::query();
            $this->applyFilters($statsQuery, $request);

            // Clone query untuk berbagai perhitungan
            $totalActivities = (clone $statsQuery)->count();
            $uniqueUsers = (clone $statsQuery)->distinct('user_id')->count('user_id');

            // Success rate (status code 200-299)
            $successCount = (clone $statsQuery)
                ->whereBetween('status_code', [200, 299])
                ->count();

            // Error rate (status code 400+)
            $errorCount = (clone $statsQuery)
                ->where('status_code', '>=', 400)
                ->count();

            $successRate = $totalActivities > 0 ? ($successCount / $totalActivities) * 100 : 0;
            $errorRate = $totalActivities > 0 ? ($errorCount / $totalActivities) * 100 : 0;

            return [
                'totalActivities' => $totalActivities,
                'uniqueUsers' => $uniqueUsers,
                'successRate' => round($successRate, 1),
                'errorRate' => round($errorRate, 1),
            ];

        } catch (\Exception $e) {
            \Log::error('Stats calculation error: ' . $e->getMessage());

            return [
                'totalActivities' => 0,
                'uniqueUsers' => 0,
                'successRate' => 0,
                'errorRate' => 0,
            ];
        }
    }

    /**
     * Get applied filters for view
     */
    private function getAppliedFilters(Request $request)
    {
        return [
            'application_id' => $request->input('application_id'),
            'activity_type' => $request->input('activity_type'),
            'date_range' => $request->input('date_range', '7days'),
            'search' => $request->input('search'),
            'user_email' => $request->input('user_email'),
            'user_id' => $request->input('user_id'),
            'ip_address' => $request->input('ip_address'),
            'method' => $request->input('method'),
            'status_code' => $request->input('status_code'),
            'custom_date_from' => $request->input('custom_date_from'),
            'custom_date_to' => $request->input('custom_date_to'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];
    }
}