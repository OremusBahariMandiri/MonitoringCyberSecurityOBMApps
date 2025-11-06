<?php

namespace App\Http\Controllers;

use App\Models\{IpManagement, Application};
use Illuminate\Http\Request;

class IpManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = IpManagement::with('application')->latest();

        if ($request->filled('application_id')) {
            if ($request->application_id === 'global') {
                $query->global();
            } else {
                $query->byApplication($request->application_id);
            }
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'expired') {
                $query->expired();
            }
        }

        $ipManagement = $query->paginate(20);
        $applications = Application::active()->get();

        $whitelistCount = IpManagement::active()->whitelist()->count();
        $blacklistCount = IpManagement::active()->blacklist()->count();
        $watchCount = IpManagement::active()->watch()->count();

        return view('ip-management.index', compact(
            'ipManagement',
            'applications',
            'whitelistCount',
            'blacklistCount',
            'watchCount'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => 'required|ip',
            'type' => 'required|in:whitelist,blacklist,watch',
            'application_id' => 'nullable|exists:applications,id',
            'reason' => 'nullable|string',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        IpManagement::create($validated);

        return back()->with('success', 'IP address added successfully');
    }

    public function show($id)
    {
        $ip = IpManagement::with('application')->findOrFail($id);
        return response()->json($ip);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'ip_address' => 'required|ip',
            'type' => 'required|in:whitelist,blacklist,watch',
            'application_id' => 'nullable|exists:applications,id',
            'reason' => 'nullable|string',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $ip = IpManagement::findOrFail($id);
        $ip->update($validated);

        return back()->with('success', 'IP address updated successfully');
    }

    public function destroy($id)
    {
        IpManagement::findOrFail($id)->delete();
        return back()->with('success', 'IP address deleted successfully');
    }

    public function activate($id)
    {
        $ip = IpManagement::findOrFail($id);
        $ip->activate();

        return back()->with('success', 'IP address activated successfully');
    }

    public function deactivate($id)
    {
        $ip = IpManagement::findOrFail($id);
        $ip->deactivate();

        return back()->with('success', 'IP address deactivated successfully');
    }
}
