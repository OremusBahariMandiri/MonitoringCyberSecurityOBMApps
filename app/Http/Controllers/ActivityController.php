<?php

namespace App\Http\Controllers;

use App\Models\{Activity, Application};
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('application')->latest();

        // Apply filters
        if ($request->filled('application_id')) {
            $query->byApplication($request->application_id);
        }

        if ($request->filled('activity_type')) {
            $query->byActivityType($request->activity_type);
        }

        if ($request->filled('date_range')) {
            switch ($request->date_range) {
                case 'today':
                    $query->today();
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', now()->yesterday());
                    break;
                case '7days':
                    $query->recent(7);
                    break;
                case '30days':
                    $query->recent(30);
                    break;
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('activity_name', 'like', "%{$search}%")
                  ->orWhere('user_email', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $activities = $query->paginate(20);
        $applications = Application::active()->get();
        $uniqueUsers = Activity::distinct('user_id')->count('user_id');

        return view('activities.index', compact('activities', 'applications', 'uniqueUsers'));
    }

    public function show($id)
    {
        $activity = Activity::with('application')->findOrFail($id);
        return response()->json($activity);
    }

    public function destroy($id)
    {
        Activity::findOrFail($id)->delete();
        return redirect()->route('activities.index')->with('success', 'Activity deleted successfully');
    }
}